<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporyFile extends Model
{
    use HasFactory;

    protected $table = 'temporyfiles';

    protected $fillable = [
        'user_id',
        'original_name',
        'path',
        'type',
        'collection',
        'hash_field',
        'uploaded_at',
        'confirmed',
    ];

    protected $casts = [
        'confirmed' => 'boolean',
        'uploaded_at' => 'datetime',
    ];

    /**
     * Relationship with User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
