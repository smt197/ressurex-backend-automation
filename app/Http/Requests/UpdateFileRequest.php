<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxFileSize = config('media-library.max_file_size', 20480);

        return [
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip',
                'max:'.$maxFileSize,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.file' => 'Le champ :attribute doit être un fichier valide.',
            'file.mimes' => 'Le fichier doit être de type :values.',
            'file.max' => 'Le fichier ne doit pas dépasser :max kilooctets.',
        ];
    }
}
