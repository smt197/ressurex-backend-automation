<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray($request)
    {
        $displayName = 'country.'.$this->country_code;

        return [
            'id' => $this->id,
            'country_code' => $this->country_code,
            'country_name' => __($displayName),
            'image_url' => $this->image_url,
            'dial_code' => $this->dial_code,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'users' => $this->whenLoaded('users', function () {
                return UsersResource::collection($this->users);
            }),
        ];
    }
}
