<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheck
{
    /**
     * Cek apakah user punya role yang diizinkan.
     * Contoh: ->middleware('role:Admin,Sales Manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->status !== 'Active') {
            abort(403, 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        $hasRole = empty($roles)
            || in_array($user->role, $roles, true)
            || ($user->isDeveloper() && in_array(User::ROLE_ADMIN, $roles, true));

        if (!$hasRole) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
