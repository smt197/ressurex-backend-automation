<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string'],
            'site_subtitle' => ['nullable', 'string'],
            'site_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif'],
            'site_active' => ['required'],
            'site_web' => ['required', 'string', 'max:20'],
        ];
    }
}
