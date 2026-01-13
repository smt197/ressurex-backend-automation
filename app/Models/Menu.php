<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\SlugOptions;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'color',
        'route',
        'roles',
        'slug',
        'category_id',
        'disable',
        'description',
    ];

    protected $casts = [
        'roles' => 'array',
        'description' => 'array',
        'disable' => 'integer',
    ];

    public function getDescriptionForLocale(string $locale = 'fr'): ?string
    {
        $descriptions = $this->description;
        if (! is_array($descriptions)) {
            return null;
        }

        return $descriptions[$locale] ?? $descriptions['fr'] ?? null;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getSlugOptions(): SlugOptions
    {

        return SlugOptions::create()
            ->generateSlugsFrom(['name']) // Ou par exemple ->generateSlugsFrom('email') ou un champ 'username'
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate(); // Optionnel: si vous ne voulez pas que le slug change si le nom/prénom change
    }
}
