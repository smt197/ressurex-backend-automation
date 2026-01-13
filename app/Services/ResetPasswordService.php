<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordService
{
    public function validateToken($email, $token)
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->orderByDesc('created_at') // Récupère le plus récent
            ->first(); // Prend le premier (le plus récent)

        if (! $record || $record->token !== $token) {
            return 'invalid';
        }
        $time_expiration = config('app.time_expiration_token');
        if (now()->parse($record->created_at)->addHour($time_expiration)->isPast()) {
            return 'expired';
        }

        return 'valid';
    }

    public function updateUserPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->save();
    }

    public function deleteToken($email)
    {
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();
    }
}
