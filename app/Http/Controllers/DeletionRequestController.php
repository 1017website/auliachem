<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerPic;
use App\Models\DeletionRequest;
use App\Models\Lead;
use App\Models\LeadPic;
use App\Models\LeadProduct;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeletionRequestController extends Controller
{
    /**
     * Semua user dapat mengajukan. Administrator/Developer langsung mengeksekusi.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'module' => ['required', Rule::in(array_keys(DeletionRequest::MODULES))],
            'model_id' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $module = $validated['module'];
        $config = DeletionRequest::configFor($module);
        $model = $config['model']::find($validated['model_id']);

        if (!$model) {
            return back()->withErrors(['delete' => 'Data yang akan dihapus tidak ditemukan.']);
        }

        $this->authorizeModule($request, $config, $model);
        $label = DeletionRequest::labelFor($module, $model);

        if ($request->user()->isAdmin()) {
            DB::transaction(function () use ($model, $request) {
                $pendingRequests = DeletionRequest::where('model_type', $model->getMorphClass())
                    ->where('model_id', $model->getKey())
                    ->where('status', 'pending')
                    ->get();

                $this->deleteTarget($model);

                foreach ($pendingRequests as $pendingRequest) {
                    $pendingRequest->update([
                        'status' => 'approved',
                        'reviewed_by' => $request->user()->id,
                        'reviewed_at' => now(),
                        'review_note' => 'Dihapus langsung oleh Administrator/Developer.',
                    ]);

                    Notification::send(
                        $pendingRequest->requested_by,
                        'delete_request',
                        'Permintaan Hapus Disetujui',
                        'Permintaan hapus "' . $pendingRequest->model_label . '" telah disetujui.',
                        route('dashboard')
                    );
                }
            });

            DeletionRequest::clearPendingCache();

            return back()->with('success', 'Data "' . $label . '" berhasil dihapus.');
        }

        $deletionRequest = DeletionRequest::requestFor(
            $module,
            $model,
            $request->user()->id,
            $validated['reason'] ?? null
        );

        Notification::broadcastToAdministrators(
            'delete_request',
            'Permintaan Hapus: ' . $deletionRequest->module_title,
            $request->user()->name . ' meminta hapus "' . $deletionRequest->model_label . '".',
            route('deletion-requests.index')
        );

        return back()->with('success', 'Permintaan hapus dikirim ke Administrator untuk disetujui.');
    }

    public function index(Request $request)
    {
        $status = $request->string('status', 'pending')->toString();
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $requests = DeletionRequest::with(['requester', 'reviewer'])
            ->where('status', $status)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = DeletionRequest::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('deletion_requests.index', compact('requests', 'status', 'counts'));
    }

    public function approve(Request $request, DeletionRequest $deletionRequest)
    {
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $processed = DB::transaction(function () use ($request, $deletionRequest, $validated) {
            $locked = DeletionRequest::whereKey($deletionRequest->id)->lockForUpdate()->firstOrFail();
            if (!$locked->isPending()) {
                return false;
            }

            $config = DeletionRequest::configFor($locked->module);
            $model = $config ? $config['model']::find($locked->model_id) : null;
            if ($model) {
                $this->deleteTarget($model);
            }

            $locked->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
            ]);

            Notification::send(
                $locked->requested_by,
                'delete_request',
                'Permintaan Hapus Disetujui',
                'Permintaan hapus "' . $locked->model_label . '" telah disetujui.',
                $config && isset($config['route']) ? route($config['route']) : route('dashboard')
            );

            return true;
        });

        DeletionRequest::clearPendingCache();

        return $processed
            ? back()->with('success', 'Permintaan disetujui dan data telah dihapus.')
            : back()->withErrors(['delete' => 'Permintaan ini sudah diproses sebelumnya.']);
    }

    public function reject(Request $request, DeletionRequest $deletionRequest)
    {
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $processed = DB::transaction(function () use ($request, $deletionRequest, $validated) {
            $locked = DeletionRequest::whereKey($deletionRequest->id)->lockForUpdate()->firstOrFail();
            if (!$locked->isPending()) {
                return false;
            }

            $locked->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
            ]);

            Notification::send(
                $locked->requested_by,
                'delete_request',
                'Permintaan Hapus Ditolak',
                'Permintaan hapus "' . $locked->model_label . '" ditolak Administrator.',
                route('dashboard')
            );

            return true;
        });

        DeletionRequest::clearPendingCache();

        return $processed
            ? back()->with('success', 'Permintaan hapus ditolak; data tetap tersimpan.')
            : back()->withErrors(['delete' => 'Permintaan ini sudah diproses sebelumnya.']);
    }

    private function authorizeModule(Request $request, array $config, Model $model): void
    {
        $user = $request->user();

        if (isset($config['feature'])) {
            abort_unless($user->canAccess($config['feature']), 403);
        }

        if (!$user->isSalesExecutive()) {
            return;
        }

        $ownerId = match (true) {
            $model instanceof Lead,
            $model instanceof Customer => $model->user_id,
            $model instanceof LeadPic,
            $model instanceof LeadProduct => $model->lead?->user_id,
            $model instanceof CustomerPic => $model->customer?->user_id,
            $model instanceof Activity => $model->sales_user_id ?? $model->user_id,
            default => null,
        };

        abort_if($ownerId !== null && (int) $ownerId !== (int) $user->id, 403);
    }

    private function deleteTarget(Model $model): void
    {
        $model->delete();
    }
}
