<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class GithubSettingsRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation lors de la création.
     */
    public function storeRules(): array
    {
        return [
            'github_token' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Règles de validation lors de la mise à jour.
     */
    public function updateRules(): array
    {
        return [
            'github_token' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function commonMessages(): array
    {
        return [
            'required' => 'Le champ :attribute est requis.',
            'string' => 'Le champ :attribute doit être une chaîne de caractères.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        ];
    }
}
