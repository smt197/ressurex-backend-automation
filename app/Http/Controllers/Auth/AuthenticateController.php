<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseServer;
use App\Http\Controllers\Actions\SendUserOtp;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GenerateLinkController;
use App\Http\Requests\LoginUserRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateController extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function login(LoginUserRequest $request)
    {
        $credentials = $request->only(['email', 'password']);

        $remmeber_me = $request->remmeber_me ? true : false;
        if (Auth::attempt($credentials)) {
            $userId = Auth::id();
            $user = User::find($userId);

            activity()->event('login')->causedBy($user)->log('logged in');

            if ($request->user()->hasVerifiedEmail()) {
                if ($user->otp_enabled) {
                    // Générer un OTP pour l'utilisateur
                    $otp = (new SendUserOtp)->handle($user->email, $remmeber_me);

                    $request->session()->regenerateToken();

                    return ResponseServer::loginSucessOTP($user, $request, $remmeber_me, $otp->id, $user->id);
                }
                // Régénérer le token CSRF
                $request->session()->regenerateToken();
                $this->associate($user->id);

                return ResponseServer::loginSucessOTP($user, $request, $remmeber_me, null, null);
            } else {
                return ResponseServer::emailVerifyFailed(false);
            }
        }

        return ResponseServer::loginFailed();
    }

    public function associate($userId)
    {

        /** @var User $user */
        $user = User::findOrFail($userId);
        $getAppLanguage = app()->getLocale();
        $newLanguageId = Language::where('code', $getAppLanguage)->value('id');

        // Check for existing association
        if ($user->languages()->where('languages.id', $newLanguageId)->exists()) {
            // $userlanguage = new LanguageResource($user->languages()->find($newLanguageId));
            $userlanguage = new LanguageResource($user->languages()->first());

            // Return without changes if already associated
            return response()->json([
                'message' => __('language.already_associated'),
                'language' => $userlanguage->code,
            ], 200);
        }

        // Dissociate all existing languages
        $user->languages()->detach();

        // Associate the new language
        $user->languages()->attach($newLanguageId, [
            'is_preferred' => true, // Set the new language as preferred
        ]);

        // Return the newly associated language
        return response()->json([
            'message' => __('language.message'),
        ], 200);
    }

    /**
     * Logout a user connected.
     */
    public function logout(Request $request)
    {
        return ResponseServer::logoutSucess($request);
    }

    public function status()
    {
        return ResponseServer::status();
    }

    public function resendVerificationEmail(Request $request)
    {
        $email = $request->only(['email']);
        $user = User::where('email', $email)->first();
        if ($user->hasVerifiedEmail()) {

            return ResponseServer::emailVerifySuccess(true);
        }

        GenerateLinkController::sendEmailVerify($user, 'fr');

        return ResponseServer::ResenderemailVerifySuccess(true);
    }
}
