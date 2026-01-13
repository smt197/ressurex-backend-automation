<?php

namespace App\Http\Controllers;

use App\Notifications\sendEmailNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class GenerateLinkController
{
    /**
     * Génère une URL backend signée.
     *
     * @param  string  $routeName  Le nom de la route backend.
     * @param  array  $params  Les paramètres de la route.
     * @param  Carbon  $expires  La date d'expiration de l'URL.
     * @return string L'URL backend signée.
     */
    public static function generateBackendUrl(string $routeName, array $params, Carbon $expires): string
    {
        return URL::temporarySignedRoute($routeName, $expires, $params);
    }

    /**
     * Transforme une URL backend en URL frontend.
     *
     * @param  string  $backendUrl  L'URL backend.
     * @return string L'URL frontend.
     */
    public static function transformToFrontendUrl(string $backendUrl): string
    {
        $backendBaseUrl = Config('app.url_api');
        $frontendBaseUrl = Config('app.url_frontend');

        // Remplace la base de l'URL backend par la base de l'URL frontend
        return str_replace($backendBaseUrl, $frontendBaseUrl, $backendUrl);
    }

    /**
     * Génère une URL frontend pour la vérification d'email.
     *
     * @param  int  $id  L'ID de l'utilisateur.
     * @param  string  $hash  Le hash de vérification.
     * @param  Carbon  $expires  La date d'expiration.
     * @param  string  $email  L'email de l'utilisateur.
     * @return string L'URL frontend.
     */
    public static function generateEmailVerificationFrontendUrl(int $id, string $hash, $uuid, Carbon $expires): string
    {
        $backendUrl = self::generateBackendUrl(
            'email.verify',
            [
                'id' => $id,
                'hash' => $hash,
                'uuid' => $uuid,
            ],
            $expires
        );

        return self::transformToFrontendUrl($backendUrl);
    }

    /**
     * Génère une URL frontend pour la réinitialisation du mot de passe.
     *
     * @param  int  $id  L'ID de l'utilisateur.
     * @param  string  $email  L'email de l'utilisateur.
     * @param  string  $token  Le token de réinitialisation.
     * @param  string  $hash  Le hash de vérification.
     * @param  Carbon  $expires  La date d'expiration.
     * @return string L'URL frontend.
     */
    public static function generatePasswordResetFrontendUrl(int $id, string $email, string $token, string $hash, Carbon $expires): string
    {
        $backendUrl = self::generateBackendUrl('password-reset', [
            'id' => $id,
            'email' => $email,
            'token' => $token,
            'hash' => $hash,
        ], $expires);

        return self::transformToFrontendUrl($backendUrl);
    }

    /**
     * Envoie un email de vérification.
     *
     * @param  mixed  $user  L'utilisateur à qui envoyer l'email.
     * @param  string  $lang  La langue de l'email.
     * @param  string|null  $password  Le mot de passe (optionnel).
     */
    public static function sendEmailVerify($user, string $lang, ?string $password = null): void
    {
        $id = time().'_'.uniqid();
        $hash = sha1($id);
        $uuid = (string) Str::uuid();
        $expires = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 180));

        $verificationUrl = self::generateEmailVerificationFrontendUrl($user->id, $hash, $uuid, $expires);
        $type = 'verify_email';
        $user->notify(new sendEmailNotification($type, $user, $verificationUrl, $lang));
    }

    /**
     * Envoie un email de vérification pour reinitialiser le mot de passe.
     *
     * @param  mixed  $user  L'utilisateur à qui envoyer l'email.
     * @param  string  $lang  La langue de l'email.
     * @param  string|null  $password  Le mot de passe (optionnel).
     */
    public static function sendResetPasswordEmailVerify($lang, $user, $token): void
    {
        $id = (int) (microtime(true) * 1000); // Exemple : 1738539135456
        $hash = sha1($id);
        $expires = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 180));

        $verificationUrl = self::generatePasswordResetFrontendUrl($id, $user->email, $token, $hash, $expires);
        $type = 'reset_password';
        $user->notify(new sendEmailNotification($type, $user, $verificationUrl, $lang));
    }
}
