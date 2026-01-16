<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orange extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'desc'];

    protected function casts(): array
    {
        return [
        ];
    }
}
