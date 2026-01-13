<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class CategoryRequest extends Request
{
    /**
     * Validation rules for storing a category.
     */
    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:categories,name'],
            'position_reference_id' => ['nullable', 'integer', 'exists:categories,id'],
            'position_type' => ['nullable', 'string', 'in:before,after'],
        ];
    }

    /**
     * Validation rules for updating a category.
     */
    public function updateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'position_reference_id' => ['nullable', 'integer', 'exists:categories,id'],
            'position_type' => ['nullable', 'string', 'in:before,after'],
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
            'unique' => 'The :attribute has already been taken.',
        ];
    }
}
