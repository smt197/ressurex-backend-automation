<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class ModuleManager extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'module_name',
        'slug',
        'display_name',
        'display_name_singular',
        'resource_type',
        'identifier_field',
        'identifier_type',
        'requires_auth',
        'route_path',
        'fields',
        'enabled',
        'dev_mode',
        'roles',
        'translations',
        'actions',
        'github_repository_slug',
        'github_branch',
        'github_commit_sha',
        'github_pushed_at',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'roles' => 'array',
            'translations' => 'array',
            'actions' => 'array',
            'enabled' => 'boolean',
            'dev_mode' => 'boolean',
            'requires_auth' => 'boolean',
            'github_pushed_at' => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['module_name'])
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }
}
