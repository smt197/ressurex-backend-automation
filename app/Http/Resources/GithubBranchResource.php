<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GithubBranchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'protected' => $this->protected,
            'commit_sha' => $this->commit_sha,
            'commit_message' => $this->commit_message,
            'commit_date' => $this->commit_date?->toDateTimeString(),
            'github_repository_id' => $this->github_repository_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
