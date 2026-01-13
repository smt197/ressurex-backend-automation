<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class ModuleManagerRequest extends Request
{
    /**
     * Validation rules for storing a module manager.
     */
    public function storeRules(): array
    {
        return [
            'module_name' => ['required', 'string', 'max:100', 'unique:module_managers,module_name', 'regex:/^[a-z0-9-]+$/'],
            'display_name' => ['required', 'string', 'max:100'],
            'display_name_singular' => ['required', 'string', 'max:100'],
            'resource_type' => ['required', 'string', 'max:100'],
            'identifier_field' => ['nullable', 'string', 'in:id,slug'],
            'identifier_type' => ['nullable', 'string', 'in:number,string'],
            'requires_auth' => ['nullable', 'boolean'],
            'route_path' => ['required', 'string', 'max:100'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:100'],
            'fields.*.type' => ['required', 'string', 'in:string,number,boolean,Date,File'],
            'fields.*.required' => ['required', 'boolean'],
            'enabled' => ['nullable', 'boolean'],
            'dev_mode' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'in:admin,manager,user'],
            'translations' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'actions.create.enabled' => ['nullable', 'boolean'],
            'actions.edit.enabled' => ['nullable', 'boolean'],
            'actions.delete.enabled' => ['nullable', 'boolean'],
            'actions.deleteAll.enabled' => ['nullable', 'boolean'],
            'actions.show.enabled' => ['nullable', 'boolean'],
            'actions.search.enabled' => ['nullable', 'boolean'],
            'actions.export.enabled' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Validation rules for updating a module manager.
     */
    public function updateRules(): array
    {
        return [
            'module_name' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'display_name' => ['sometimes', 'string', 'max:100'],
            'display_name_singular' => ['sometimes', 'string', 'max:100'],
            'resource_type' => ['sometimes', 'string', 'max:100'],
            'identifier_field' => ['nullable', 'string', 'in:id,slug'],
            'identifier_type' => ['nullable', 'string', 'in:number,string'],
            'requires_auth' => ['nullable', 'boolean'],
            'route_path' => ['sometimes', 'string', 'max:100'],
            'fields' => ['sometimes', 'array', 'min:1'],
            'fields.*.name' => ['required_with:fields', 'string', 'max:100'],
            'fields.*.type' => ['required_with:fields', 'string', 'in:string,number,boolean,Date,File'],
            'fields.*.required' => ['required_with:fields', 'boolean'],
            'enabled' => ['nullable', 'boolean'],
            'dev_mode' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'in:admin,manager,user'],
            'translations' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'actions.create.enabled' => ['nullable', 'boolean'],
            'actions.edit.enabled' => ['nullable', 'boolean'],
            'actions.delete.enabled' => ['nullable', 'boolean'],
            'actions.deleteAll.enabled' => ['nullable', 'boolean'],
            'actions.show.enabled' => ['nullable', 'boolean'],
            'actions.search.enabled' => ['nullable', 'boolean'],
            'actions.export.enabled' => ['nullable', 'boolean'],
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
            'regex' => 'The :attribute field must contain only lowercase letters, numbers, and hyphens.',
            'array' => 'The :attribute field must be an array.',
            'min' => 'The :attribute field must have at least :min items.',
            'in' => 'The selected :attribute is invalid.',
            'boolean' => 'The :attribute field must be true or false.',
        ];
    }
}
