<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Compte cree avec succes.',
            'data' => $this->authService->register($request->validated()),
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Connexion reussie.',
            'data' => $this->authService->login($request->validated()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Deconnexion reussie.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Profil mis \u{00E0} jour avec succ\u{00E8}s.',
            'data' => $this->authService->updateProfile($request->user(), $request->validated()),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('new_password'),
        );

        return response()->json([
            'message' => "Mot de passe modifi\u{00E9} avec succ\u{00E8}s.",
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated('email'));

        return response()->json([
            'message' => 'Si cette adresse existe, un e-mail de r\u{00E9}initialisation vient de lui \u{00EA}tre envoy\u{00E9}.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return response()->json([
            'message' => 'Mot de passe r\u{00E9}initialis\u{00E9} avec succ\u{00E8}s.',
        ]);
    }
}
