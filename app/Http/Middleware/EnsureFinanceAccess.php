<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanceAccess
{
    /**
     * Handle an incoming request.
     * Batasi akses route hanya untuk jabatan: Finance, Admin Finance, dan Cashier.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->canAccessFinanceBackoffice()) {
            abort(403, 'Akses ditolak. Menu ini hanya dapat diakses oleh jabatan Finance, Admin Finance, dan Cashier.');
        }

        return $next($request);
    }
}

