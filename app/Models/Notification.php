<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'id',
        'notifiable_id',
        'notifiable_type',
        'type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // Relation polymorphique au lieu de la relation user directe
    public function notifiable()
    {
        return $this->morphTo();
    }
}
