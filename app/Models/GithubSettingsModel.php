<?php

namespace App\Models;

use App\Settings\GithubSettings;
use Illuminate\Database\Eloquent\Model;

/**
 * Model wrapper pour GithubSettings (Spatie Laravel Settings)
 * Ce modèle permet d'utiliser Orion avec les settings
 */
class GithubSettingsModel extends Model
{
    protected $table = 'settings'; // Table Spatie Laravel Settings

    protected $fillable = [
        'group',
        'name',
        'locked',
        'payload'
    ];

    protected $casts = [
        'locked' => 'boolean',
        'payload' => 'array'
    ];

    public $timestamps = false;

    /**
     * Scope pour filtrer par groupe github
     */
    public function scopeGithub($query)
    {
        return $query->where('group', 'github');
    }

    /**
     * Récupère la valeur du token depuis le payload
     */
    public function getGithubTokenAttribute()
    {
        if ($this->name === 'github_token' && isset($this->payload)) {
            return $this->payload;
        }
        return null;
    }

    /**
     * Définit la valeur du token dans le payload
     */
    public function setGithubTokenAttribute($value)
    {
        $this->name = 'github_token';
        $this->group = 'github';
        $this->payload = $value;
    }

    /**
     * Récupère ou crée l'entrée github_token
     */
    public static function getOrCreateGithubToken()
    {
        return static::firstOrCreate(
            [
                'group' => 'github',
                'name' => 'github_token',
            ],
            [
                'locked' => false,
                'payload' => null,
            ]
        );
    }
}
