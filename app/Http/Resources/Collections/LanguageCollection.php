<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\LanguageResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class LanguageCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => LanguageResource::collection($this->collection),
            'links' => [
                'self' => url()->current(),
            ],
            'meta' => [
                'count' => $this->collection->count(),
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
            ],
        ];
    }
}
