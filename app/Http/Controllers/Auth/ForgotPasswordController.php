<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseServer;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GenerateLinkController;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\ForgotPasswordUserService;
use App\Services\ResetPasswordService;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    protected $forgotPasswordUserService;

    protected $resetPasswordService;

    public function __construct(ForgotPasswordUserService $forgotPasswordUserService, ResetPasswordService $resetPasswordService)
    {
        $this->forgotPasswordUserService = $forgotPasswordUserService;
        $this->resetPasswordService = $resetPasswordService;
    }

    public function index(ForgotPasswordRequest $request)
    {

        $email = $request->email;
        $user = $this->forgotPasswordUserService->getUser($email);
        if (! $user) {
            return ResponseServer::UserNotFound();
        }
        $userEmailExist = $this->forgotPasswordUserService->validateEmail($email);
        if ($userEmailExist) {
            // verification du mail de l'utilisateur
            if (! $user->hasVerifiedEmail()) {
                return ResponseServer::emailVerifyFailed();
            }

            $token = $this->forgotPasswordUserService->createToken($email);

            GenerateLinkController::sendResetPasswordEmailVerify('fr', $user, $token);

            return ResponseServer::ResetSuccess();
        }
    }

    public function reset(ResetPasswordRequest $request)
    {
        try {
            $validatedData = $request->validated();

            // Cas 1: Changement de mot de passe (avec current_password)
            if ($request->has('current_password')) {
                $user = auth()->user();

                // Vérifier l'ancien mot de passe
                if (! Hash::check($validatedData['current_password'], $user->password)) {
                    return ResponseServer::errorChangePassword('Le mot de passe actuel est incorrect', 422);
                }

                // Vérifier que le nouveau est différent de l'ancien
                if ($validatedData['current_password'] === $validatedData['password']) {
                    return ResponseServer::errorChangePassword('Le nouveau mot de passe doit être différent de l\'actuel', 422);
                }

                $this->resetPasswordService->updateUserPassword($user, $validatedData['password']);

                return ResponseServer::passwordResetSuccess();
            }
            // Cas 2: Reset après oubli (avec token)
            else {
                // Vérifier la validité du token
                $tokenStatus = $this->resetPasswordService->validateToken(
                    $validatedData['email'],
                    $validatedData['token']
                );

                if ($tokenStatus === 'invalid') {
                    return ResponseServer::invalidToken();
                }

                if ($tokenStatus === 'expired') {
                    return ResponseServer::expiredToken();
                }

                $user = $this->forgotPasswordUserService->getUser($validatedData['email']);

                if (! $user) {
                    return ResponseServer::UserNotFound();
                }

                if (! $user->hasVerifiedEmail()) {
                    return ResponseServer::emailVerifyFailed();
                }

                $this->resetPasswordService->updateUserPassword($user, $validatedData['password']);
                $this->resetPasswordService->deleteToken($validatedData['email']);

                return ResponseServer::passwordResetSuccess();
            }
        } catch (\Exception $e) {
            return ResponseServer::serverError($e->getMessage());
        }
    }
}
