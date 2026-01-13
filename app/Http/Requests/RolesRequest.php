<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class RolesRequest extends Request
{
    /**
     * Validation rules for storing a user.
     */
    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Validation rules for updating a user.
     */
    public function updateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
        ];

    }

    /**
     * Common validation messages for the request.
     */
    public function commonMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field must not exceed :max characters.',
        ];
    }
}
