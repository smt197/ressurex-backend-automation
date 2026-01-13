<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    protected $model = Document::class;

    public function toArray($request): array
    {

        return [
            'message' => $this->successMessage($request),
            'id' => $this->id,
            'name' => $this->allias_name,
            'slug' => $this->slug,
            'description' => $this->description,
            // On transforme la collection de médias en un tableau d'informations
            'files_info' => $this->getAllalliasDocs($this->id),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    private function getAllalliasDocs(int $id)
    {
        $document = $this->model::where('id', $id)->firstOrFail();
        $documentsWithAlias = $this->model::where('allias_name', $document->allias_name)->get();

        $media = $documentsWithAlias->map(function ($doc) {
            return $doc->getInfoMedia($doc);
        });

        return $media->collapse()->values()->all(); // flatten le tableau et retourne un tableau pur
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'POST':
                return __('menus.created');
            case 'PATCH':
                return __('menus.updated');
            case 'PUT':
                return __('menus.updated');
            case 'DELETE':
                return __('menus.deleted');
            default:
                return '';
        }
    }
}
