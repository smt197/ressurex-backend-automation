<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceMode extends Model
{
    protected $table = 'maintenance_mode';

    protected $fillable = [
        'is_active',
        'message',
        'activated_at',
        'activated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activated_at' => 'datetime',
        ];
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public static function isActive(): bool
    {
        return self::latest()->first()?->is_active ?? false;
    }

    public static function getMessage(): ?string
    {
        return self::latest()->first()?->message;
    }
}
