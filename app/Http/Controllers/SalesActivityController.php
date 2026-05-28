<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SalesActivityController extends Controller
{
    public function index(Request $request)
    {
        $date    = $request->get('date'); // kosong = semua tanggal
        $salesId = $request->get('user_id');
        $type    = $request->get('activity_type');

        $query = Activity::with(['lead', 'customer', 'salesUser']);

        if ($date) {
            $query->whereDate('activity_at', $date);
        }

        if ($salesId && $salesId !== 'all') {
            $query->where('user_id', $salesId);
        }
        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        $activities = $query->orderBy('activity_at', 'desc')->paginate(10)->withQueryString();

        $todayReminders = Activity::with(['lead', 'customer'])
            ->whereDate('activity_at', today())->orderBy('activity_at')->get();

        $overdueActivities = Activity::where('status', 'Overdue')
            ->with(['lead', 'customer'])->get();

        $upcomingActivities = Activity::whereDate('activity_at', '>', today())
            ->with(['lead', 'customer'])->orderBy('activity_at')->limit(5)->get();

        $salesUsers = User::orderBy('name')->get();

        // Pipeline summary for sidebar
        $pipelineSummary = [
            'Identifying' => ['count' => Lead::where('pipeline_stage', 'Identifying')->count(), 'value' => Lead::where('pipeline_stage', 'Identifying')->sum('potensi_revenue')],
            'Approaching' => ['count' => Lead::where('pipeline_stage', 'Approaching')->count(), 'value' => Lead::where('pipeline_stage', 'Approaching')->sum('potensi_revenue')],
            'Follow Up'   => ['count' => Lead::where('pipeline_stage', 'Follow Up')->count(), 'value' => Lead::where('pipeline_stage', 'Follow Up')->sum('potensi_revenue')],
            'Won/Closing' => ['count' => Lead::where('pipeline_stage', 'Won')->count(), 'value' => Lead::where('pipeline_stage', 'Won')->sum('potensi_revenue')],
            'Maintaining' => ['count' => Lead::where('pipeline_stage', 'Maintaining')->count(), 'value' => Lead::where('pipeline_stage', 'Maintaining')->sum('potensi_revenue')],
        ];

        return view('sales.activity', compact(
            'activities',
            'todayReminders',
            'overdueActivities',
            'upcomingActivities',
            'salesUsers',
            'pipelineSummary',
            'date',
            'salesId',
            'type'
        ));
    }

    public function store(Request $request)
    {
        // Normalisasi activity_at sebelum validasi: terima 'Y-m-d H:i', 'Y-m-d H:i:s',
        // 'Y-m-d\TH:i', dll. Ubah ke format standar yang pasti lolos rule 'date'.
        if ($request->filled('activity_at')) {
            try {
                $request->merge([
                    'activity_at' => \Carbon\Carbon::parse(
                        str_replace('T', ' ', $request->input('activity_at'))
                    )->format('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                // biarkan validasi yang menangkap bila benar-benar tidak valid
            }
        }

        if ($request->filled('next_follow_up')) {
            try {
                $request->merge([
                    'next_follow_up' => \Carbon\Carbon::parse(
                        str_replace('T', ' ', $request->input('next_follow_up'))
                    )->format('Y-m-d'),
                ]);
            } catch (\Throwable $e) {}
        }

        $validated = $request->validate([
            'lead_id'        => 'nullable|exists:leads,id',
            'customer_id'    => 'nullable|exists:customers,id',
            'type'           => 'required|in:Call,Visit,Email,Note,Others',
            'subject'        => 'required|string|max:255',
            'description'    => 'nullable|string',
            'activity_at'    => 'required|date',
            'status'         => 'required|in:Done,Pending,Planned,Overdue',
            'next_follow_up' => 'nullable|date',
            'pipeline_stage' => 'nullable|in:Identifying,Approaching,Follow Up,Won,Lost,Maintaining',
            'photo'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
            'redirect_to'    => 'nullable|string',
        ]);

        // Selalu pakai auth user
        $validated['user_id']       = auth()->id();
        $validated['sales_user_id'] = $validated['sales_user_id'] ?? auth()->id();

        // Resolusi target: lead langsung, atau lead yang terhubung ke customer.
        // Revisi #1: activity dari customer existing harus ikut ter-record di pipeline.
        $targetLead     = null;
        $targetCustomer = null;

        if (!empty($validated['lead_id'])) {
            $targetLead = Lead::find($validated['lead_id']);
        } elseif (!empty($validated['customer_id'])) {
            $targetCustomer = Customer::find($validated['customer_id']);
            if ($targetCustomer) {
                // Cari lead terbaru yang terhubung ke customer ini untuk update stage.
                $targetLead = Lead::where('customer_id', $targetCustomer->id)
                    ->orderByDesc('updated_at')
                    ->first();
            }
        }

        // Update pipeline_stage bila dikirim.
        // Revisi #1 & #4: validasi stage sesuai sumber dilakukan di server.
        if ($request->filled('pipeline_stage')) {
            $requested  = $request->pipeline_stage;
            $isExisting = $targetCustomer && $targetCustomer->status === 'Existing';

            $allowed = $isExisting
                ? ['Follow Up', 'Won', 'Lost', 'Maintaining']                                  // customer existing
                : ['Identifying', 'Approaching', 'Follow Up', 'Won', 'Lost', 'Maintaining'];   // lead / customer potential

            if (in_array($requested, $allowed, true)) {
                // Revisi #1: customer existing tanpa lead → buat lead backing
                // agar perubahan stage muncul di pipeline.
                if (!$targetLead && $targetCustomer) {
                    $targetLead = Lead::create([
                        'lead_code'      => Lead::generateLeadCode(),
                        'customer_id'    => $targetCustomer->id,
                        'company_name'   => $targetCustomer->company_name,
                        'pic_name'       => $targetCustomer->pic_name,
                        'pic_position'   => $targetCustomer->pic_position,
                        'phone'          => $targetCustomer->phone,
                        'email'          => $targetCustomer->email,
                        'address'        => $targetCustomer->address,
                        'industry'       => $targetCustomer->industry,
                        'location'       => $targetCustomer->location,
                        'pipeline_stage' => $requested,
                        'user_id'        => $targetCustomer->user_id ?? auth()->id(),
                    ]);
                }

                if ($targetLead) {
                    $targetLead->update(['pipeline_stage' => $requested]);
                    \App\Http\Controllers\LeadsController::syncToCustomer($targetLead->fresh());
                }
            }
        }

        // Foto: compress ke maks 500KB sebelum simpan
        unset($validated['photo'], $validated['pipeline_stage'], $validated['redirect_to']);
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $validated['photo'] = self::compressAndStore($request->file('photo'));
        }

        // Revisi #3: tautkan activity ke lead DAN customer sekaligus bila keduanya ada,
        // supaya muncul di timeline detail Lead maupun Customer.
        if ($targetLead) {
            $validated['lead_id'] = $targetLead->id;
            if (!empty($targetLead->customer_id)) {
                $validated['customer_id'] = $targetLead->customer_id;
            }
        }
        if ($targetCustomer) {
            $validated['customer_id'] = $targetCustomer->id;
        }

        Activity::create($validated);

        // Revisi #6: hormati redirect (mis. balik ke halaman customer/lead)
        if ($request->filled('redirect_to')) {
            return redirect($request->redirect_to)->with('success', 'Aktivitas berhasil disimpan.');
        }
        return redirect()->back()->with('success', 'Aktivitas berhasil disimpan.');
    }

    /**
     * Compress image menggunakan GD, simpan ke storage/app/public/activity-photos
     * Target: file size ≤ 500KB. Iterasi quality dari 85 turun ke 40.
     */
    private static function compressAndStore(\Illuminate\Http\UploadedFile $file): string
    {
        $maxBytes  = 500 * 1024; // 500 KB
        $mime      = $file->getMimeType();
        $src       = null;

        // Load image sesuai tipe
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $src = @imagecreatefromjpeg($file->getRealPath());
        } elseif ($mime === 'image/png') {
            $src = @imagecreatefrompng($file->getRealPath());
        } elseif ($mime === 'image/webp') {
            $src = @imagecreatefromwebp($file->getRealPath());
        }

        // Fallback: simpan as-is jika GD gagal
        if (!$src) {
            return $file->store('activity-photos', 'public');
        }

        // Resize jika lebar > 1200px (pertahankan rasio)
        $origW = imagesx($src);
        $origH = imagesy($src);
        $maxW  = 1200;
        if ($origW > $maxW) {
            $newH = (int) round($origH * ($maxW / $origW));
            $dst  = imagecreatetruecolor($maxW, $newH);
            // Pertahankan transparansi PNG
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        // Tentukan nama file & path
        $filename  = 'activity-photos/' . \Illuminate\Support\Str::random(40) . '.jpg';
        $storagePath = storage_path('app/public/' . $filename);

        // Pastikan direktori ada
        if (!is_dir(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0755, true);
        }

        // Iterasi quality hingga ukuran ≤ 500KB
        $quality = 85;
        do {
            ob_start();
            imagejpeg($src, null, $quality);
            $data = ob_get_clean();
            $quality -= 10;
        } while (strlen($data) > $maxBytes && $quality >= 30);

        file_put_contents($storagePath, $data);
        imagedestroy($src);

        return $filename;
    }
}
