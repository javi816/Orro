<?php

namespace App\Http\Middleware;

use App\Services\LocalAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard de las pantallas nativas (Android).
 *
 * Si el dispositivo aún no está desbloqueado, NativePHP traduce este
 * redirect en un replace() hacia la pantalla de PIN.
 */
class EnsureMobileAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! LocalAuthService::isUnlocked()) {
            return redirect('/lock');
        }

        return $next($request);
    }
}
