<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class DocumentCollection extends ResourceCollection
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
            'data' => DocumentResource::collection($this->collection),
        ];

        // 2. On vérifie si la ressource est une instance du paginateur
        // Si c'est le cas, on ajoute les informations de pagination à la réponse.
        // Sinon (cas de la suppression), on ne les ajoute pas, ce qui évite l'erreur.
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
                return __('documents.list');
            case 'DELETE':
                // Personnalisation du message pour la suppression
                return $this->collection->count().' document(s) supprimé(s) avec succès.';
            default:
                return __('documents.list');
        }
    }
}
