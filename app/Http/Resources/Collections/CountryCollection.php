<?php

namespace App\Http\Resources\Collections;

use App\Http\Resources\CountryResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CountryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => CountryResource::collection($this->collection),
        ];
    }
}
