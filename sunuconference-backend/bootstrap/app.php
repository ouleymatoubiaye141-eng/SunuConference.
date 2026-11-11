<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureChairConferenceIsValidated;
use App\Http\Middleware\EnsureUserDoesNotNeedToChangePassword;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'password.changed' => EnsureUserDoesNotNeedToChangePassword::class,
            'role' => EnsureUserHasRole::class,
            'chair.validated' => EnsureChairConferenceIsValidated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AccessDeniedHttpException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => "Cette action n'est pas autorisée.",
                ], 403);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => "Cette action n'est pas autorisée.",
                ], 403);
            }
        });

        $exceptions->render(function (ApiException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->statusCode());
        });

        $exceptions->render(function (ValidationException $exception) {
            return response()->json([
                'message' => 'Les données envoyées sont invalides.',
                'errors' => $exception->errors(),
            ], 422);
        });
    })->create();
