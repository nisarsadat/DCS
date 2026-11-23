<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function enable(Request $request)
    {
        $user = $request->user();
        $secret = Totp::generateSecret();
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => null,
        ])->save();

        return [
            'secret' => $secret,
            'otpauth' => Totp::provisioningUri($secret, $user->email),
        ];
    }

    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        $secret = $user->two_factor_secret ? decrypt($user->two_factor_secret) : null;

        if (! $secret || ! Totp::verify($secret, $data['code'])) {
            return response()->json(['message' => 'The authentication code is not valid.'], 422);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return ['enabled' => true];
    }

    public function disable(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);
        $user = $request->user();
        if (! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Password is incorrect.'], 422);
        }
        $secret = $user->two_factor_secret ? decrypt($user->two_factor_secret) : null;
        if (! $secret || ! Totp::verify($secret, $data['code'])) {
            return response()->json(['message' => 'The authentication code is not valid.'], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return ['enabled' => false];
    }

    public function challenge(Request $request)
    {
        $data = $request->validate([
            'challenge' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $userId = Cache::pull('2fa-challenge:'.$data['challenge']);
        $user = $userId ? User::query()->find($userId) : null;
        $secret = $user?->two_factor_secret ? decrypt($user->two_factor_secret) : null;

        if (! $user || ! $secret || ! Totp::verify($secret, $data['code'])) {
            return response()->json(['message' => 'The authentication code is not valid.'], 422);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => trim($user->first_name.' '.$user->last_name),
                'email' => $user->email,
                'photo' => \App\Support\Media::url($user->profile_picture),
            ],
        ];
    }
}
