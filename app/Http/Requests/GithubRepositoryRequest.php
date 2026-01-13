<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class GithubRepositoryRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation lors de la création.
     */
    public function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:255', 'unique:github_repositories,full_name'],
            'owner' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'html_url' => ['required', 'string', 'url', 'max:500'],
            'default_branch' => ['nullable', 'string', 'max:255'],
            'private' => ['nullable', 'boolean'],
            'visibility' => ['nullable', 'string', 'in:public,private'],
            'github_id' => ['nullable', 'integer', 'unique:github_repositories,github_id'],
            'is_owner' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Règles de validation lors de la mise à jour.
     */
    public function updateRules(): array
    {
        $repositoryId = $this->route('github_repository');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'full_name' => ['sometimes', 'required', 'string', 'max:255', 'unique:github_repositories,full_name,' . $repositoryId . ',slug'],
            'owner' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'html_url' => ['sometimes', 'required', 'string', 'url', 'max:500'],
            'default_branch' => ['nullable', 'string', 'max:255'],
            'private' => ['nullable', 'boolean'],
            'visibility' => ['nullable', 'string', 'in:public,private'],
            'github_id' => ['nullable', 'integer', 'unique:github_repositories,github_id,' . $repositoryId . ',slug'],
            'is_owner' => ['nullable', 'boolean'],
        ];
    }

    public function commonMessages(): array
    {
        return [
            'required' => 'Le champ :attribute est requis.',
            'string' => 'Le champ :attribute doit être une chaîne de caractères.',
            'max' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
            'unique' => 'Ce :attribute existe déjà.',
            'url' => 'Le champ :attribute doit être une URL valide.',
            'boolean' => 'Le champ :attribute doit être vrai ou faux.',
            'in' => 'Le champ :attribute doit être :values.',
            'integer' => 'Le champ :attribute doit être un nombre entier.',
        ];
    }
}
