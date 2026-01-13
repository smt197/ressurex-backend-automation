<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RolesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $displayName = 'roles.'.$this->name;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => __($displayName),
            'guard_name' => $this->guard_name,
        ];
    }
}
