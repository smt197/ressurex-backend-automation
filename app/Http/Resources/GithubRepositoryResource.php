<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GithubRepositoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'message' => $this->successMessage($request),
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'owner' => $this->owner,
            'description' => $this->description,
            'html_url' => $this->html_url,
            'default_branch' => $this->default_branch,
            'private' => $this->private,
            'visibility' => $this->visibility,
            'github_id' => $this->github_id,
            'is_owner' => $this->is_owner,
            'last_synced_at' => $this->last_synced_at?->toDateTimeString(),
            'branches' => GithubBranchResource::collection($this->whenLoaded('branches')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'POST':
                return __('github.created');
            case 'PATCH':
                return __('github.updated');
            case 'PUT':
                return __('github.updated');
            case 'DELETE':
                return __('github.deleted');
            default:
                return '';
        }
    }
}
