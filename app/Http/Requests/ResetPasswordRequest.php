<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Règles de base communes aux deux cas
        $rules = [
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];

        // Cas 1: Réinitialisation après oubli (avec token)
        if ($this->isPasswordReset()) {
            $rules['token'] = 'required|string';
            $rules['email'] = 'required|email|exists:users,email';
            $rules['expires'] = 'required';
        }
        // Cas 2: Changement de mot de passe (avec current_password)
        else {
            $rules['current_password'] = 'required|string';
        }

        return $rules;
    }

    /**
     * Vérifie si c'est une demande de reset password (oublié)
     */
    protected function isPasswordReset(): bool
    {
        return $this->has('token') && $this->has('email');
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return $this->commonMessages();
    }

    /**
     * Common validation messages for the request.
     */
    public function commonMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'email' => 'The :attribute field must be a valid email address.',
            'exists' => 'The selected :attribute is invalid.',
            'min' => 'The :attribute field must be at least :min characters.',
            'confirmed' => 'The :attribute confirmation does not match.',

            // Messages spécifiques au reset password
            'current_password.required' => 'Le mot de passe actuel est requis',
            'password.required' => 'Le nouveau mot de passe est requis',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins :min caractères',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
            'email.exists' => 'Aucun utilisateur trouvé avec cette adresse email',
            'token.required' => 'Le token de réinitialisation est requis',
        ];
    }
}
