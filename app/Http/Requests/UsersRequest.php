<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class UsersRequest extends Request
{
    public $maxFileSize;

    public function __construct()
    {
        $this->maxFileSize = config('media-library.max_file_size', 10240); // Default to 10MB if not set
    }

    /**
     * Validation rules for storing a user.
     */
    public function storeRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'roles' => 'sometimes|array_or_string',
            'roles.*' => 'sometimes|string|exists:roles,name',
            'permissions' => 'sometimes|array_or_string',
            'permissions.*' => 'sometimes|string',
            'birthday' => 'nullable|date|before:today',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'photo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png', // Consider adding image formats if appropriate: jpg,jpeg,png,gif
                'max:'.$this->maxFileSize,
            ],
        ];
    }

    // Transformez la valeur avant validation
    public function prepareForValidation()
    {
        if ($this->has('permissions') && is_string($this->permissions)) {
            $this->merge([
                'permissions' => json_decode($this->permissions, true),
            ]);
        }
    }

    /**
     * Validation rules for updating a user.
     */
    public function updateRules(): array
    {
        $userSlug = $this->route('user'); // ou la clé appropriée pour votre route

        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'roles' => 'sometimes|array_or_string',
            'roles.*' => 'sometimes|string|exists:roles,name',
            'permissions' => 'sometimes|array_or_string',
            'permissions.*' => 'sometimes|string',
            'birthday' => 'nullable|date|before:today',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userSlug, 'slug'), // 'slug' est le nom de la colonne
            ],
            'photo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png',
                'max:'.$this->maxFileSize,
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->addExtension('array_or_string', function ($attribute, $value, $parameters, $validator) {
            if (is_array($value)) {
                return true;
            }

            if (is_string($value)) {
                try {
                    $array = json_decode($value, true);

                    return is_array($array);
                } catch (\Exception $e) {
                    return false;
                }
            }

            return false;
        });
    }

    /**
     * Common validation messages for the request.
     */
    public function commonMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'array' => 'The :attribute field must be an array.',
            // 'exists' => 'The selected :attribute is invalid.',
            'string' => 'The :attribute field must be a string.',
            'max' => 'The :attribute field must not exceed :max characters.',
            'email' => 'The :attribute field must be a valid email address.',
            'unique' => 'The :attribute has already been taken.',
            'file' => 'The :attribute must be a file.',
            'mimes' => 'The :attribute must be a file of type: :values.',
            'birthday.date' => 'La date de naissance doit être une date valide.',
            'birthday.before' => 'La date de naissance doit être dans le passé.',
            'max' => [
                'numeric' => 'The :attribute may not be greater than :max.',
                'file' => 'The :attribute may not be greater than :max kilobytes.',
            ],
        ];
    }
}
