<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class MenuRequest extends Request
{
    public function authorize(): bool
    {
        // Vous pouvez mettre votre logique de permission ici, par exemple :
        // return $this->user()->can('manage menus');
        return true;
    }

    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:255'],
            'route' => ['required', 'string', 'max:255'],
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'string', Rule::in(['admin', 'user', 'system', 'manager'])],
            'disable' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'array'],
            'description.fr' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description.en' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description.pt' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'icon' => ['sometimes', 'required', 'string', 'max:255'],
            'color' => ['sometimes', 'required', 'string', 'max:255'],
            'route' => ['sometimes', 'required', 'string', 'max:255'],
            'roles' => ['sometimes', 'required', 'array'],
            'roles.*' => ['required', 'string', Rule::in(['admin', 'user', 'system', 'manager'])],
            'disable' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'array'],
            'description.fr' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description.en' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description.pt' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
