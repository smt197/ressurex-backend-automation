<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\PermissionsResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PermissionsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        return [
            'message' => $this->successMessage($request),
            'data' => PermissionsResource::collection($this->collection),
        ];
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
                return __('permissions.list');
            default:
                return '';
        }
    }
}
