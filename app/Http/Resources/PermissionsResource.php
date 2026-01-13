<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $displayName = 'permissions.'.$this->name;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => __($displayName),
            'guard_name' => $this->guard_name,
        ];
    }
}
