<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Greffe extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email'];

    protected function casts(): array
    {
        return [
        ];
    }
}
