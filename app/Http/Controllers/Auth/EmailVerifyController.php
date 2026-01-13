<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ResponseServer;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmailVerifyController extends Controller
{
    public function verify(Request $request, $id, $hash, $uuid)
    {
        $user = User::where('uuid', $uuid)->first() ?: User::where('id', $id)->first();

        if (! $user) {
            return ResponseServer::UserNotFound();
        }

        // Vérifier si l'email est déjà vérifié AVANT de valider la signature
        if ($user->hasVerifiedEmail()) {
            return ResponseServer::emailVerifyExist(true);
        }

        // Maintenant valider la signature seulement si l'email n'est pas vérifié
        if (! $request->hasValidSignature()) {
            return ResponseServer::unauthorizationsignatureRequest();
        }

        // Si on arrive ici, l'email n'est pas vérifié et la signature est valide
        $user->uuid = (string) Str::uuid();
        $user->markEmailAsVerified();
        $user->save();

        // event(new Verified($user));
        return ResponseServer::emailVerifySuccess(true);
    }
}
