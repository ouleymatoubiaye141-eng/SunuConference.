<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->select(['id', 'name', 'email', 'affiliation', 'role', 'created_at']);

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->latest()->get(),
        ]);
    }
}
