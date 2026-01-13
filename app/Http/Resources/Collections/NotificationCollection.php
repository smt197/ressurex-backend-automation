<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'message' => $this->successMessage($request),
            'data' => NotificationResource::collection($this->collection),
            'pagination' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
            ],
        ];
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'GET':
                return __('notifications.list');
            case 'DELETE':
                return __('notifications.deleted');
            default:
                return '';
        }
    }
}
