<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class CountryRequest extends Request
{
    public function commonRules(): array
    {
        return [
            'country_code' => 'required|string|max:10|unique:countries,country_code',
            'country_name' => 'required|string|max:255',
            'image_url' => 'required|string|max:255',
            'dial_code' => 'required|string|max:255',
        ];
    }

    public function updateRules(): array
    {
        return [
            'country_code' => 'sometimes|string|max:10|unique:countries,country_code,'.$this->country,
            'country_name' => 'sometimes|string|max:255',
            'image_url' => 'sometimes|string|max:255',
            'dial_code' => 'sometimes|string|max:255',
        ];
    }
}
