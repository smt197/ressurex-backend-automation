<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class LanguageRequest extends Request
{
    public function storeRules(): array
    {
        return [
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:255',

        ];
    }

    public function updateRules(): array
    {

        return [
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Common validation messages for the request.
     */
    public function commonMessages(): array
    {
        return [
            'code' => 'The :attribute field is required.',
            'name' => 'The :attribute field must be a string.',
        ];
    }
}
