<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class GithubRepository extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'slug',
        'name',
        'full_name',
        'owner',
        'description',
        'html_url',
        'default_branch',
        'private',
        'visibility',
        'github_id',
        'is_owner',
        'last_synced_at',
        'user_id',
    ];

    protected $casts = [
        'private' => 'boolean',
        'is_owner' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name', 'owner'])
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * Relation: un repository appartient à un utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation: un repository a plusieurs branches.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(GithubBranch::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
