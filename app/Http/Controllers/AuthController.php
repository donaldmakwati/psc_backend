<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('staff_id', 'password');

        $request->validate([
            'staff_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('staff_id', $credentials['staff_id'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Invalid credentials.'], 401);
        }

        // Get the user's role
        $allowedRoles = ['admin', 'staff', 'operator'];
        $userRole = $user->roles()->pluck('name')->first();

        if (!in_array($userRole, $allowedRoles)) {
            return response()->json(['error' => 'Unauthorized role.'], 403);
        }

        $customClaims = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'email' => $user->email,
                'staff_id' => $user->staff_id,
                'role' => $userRole,
            ]
        ];
        
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        return response()->json([
            'message' => 'Login successful',
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken());
            return response()->json(['message' => 'Logout successful']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout.'], 500);
        }
    }

    public function me()
    {
        return response()->json(auth()->user());
    }
}
