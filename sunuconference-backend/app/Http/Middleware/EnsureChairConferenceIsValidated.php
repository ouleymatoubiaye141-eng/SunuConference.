<?php

namespace App\Http\Middleware;

use App\Models\Conference;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChairConferenceIsValidated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== User::ROLE_CHAIR) {
            return $next($request);
        }

        $conference = $request->route('conference');

        if (! $conference instanceof Conference) {
            $conference = $user->chairedConferences()->latest()->first();
        } elseif ($conference->chair_id !== $user->id) {
            return $next($request);
        }

        if (! $conference) {
            return response()->json([
                'message' => "Vous devez créer une conférence avant d'accéder à cet espace.",
            ], Response::HTTP_FORBIDDEN);
        }

        if ($conference->status === Conference::STATUS_PENDING) {
            return response()->json([
                'message' => "Votre conférence est en attente de validation par l'administrateur.",
                'conference' => $conference,
            ], Response::HTTP_FORBIDDEN);
        }

        if ($conference->status === Conference::STATUS_REJECTED) {
            return response()->json([
                'message' => 'Votre conférence a été rejetée. Vous pouvez la modifier puis la resoumettre.',
                'rejection_reason' => $conference->rejection_reason,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
