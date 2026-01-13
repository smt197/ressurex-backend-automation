<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L’autorisation spécifique peut être gérée dans le contrôleur
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.required' => 'Le champ "is_active" est obligatoire.',
            'is_active.boolean' => 'Le champ "is_active" doit être un booléen.',
            'message.string' => 'Le message doit être une chaîne de caractères.',
            'message.max' => 'Le message ne peut pas dépasser 500 caractères.',
        ];
    }
}
