<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class GreffeRequest extends Request
{
    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes'],
        ];
    }

    public function commonMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'string' => 'The :attribute field must be a string.',
            'numeric' => 'The :attribute field must be a number.',
            'boolean' => 'The :attribute field must be true or false.',
            'date' => 'The :attribute field must be a valid date.',
            'max' => 'The :attribute field must not exceed :max characters.',
        ];
    }
}
