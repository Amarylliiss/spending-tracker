<?php

namespace App\Http\Controllers\Api;

use App\Application\Onboarding\CreateStarterCategories;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, CreateStarterCategories $starter): JsonResponse
    {
        $user = DB::transaction(function () use ($request, $starter) {
            $user = User::query()->create($request->only('name', 'email', 'password'));
            $starter->handle($user);

            return $user;
        });

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => 'Nieprawidłowy e-mail lub hasło.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }
}
