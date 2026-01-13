<?php

namespace App\Services;

use App\Http\Controllers\GenerateLinkController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterUserService
{
    public function createUser(array $data): User
    {
        $uuid = (string) Str::uuid();
        $user = User::create([
            'uuid' => $uuid,
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'email' => $data['email'],
            'confirmed' => $data['confirmed'],
            'password' => Hash::make($data['password']),
        ]);

        GenerateLinkController::sendEmailVerify($user, 'fr');
        $this->setDefaultLanguages($user);
        $user->assignRole('user');

        return $user;
    }

    public function setDefaultLanguages($user): void
    {
        $getAppLanguage = app()->getLocale();
        $newLanguageId = DB::table('languages')->where('code', $getAppLanguage)->value('id');
        $user->languages()->attach($newLanguageId, [
            'is_preferred' => true, // Set the new language as preferred
        ]);
    }
}
