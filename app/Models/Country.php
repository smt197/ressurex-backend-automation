<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'country_name',
        'image_url',
        'dial_code',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
