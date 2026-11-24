<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Login information is invalid!'], 401);
        }

        if ($user->two_factor_confirmed_at) {
            $challenge = Str::random(64);
            Cache::put('2fa-challenge:'.$challenge, $user->id, now()->addMinutes(5));

            return response()->json([
                'two_factor' => true,
                'challenge' => $challenge,
            ]);
        }

        $accessToken = $user->createToken('api_token')->plainTextToken;

       
        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'user' => [
                "id" => $user->id,
                "name" => trim($user->first_name . ' ' . $user->last_name),
                "email"=>$user->email,
                "photo" => \App\Support\Media::url($user->profile_picture)

            ],
                       
        ]);
        
    }

    public function logout(Request $request)
    {
        // Delete all tokens for the authenticated user
        $request->user()->tokens()->delete();

        // $user():currently authenticated user from the request.
        return response()->json(['message' => 'The user logged out']);
    }

    // Fixed password reset methods
public function sendResetLink(Request $request)
{
    $request->validate(['email' => 'required|email']);

    Password::sendResetLink($request->only('email'));

    return response()->json([
        'message' => 'If that email is registered, a reset link has been sent.',
        'status' => 'success',
    ]);
}


public function resetPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json([
            'message' => __($status),
            'status' => 'success'
        ]);
    }

    return response()->json([
        'message' => __($status),
        'status' => 'error'
    ], 400);
}

    



    
}
