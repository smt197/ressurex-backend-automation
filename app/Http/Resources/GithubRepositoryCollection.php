<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class GithubRepositoryCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [
            'message' => $this->successMessage($request),
            'data' => GithubRepositoryResource::collection($this->collection),
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
                return __('github.list');
                case 'POST':
                return __('github.created');
            case 'PATCH':
                return __('github.updated');
            case 'PUT':
                return __('github.updated');
            case 'DELETE':
                return $this->collection->count() . ' repository(ies) supprimé(s) avec succès.';
            default:
                return __('github.list');
        }
    }
}
