<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseServer;
use App\Http\Controllers\Actions\SendUserOtp;
use App\Http\Controllers\Controller;
use BenBjurstrom\Otpz\Exceptions\OtpThrottleException;
use BenBjurstrom\Otpz\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function sendOtp(Request $request)
    {
        // Validation des données
        $request->validate([
            'otp_id' => 'required|exists:otps,id', // Recevoir l'identifiant de l'OTP
        ]);

        // Récupérer l'identifiant de l'OTP de la requête
        $otpId = $request->input('otp_id');

        // Rechercher l'OTP correspondant à l'identifiant
        $otp = Otp::find($otpId);

        // Vérifier si l'OTP existe
        if (! $otp) {
            throw ValidationException::withMessages([
                'otp_id' => __('auth.otp_invalid'),
            ]);
        }

        // Réinitialiser le compteur de tentatives
        $otp->attempts = 0;
        $otp->save();

        // Récupérer l'e-mail de l'utilisateur associé à l'OTP
        $email = $otp->user->email;

        try {
            // Envoyer un nouvel OTP à l'e-mail de l'utilisateur
            $newOtp = (new SendUserOtp)->handle($email);

            // Retourner une réponse de succès standardisée
            return ResponseServer::OtpSentSuccess($newOtp->id);
        } catch (OtpThrottleException $e) {
            // Capturer l'exception et renvoyer une réponse standardisée
            return ResponseServer::OtpThrottleError($e->getMessage());
        } catch (\Exception $e) {
            // Gérer les autres erreurs
            return ResponseServer::serverError();
        }
    }

    public function verifyOtp(Request $request)
    {
        // Validation des données
        $request->validate([
            'otp_id' => 'required|exists:otps,id',
            'code' => 'required|string',
        ]);

        // Récupérer l'OTP
        $otp = Otp::find($request->input('otp_id'));

        // Vérifier que l'OTP existe
        if (! $otp) {
            return ResponseServer::OtpNotFound();
        }

        // Vérifier le nombre de tentatives
        if ($otp->attempts >= 3) {
            return ResponseServer::TooManyOtpAttempts();
        }

        // Vérifier le code OTP
        if (Hash::check((string) $request->input('code'), $otp->code)) {
            // Réinitialiser le compteur de tentatives
            $otp->attempts = 0;
            $otp->status = 1;
            $otp->save();

            // Retourner une réponse de succès
            $status_otp = $otp->user->status ? true : false;

            return ResponseServer::OtpSuccess($status_otp);
        }

        // Incrémenter le compteur de tentatives
        $otp->increment('attempts');

        // Retourner une réponse d'échec
        return ResponseServer::InvalidOtp(2 - $otp->attempts);
    }

    public function toggleOtp(Request $request)
    {
        $request->validate(['otp_enabled' => 'required|boolean']);

        try {
            $user = Auth::user();
            $user->toggleOtp($request->otp_enabled);
            $user->otp_status_auth = 1;
            $user->save();
            $status = $request->otp_enabled ? 'activée' : 'désactivée';
            $message = __('auth.otp_toggle', ['status' => $status]);

            return ResponseServer::successEnabled($message);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification du statut OTP: '.$e->getMessage());

            return ResponseServer::errorOTP(
                __('auth.error'),
                500
            );
        }
    }
}
