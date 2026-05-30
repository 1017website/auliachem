<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Lead;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $role   = $request->get('role');
        $status = $request->get('status');

        // User Non-Active tidak ditampilkan lagi di list user.
        $query = User::where('status', 'Active')->withCount([
            'leads',
            'leads as deals_won' => fn($q) => $q->where('pipeline_stage', 'Won'),
        ]);

        if ($search) $query->where(fn($q) => $q->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%"));
        if ($role)   $query->where('role', $role);
        // Filter status tetap diterima agar query lama tidak error, tetapi list selalu hanya Active.

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        $userIds  = $users->pluck('id');
        $revenues = Lead::whereIn('user_id', $userIds)->where('pipeline_stage', 'Won')
            ->selectRaw('user_id, SUM(potensi_revenue) as total')
            ->groupBy('user_id')->pluck('total', 'user_id');

        $totalUsers   = User::where('status', 'Active')->count();
        $activeUsers  = $totalUsers;
        $totalSales   = User::where('status', 'Active')->where('role', 'Sales Executive')->count();
        $totalManager = User::where('status', 'Active')->where('role', 'Sales Manager')->count();
        $roles        = User::where('status', 'Active')->distinct()->whereNotNull('role')->pluck('role')->sort()->values();

        return view('users.index', compact(
            'users', 'revenues', 'totalUsers', 'activeUsers', 'totalSales', 'totalManager',
            'roles', 'search', 'role', 'status'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|unique:users,email',
            'password'          => 'required|string|min:6|confirmed',
            'phone'             => 'nullable|string|max:20',
            'position'          => 'nullable|string|max:100',
            'role'              => 'required|string|max:100',
            'status'            => 'required|in:Active,Non-Active',
            'target'            => 'nullable|numeric|min:0',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);
        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan. Akun siap digunakan untuk login.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'role'     => 'sometimes|string|max:100',
            'status'   => 'sometimes|in:Active,Non-Active',
            'target'   => 'nullable|numeric|min:0',
        ]);

        if ($request->filled('new_password')) {
            $request->validate(['new_password' => 'min:6|confirmed']);
            $validated['password'] = Hash::make($request->new_password);
        }

        $willBeNonActive = ($validated['status'] ?? $user->status) === 'Non-Active' && $user->status !== 'Non-Active';

        if ($willBeNonActive && $user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        DB::transaction(function () use ($user, $validated, $willBeNonActive) {
            if ($willBeNonActive) {
                $administrator = $this->getAdministratorForTransfer($user->id);
                Customer::where('user_id', $user->id)->update(['user_id' => $administrator->id]);
                Lead::where('user_id', $user->id)->update(['user_id' => $administrator->id]);
            }

            $user->update($validated);
        });

        $message = $willBeNonActive
            ? 'User diupdate dan seluruh Customer/Leads miliknya dipindahkan ke Administrator.'
            : 'User diupdate.';

        return redirect()->back()->with('success', $message);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }
        DB::transaction(function () use ($user) {
            $administrator = $this->getAdministratorForTransfer($user->id);
            Customer::where('user_id', $user->id)->update(['user_id' => $administrator->id]);
            Lead::where('user_id', $user->id)->update(['user_id' => $administrator->id]);
            $user->update(['status' => 'Non-Active']);
        });

        return redirect()->route('users.index')->with('success', 'User ' . $user->name . ' dinonaktifkan. Customer dan Leads dipindahkan ke Administrator.');
    }

    private function getAdministratorForTransfer(int $excludedUserId): User
    {
        $administrator = User::where('status', 'Active')
            ->where('role', 'Admin')
            ->where('id', '!=', $excludedUserId)
            ->orderByRaw("CASE WHEN name = 'Administrator' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if (!$administrator) {
            abort(422, 'Tidak ada Administrator aktif untuk menerima transfer Customer dan Leads.');
        }

        return $administrator;
    }
}