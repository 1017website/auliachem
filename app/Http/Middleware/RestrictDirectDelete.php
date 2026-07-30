<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictDirectDelete
{
    /**
     * Endpoint DELETE lama hanya boleh dipakai Administrator/Developer.
     * Role lain wajib memakai alur Permintaan Hapus.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('DELETE') && !$request->user()?->isAdmin()) {
            abort(403, 'Penghapusan data memerlukan persetujuan Administrator. Gunakan tombol Request Hapus.');
        }

        return $next($request);
    }
}
