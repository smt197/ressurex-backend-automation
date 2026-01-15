<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    protected $fillable = [
        'user_id',
        'module_manager_id',
        'module_slug',
        'branch_name',
        'dokploy_deployment_id',
        'status',
        'message',
        'logs',
        'progress',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'logs' => 'array',
        'progress' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_BUILDING = 'building';
    const STATUS_DEPLOYING = 'deploying';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * Get the user that owns the deployment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the module manager associated with the deployment
     */
    public function moduleManager(): BelongsTo
    {
        return $this->belongsTo(ModuleManager::class);
    }

    /**
     * Check if deployment is active (not completed)
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_BUILDING,
            self::STATUS_DEPLOYING,
        ]);
    }

    /**
     * Check if deployment is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUCCESS,
            self::STATUS_FAILED,
        ]);
    }

    /**
     * Scope a query to only include active deployments
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_BUILDING,
            self::STATUS_DEPLOYING,
        ]);
    }

    /**
     * Scope a query to only include deployments for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
