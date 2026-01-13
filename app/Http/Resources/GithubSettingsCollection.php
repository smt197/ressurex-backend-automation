<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class GithubSettingsCollection extends ResourceCollection
{
    public function toArray($request): array
    {

        $response = [
            'message' => $this->successMessage($request),
            'data' => GithubSettingsResource::collection($this->collection),
        ];

         // On vérifie si la ressource est une instance du paginateur
        // Si c'est le cas, on ajoute les informations de pagination à la réponse.
        if ($this->resource instanceof LengthAwarePaginator) {
            $response['pagination'] = [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ];
        }

        return $response;
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'GET':
                return __('github.settings.index');
            case 'POST':
                return __('github.settings.created');
            case 'PATCH':
                return __('github.settings.updated');
            case 'PUT':
                return __('github.settings.updated');
            case 'DELETE':
                return __('github.settings.deleted');
            default:
                return '';
        }
    }
}
