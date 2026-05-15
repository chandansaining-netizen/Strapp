<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'gender'   => 'nullable|in:male,female,other',
        ]);

        $user = User::create([
            'name'         => $request->name,
            'display_name' => $request->name,
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
            'gender'       => $request->gender,
            'role'         => 'user',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = JWTAuth::user();
        $user->update(['is_online' => true, 'last_active' => now()]);

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $user->update(['is_online' => false]);
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {}

        return response()->json(['message' => 'Logged out']);
    }

    public function me()
    {
        return response()->json(JWTAuth::parseToken()->authenticate());
    }
}
