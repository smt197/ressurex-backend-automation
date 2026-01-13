<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Str;

class ForgotPasswordUserService
{
    public function validateEmail($email)
    {
        return (bool) User::where('email', $email)->first();
    }

    public function getUser($email)
    {
        $user = User::where('email', $email)->first();

        return $user;
    }

    public function createToken($email)
    {
        $oldToken = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($oldToken) {
            return $oldToken->token;
        }

        $token = Str::random(60);
        $this->saveToken($token, $email);

        return $token;
    }

    public function saveToken($token, $email)
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);
    }

    public function isTokenExpired($email, $token)
    {
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->first();

        if (! $tokenData) {
            return true; // Aucun token trouvé, considéré comme expiré
        }

        return Carbon::parse($tokenData->created_at)->addHours(3)->isPast();
    }
}
