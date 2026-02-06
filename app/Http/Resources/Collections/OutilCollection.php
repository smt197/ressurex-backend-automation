<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\OutilResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OutilCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'message' => $this->successMessage($request),
            'data' => OutilResource::collection($this->collection),
        ];
    }

    public function withResponse($request, $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);

        // Transform pagination metadata to frontend format
        if (isset($jsonResponse['meta'])) {
            $jsonResponse['pagination'] = [
                'current_page' => $jsonResponse['meta']['current_page'] ?? 1,
                'per_page' => $jsonResponse['meta']['per_page'] ?? 15,
                'total' => $jsonResponse['meta']['total'] ?? 0,
            ];
        }

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
                return __('outils.list');
            default:
                return '';
        }
    }
}
