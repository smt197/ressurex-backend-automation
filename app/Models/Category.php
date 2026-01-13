<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['name', 'slug', 'order', 'icon', 'navigation_type', 'position_reference_id', 'position_type'];

    protected $casts = [
        'order' => 'integer',
        'position_reference_id' => 'integer',
    ];

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function positionReference()
    {
        return $this->belongsTo(Category::class, 'position_reference_id');
    }

    public static function calculateNewOrder(int $referenceId, string $positionType): int
    {
        $referenceCategory = self::find($referenceId);
        if (! $referenceCategory) {
            return self::max('order') + 1;
        }

        if ($positionType === 'before') {
            $newOrder = $referenceCategory->order;
            self::where('order', '>=', $newOrder)->increment('order');

            return $newOrder;
        } else {
            $newOrder = $referenceCategory->order + 1;
            self::where('order', '>=', $newOrder)->increment('order');

            return $newOrder;
        }
    }

    public function getSlugOptions(): SlugOptions
    {
        // Choisissez le champ (ou les champs) source pour votre slug.
        // 'username' est un bon candidat s'il existe et est unique.
        // Sinon, une combinaison de first_name et last_name est courant.
        return SlugOptions::create()
            ->generateSlugsFrom(['name']) // Ou par exemple ->generateSlugsFrom('email') ou un champ 'username'
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate(); // Optionnel: si vous ne voulez pas que le slug change si le nom/prénom change
    }
}
