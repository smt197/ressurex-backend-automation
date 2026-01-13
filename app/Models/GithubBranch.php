<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GithubBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'protected',
        'commit_sha',
        'commit_message',
        'commit_date',
        'github_repository_id',
    ];

    protected $casts = [
        'protected' => 'boolean',
        'commit_date' => 'datetime',
    ];

    /**
     * Relation: une branche appartient à un repository.
     */
    public function githubRepository(): BelongsTo
    {
        return $this->belongsTo(GithubRepository::class);
    }
}
