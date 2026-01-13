<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\MenuResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MenuCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        $data = [
            'message' => $this->successMessage($request),
            'data' => MenuResource::collection($this->collection),
            'pagination' => $this->when(
                $this->resource instanceof \Illuminate\Pagination\AbstractPaginator,
                function () {
                    return [
                        'total' => $this->resource->total(),
                        'current_page' => $this->resource->currentPage(),
                        'per_page' => $this->resource->perPage(),
                    ];
                }
            ),

        ];

        return $data;
    }

    public function withResponse($request, $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);

        // Remove default Laravel pagination keys if they exist
        unset(
            $jsonResponse['links'],
            $jsonResponse['meta'],
            $jsonResponse['data']['links'],
            $jsonResponse['data']['meta']
        );

        $response->setContent(json_encode($jsonResponse));
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'GET':
                return __('menus.list');
            case 'DELETE':
                return __('menus.deleted');
            default:
                return '';
        }
    }
}
