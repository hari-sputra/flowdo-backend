<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $this->initializeDefaultTags($user);

        Auth::login($user);

        return response()->json(new UserResource($user), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json(new UserResource(Auth::user()), 200);
    }

    public function logout(Request $request): \Illuminate\Http\Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()), 200);
    }

    private function initializeDefaultTags(User $user): void
    {
        $defaultTags = [
            ['name' => 'Work', 'color' => '#8764FF', 'is_default' => true],
            ['name' => 'Personal', 'color' => '#FF7D53', 'is_default' => true],
            ['name' => 'Study', 'color' => '#2555FF', 'is_default' => true],
            ['name' => 'Fitness', 'color' => '#F478B8', 'is_default' => true],
        ];

        foreach ($defaultTags as $tag) {
            $user->tags()->create($tag);
        }
    }
}
