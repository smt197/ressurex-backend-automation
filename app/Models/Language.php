<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'flag',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_languages')
            ->withPivot(['is_preferred'])
            ->withTimestamps();
    }
}
