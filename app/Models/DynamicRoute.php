<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicRoute extends Model
{
    protected $fillable = [
        'name',
        'uri',
        'method',
        'controller',
        'action',
        'description',
        'requires_auth',
        'guard',
        'middleware',
        'permissions',
        'roles',
        'is_active',
        'order',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'requires_auth' => 'boolean',
            'is_active' => 'boolean',
            'middleware' => 'array',
            'permissions' => 'array',
            'roles' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * Scope to get only active routes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by guard.
     */
    public function scopeGuard($query, string $guard)
    {
        return $query->where('guard', $guard);
    }

    /**
     * Scope to order by order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Get the full controller action.
     */
    public function getFullActionAttribute(): string
    {
        if ($this->controller && $this->action) {
            return $this->controller.'@'.$this->action;
        }

        return $this->controller ?? '';
    }

    /**
     * Get all middleware for this route.
     */
    public function getAllMiddleware(): array
    {
        $middleware = [$this->guard];

        if ($this->requires_auth) {
            $middleware[] = 'auth:'.$this->guard;
        }

        if ($this->middleware) {
            $middleware = array_merge($middleware, $this->middleware);
        }

        return array_unique($middleware);
    }
}
