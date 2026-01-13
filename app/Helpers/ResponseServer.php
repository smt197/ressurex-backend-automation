<?php

namespace App\Helpers;

use App\Events\UserNotificationCountUpdated;
use App\Http\Resources\AppSettingsResource;
use App\Http\Resources\RegisterUserResource;
use App\Notifications\UserSpecificNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ResponseServer
{
    public static function registerUserResponse($user): JsonResponse
    {
        return response()->json([
            'message' => __('auth.register_success'),
            'mailmessage' => __('auth.email_verify_message'),
            'user' => new RegisterUserResource($user),
        ], 201);
    }

    public static function loginSucessOTP($user, $request, $remmeber_me, $otpId, $useId): JsonResponse
    {
        $email_verified = $request->user()->hasVerifiedEmail();
        // Récupérer la durée normale de session dans la base de données de l'utilisateur
        $SESSION_NORMAL = $user->session_normal; // Durée normale de la session (en minutes)
        $SESSION_LIFETIME = $user->session_expiration; // Durée prolongée de la session avec Remember Me (7 heures)

        // Définir la durée d'expiration du jeton en fonction de l'option Remember Me
        $timeExpiration = $request->remember_me ? $SESSION_LIFETIME : $SESSION_NORMAL;
        $user->session_user_second = $timeExpiration;
        $user->save();

        $token = $user->createToken('auth_token', ['*'], now()->addMinutes($timeExpiration))->plainTextToken;

        // Si nécessaire, définissez la durée de vie de la session (cela affecte les cookies et la session utilisateur)
        // Config::set('session.lifetime', $timeExpiration);
        // Création du cookie avec la durée d'expiration conditionnelle
        if ($remmeber_me) {
            $cookie = Cookie::make(
                'jwt',                                  // Nom du cookie
                $token,                                 // Valeur du cookie (le token)
                $SESSION_LIFETIME,                      // Durée en minutes avant l'expiration du cookie
                '/',                                    // Chemin où le cookie est accessible (par défaut à la racine)
                null,                                   // Domaine (null utilise le domaine actuel)
                config('session.secure'),               // Si le cookie doit être envoyé uniquement sur HTTPS (true/false)
                config('session.http_only')             // Si le cookie est HttpOnly (non accessible via JavaScript)
            );
        } else {
            $cookie = Cookie::make(
                'jwt',                                  // Nom du cookie
                $token,                                 // Valeur du cookie (le token)
                $SESSION_NORMAL,                      // Durée en minutes avant l'expiration du cookie
                '/',                                    // Chemin où le cookie est accessible (par défaut à la racine)
                null,                                   // Domaine (null utilise le domaine actuel)
                config('session.secure'),               // Si le cookie doit être envoyé uniquement sur HTTPS (true/false)
                config('session.http_only')             // Si le cookie est HttpOnly (non accessible via JavaScript)
            );
        }

        $otpRequired = ! is_null($otpId) && ! is_null($useId);

        // Construire la réponse conditionnellement
        $response = [
            'message' => __('auth.login_success'),
            'otp_required' => $otpRequired,
        ];

        if ($otpRequired) {
            $response['otp_id'] = $otpId;
            $response['user_id'] = $useId;
        }

        return response()->json($response, 200)->cookie($cookie);
    }

    public static function loginFailed(): JsonResponse
    {
        return response()->json([
            'message' => __('auth.login_failed'),
        ], 400);
    }

    public static function unauthorization(): JsonResponse
    {
        return response()->json([
            'message' => __('auth.unauthorized'),
        ], 400);
    }

    public static function unauthorizationsignatureRequest(): JsonResponse
    {
        return response()->json([
            'message' => __('auth.link_expired'),
            'valide' => true,

        ], 400);
    }

    public static function logoutSucess($request): JsonResponse
    {
        try {
            // Invalider la session
            $request->session()->invalidate();

            // Régénérer le token CSRF
            $request->session()->regenerateToken();
            $request->user()->tokens()->delete(); // Revoke all tokens associated with the user
            $request->user()->otp_status_auth = 0;
            $request->user()->save();
            activity()->event('logout')->causedBy($request->user())->log('logged out');

            return response()->json([
                'message' => __('auth.logout_success'),
            ], 200)->withCookie(cookie()->forget('jwt'));
        } catch (\Exception $e) {
            self::unauthorization();
        }
    }

    public static function logoutAdminSucess($request): JsonResponse
    {
        try {
            // Invalider la session
            $request->session()->invalidate();

            // Régénérer le token CSRF
            $request->session()->regenerateToken();
            $request->user()->tokens()->delete(); // Revoke all tokens associated with the user
            $request->user()->save();
            activity()->event('logout')->causedBy($request->user())->log('logged out');

            return response()->json([
                'message' => __('auth.logout_success'),
            ], 200)->withCookie(cookie()->forget('jwt'));
        } catch (\Exception $e) {
            self::unauthorization();
        }
    }

    public static function logoutSucessforUserBlocked($user): JsonRespone
    {
        try {
            $user->tokens()->delete(); // Revoke all tokens associated with the user
            $user->otp_status_auth = 0;
            $user->save();
            activity()->event('logout')->causedBy($user)->log('logged out');
        } catch (\Exception $e) {
            self::unauthorization();
        }
    }

    public static function emailVerifySuccess($email_verified): JsonResponse
    {
        return response()->json([
            'message' => __('auth.email_verify_success'),
            'email_verified' => $email_verified,
        ], 200);
    }

    public static function emailVerifyFailed($email_verified): JsonResponse
    {
        return response()->json([
            'message' => __('auth.email_verify_failed'),
            'email_verified' => $email_verified,
        ], 400);
    }

    public static function ResetSuccess(): JsonResponse
    {
        return response()->json([
            'message' => __('auth.reset_password_success'),
        ], 200);
    }

    public static function UserNotFound(): JsonResponse
    {
        return response()->json([
            'message' => __('auth.not_found'),
        ], 400);
    }

    public static function emailVerifyExist($email_verified): JsonResponse
    {
        return response()->json([
            'message' => __('auth.email_verify_exist'),
            'email_verified' => $email_verified,
        ], 200);
    }

    public static function ResenderemailVerifySuccess($email_verified): JsonResponse
    {
        return response()->json([
            'message' => __('auth.email_verify_resender_success'),
            'email_verified' => $email_verified,
        ], 200);
    }

    public static function invalidToken()
    {
        return response()->json([
            'message' => __('auth.invalid_token'),
            'success' => false,
        ], 422);
    }

    public static function expiredToken()
    {
        return response()->json([
            'message' => __('auth.expired_token'),
            'success' => false,
        ], 422);
    }

    public static function passwordResetSuccess()
    {
        return response()->json([
            'message' => __('auth.password_reset_success'),
            'success' => true,
        ], 200);
    }

    /**
     * Réponse pour un OTP non trouvé.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function OtpNotFound()
    {
        return response()->json([
            'message' => __('auth.otp_not_found'),
            'success' => false,
        ], 404); // Code HTTP 404 : Not Found
    }

    /**
     * Réponse pour un nombre excessif de tentatives OTP.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function TooManyOtpAttempts()
    {
        return response()->json([
            'message' => __('auth.otp_too_many_attempts'),
            'success' => false,
            'attempts_exceeded' => true, // Indique que le nombre maximal de tentatives a été atteint
        ], 429); // Code HTTP 429 : Too Many Requests
    }

    /**
     * Réponse pour un OTP valide.
     *
     * @param  bool  $userStatus  Le statut de l'utilisateur (true ou false).
     * @return \Illuminate\Http\JsonResponse
     */
    public static function OtpSuccess($userStatus)
    {
        return response()->json([
            'message' => __('auth.otp_verified'),
            'success' => true,
            'user_status' => $userStatus, // Statut de l'utilisateur (par exemple, actif ou inactif)
        ], 200); // Code HTTP 200 : OK
    }

    /**
     * Réponse pour un OTP invalide.
     *
     * @param  int  $remainingAttempts  Le nombre de tentatives restantes.
     * @return \Illuminate\Http\JsonResponse
     */
    public static function InvalidOtp($remainingAttempts)
    {
        return response()->json([
            'message' => __('auth.otp_invalid_attempts', ['remainingAttempts' => $remainingAttempts]),
            'success' => false,
            'remaining_attempts' => $remainingAttempts,
        ], 401);
    }

    /**
     * Réponse pour une limitation de taux (throttling).
     *
     * @param  string  $message  Le message d'erreur.
     * @return \Illuminate\Http\JsonResponse
     */
    public static function OtpThrottleError($message)
    {
        return response()->json([
            'message' => $message,
            'success' => false,
            'throttle_error' => true, // Indique que l'erreur est due à une limitation de taux
        ], 429); // Code HTTP 429 : Too Many Requests

    }

    public static function status()
    {
        $user = Auth::user();

        return response()->json([
            'status' => Auth::check(),
            'user' => Auth::user() ? $user->getUserInfo() : null,
            'otp_required' => $user->getOtpEnabledAttribute(),
            'otp_id' => $user->getUserOtpId(),
            'status_otp' => $user->getUserOtpStatus(),
            'otp_status_auth' => $user->getOtpEnabledAuth(),
            'menu' => $user->getMenu(),
        ]);
    }

    /**
     * Réponse pour un OTP envoyé avec succès.
     *
     * @param  int  $otpId  L'identifiant de l'OTP généré.
     * @return \Illuminate\Http\JsonResponse
     */
    public static function OtpSentSuccess($otpId)
    {
        return response()->json([
            'message' => __('auth.otp_sent'),
            'otp_id' => $otpId,
            'success' => true,
        ], 200); // Code HTTP 200 : OK
    }

    /**
     * Réponse pour une erreur serveur (500).
     *
     * @param  string  $message  Le message d'erreur (optionnel).
     * @return \Illuminate\Http\JsonResponse
     */
    public static function serverError()
    {
        return response()->json([
            'message' => __('auth.server_error'),
            'success' => false,
            'error_code' => 'SERVER_ERROR', // Code d'erreur personnalisé (optionnel)
        ], 500); // Code HTTP 500 : Internal Server Error
    }

    public static function successEnabled(string $message = '')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    public static function errorOTP(string $message, int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function handleMaxPaginationLimitExceededException($limit)
    {
        return response()->json([
            'success' => false,
            'message' => __('pagination.limit_exceeded', ['limit' => $limit]),
        ], 400); // Vous pouvez ajouter un code HTTP approprié (400 Bad Request par exemple)
    }

    public static function notFoundHttpException($e)
    {
        return response()->json([
            'success' => false,
            'message' => __('query.no_results'),
            'exception' => $e->getMessage(),

        ]);
    }

    /**
     * Réponse pour des paramètres mis à jour avec succès
     *
     * @param  string  $siteName
     * @param  string|null  $siteDescription
     * @param  string|null  $siteLogoPath
     * @param  bool  $siteActive
     */
    public static function settingsUpdatedSuccess($settings): JsonResponse
    {
        $user = Auth::user();
        $message_content = __('settings.update_success');
        $data = [
            'setting' => new AppSettingsResource($settings),
            'message' => $message_content,
            'notification_type' => 'setting',
        ];
        $user->notify(new UserSpecificNotification($user, $data));
        event(new UserNotificationCountUpdated($user, $user->unreadNotifications()->count()));

        return response()->json([
            'message' => $message_content,
            'data' => new AppSettingsResource($settings),
        ]);
    }

    /**
     * Réponse pour la récupération des paramètres du site avec message
     *
     * @param  string  $siteName
     * @param  string|null  $siteDescription
     * @param  string|null  $siteLogoPath
     * @param  bool  $siteActive
     */
    public static function getSettingsResponse($settings): JsonResponse
    {
        return response()->json(new AppSettingsResource($settings), 200);
    }

    public static function errorChangePassword(string $message, int $statusCode = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'status' => $statusCode,
            'timestamp' => now()->toDateTimeString(),
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
