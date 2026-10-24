<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();
        if (!$user || $user->role !== 'admin' || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The supplied credentials are invalid.']]);
        }

        $token = $user->createToken('kfs-admin-dashboard', ['admin:read', 'admin:write'], now()->addHours(8))->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Signed out.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role]]);
    }
}
