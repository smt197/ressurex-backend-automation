<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GithubSettingsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'message' => $this->successMessage($request),
            'id' => $this->id,
            'github_token' => $this->github_token,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'POST':
                return __('github.settings.created');
            case 'PATCH':
                return __('github.settings.updated');
            case 'PUT':
                return __('github.settings.updated');
            case 'DELETE':
                return __('github.settings.deleted');
            default:
                return '';
        }
    }
}
