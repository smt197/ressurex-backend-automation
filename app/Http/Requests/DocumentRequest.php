<?php

namespace App\Http\Requests;

use Orion\Http\Requests\Request;

class DocumentRequest extends Request
{
    public $maxFileSize;

    public function __construct()
    {
        $this->maxFileSize = config('media-library.max_file_size', 20480); // 20MB par défaut
    }

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
            'description' => ['nullable', 'string'],
            // On s'attend à recevoir un tableau de fichiers
            'files' => ['required', 'array'],
            // Chaque élément du tableau doit être un fichier valide
            'files.*' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip',
                'max:'.$this->maxFileSize,
            ],
        ];
    }

    /**
     * Règles de validation lors de la mise à jour.
     */
    public function updateRules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Le tableau de fichiers est optionnel lors d'une mise à jour
            'files' => ['nullable', 'array'],
            'files.*' => [
                'sometimes',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip',
                'max:'.$this->maxFileSize,
            ],
        ];
    }

    public function commonMessages(): array
    {
        return [
            'required' => 'Le champ :attribute est requis.',
            'string' => 'Le champ :attribute doit être une chaîne de caractères.',
            'array' => 'Le champ :attribute doit être un tableau.',
            'max' => [
                'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
                'file' => 'Chaque fichier dans :attribute ne doit pas dépasser :max kilooctets.',
            ],
            'mimes' => 'Chaque fichier dans :attribute doit être de type :values.',
            'file' => 'Chaque élément de :attribute doit être un fichier.',
        ];
    }
}
