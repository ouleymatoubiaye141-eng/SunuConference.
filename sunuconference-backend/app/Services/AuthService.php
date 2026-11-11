<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpFoundation\Response;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create($data);
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new ApiException('Identifiants incorrects.', Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new ApiException('Le mot de passe actuel est incorrect.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
        ])->save();
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh();
    }

    public function forgotPassword(string $email): void
    {
        // Toujours répondre avec succès, que l'e-mail existe ou non, pour ne pas
        // divulguer quels comptes existent.
        Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $data): void
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new ApiException('Ce lien de réinitialisation est invalide ou a expiré.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
