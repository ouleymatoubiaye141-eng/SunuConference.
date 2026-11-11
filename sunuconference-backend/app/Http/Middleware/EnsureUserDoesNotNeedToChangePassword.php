<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserDoesNotNeedToChangePassword
{
    private const ALLOWED_ROUTE_NAMES = [
        'auth.logout',
        'auth.me',
        'auth.change-password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED_ROUTE_NAMES, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Vous devez changer votre mot de passe avant de continuer.',
        ], Response::HTTP_FORBIDDEN);
    }
}
