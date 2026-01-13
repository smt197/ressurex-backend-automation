<?php

namespace App\Models;

use App\Http\Resources\CountryResource;
use App\Http\Resources\LanguageResource;
use App\Http\Resources\UsersResource;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use BenBjurstrom\Otpz\Models\Concerns\HasOtps;
use BenBjurstrom\Otpz\Models\Concerns\Otpable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles; // AJOUTER CECI
use Spatie\Sluggable\HasSlug; // AJOUTER CECI
use Spatie\Sluggable\SlugOptions;

class User extends Authenticatable implements HasMedia, MustVerifyEmail, Otpable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasOtps, HasRoles, HasSlug, InteractsWithMedia, Notifiable;

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'last_name',
        'first_name',
        'role',
        'photo',
        'email',
        'confirmed',
        'is_blocked',
        'password',
        'otp_enabled',
        'otp_status_auth',
        'birthday', // Ajout du champ birthday,
        'phone',
        'slug',
        'session_user_second',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthday' => 'date',
            'is_blocked' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        // Choisissez le champ (ou les champs) source pour votre slug.
        // 'username' est un bon candidat s'il existe et est unique.
        // Sinon, une combinaison de first_name et last_name est courant.
        return SlugOptions::create()
            ->generateSlugsFrom(['first_name', 'last_name']) // Ou par exemple ->generateSlugsFrom('email') ou un champ 'username'
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate(); // Optionnel: si vous ne voulez pas que le slug change si le nom/prénom change
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): ?string
    {
        if ($this->first_name && $this->last_name) {
            return trim($this->first_name.' '.$this->last_name);
        }

        return $this->first_name ?? $this->last_name ?? null;
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getOtpEnabledAttribute()
    {
        return DB::table('users')
            ->where('id', $this->id)
            ->value('otp_enabled') == 1;
    }

    public function getOtpEnabledAuth()
    {
        return DB::table('users')
            ->where('id', $this->id)
            ->value('otp_status_auth') == 1;
    }

    public function getUserOtpId()
    {
        return DB::table('otps')
            ->where('user_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->value('id');
    }

    public function getUserOtpStatus()
    {
        $otp = DB::table('otps')
            ->where('user_id', $this->id)
            ->orderByDesc('created_at') // Récupère le plus récent
            ->first(); // Prend le premier (le plus récent)

        if (! $otp) {
            return false; // Aucun OTP trouvé
        }

        return $otp->status == 1; // Retourne true si status = 1, sinon false
    }

    public function toggleOtp(bool $status): void
    {
        $this->forceFill([
            'otp_enabled' => $status,
        ])->save();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profils')
            ->acceptsMimeTypes(['image/png', 'image/jpg', 'image/jpeg'])
            ->singleFile()
            ->useDisk('profils');
    }

    // app/Models/User.php
    public function getPhotoAttribute($value)
    {
        if (! $this->relationLoaded('media') || ! $this->hasMedia('profils')) {
            return $value;
        }

        $media = $this->getFirstMedia('profils');

        return $media->getUrl();
    }

    // app/Models/User.php
    public function languages()
    {
        return $this->belongsToMany(Language::class, 'user_languages')
            ->withPivot(['is_preferred'])
            ->withTimestamps();
    }

    /**
     * Récupère UNIQUEMENT la langue préférée de l'utilisateur
     * Retourne null si aucune langue n'est explicitement marquée comme préférée
     *
     * @return array|null Retourne un tableau avec les infos de la langue ou null
     */
    public function getPreferredLanguageUser()
    {
        $preferredLanguage = $this->languages()->where('is_preferred', true)->first();

        return $preferredLanguage; // Aucune langue préférée définie
    }

    public function getPermissions()
    {
        $permissions = $this->permissions;

        return $permissions;
    }

    /**
     * Récupère le code de langue préféré de l'utilisateur (pt, fr, en)
     */
    public function getLocale(): string
    {
        $preferredLanguage = $this->getPreferredLanguageUser();

        if ($preferredLanguage && $preferredLanguage->code) {
            return $preferredLanguage->code;
        }

        // Fallback sur la locale par défaut de l'application
        return config('app.locale', 'en');
    }

    public function getUserToken()
    {
        $personal_access_tokens = DB::table('personal_access_tokens')->where('tokenable_id', $this->id)->first();

        return $personal_access_tokens->token;
    }

    public function getUserInfo()
    {

        return new UsersResource($this);
    }

    public function getAllLanguagesFormatted()
    {
        return LanguageResource::collection(language::all());
    }

    public function getCountriesWithUsers()
    {
        // Retourne le pays de l'utilisateur courant sous forme de collection
        return CountryResource::collection(
            collect([$this->country])->filter() // Filtre les valeurs null
        );
    }

    public function getAllCountries()
    {
        // Retourne tous les pays depuis la table countries
        $countries = Country::all();

        return CountryResource::collection($countries);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user');
    }

    public function unreadChatMessagesCount()
    {
        // Compte les messages non lus dans les conversations de l'utilisateur
        return $this->conversations()
            ->withCount(['messages as unread_messages_count' => function ($query) {
                $query->whereNull('read_at'); // Filtre pour les messages non lus
            }])
            ->get()
            ->sum('unread_messages_count'); // Somme des messages non lus dans toutes les conversations

    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function getMenu()
    {
        $role = $this->getRoleNames()->first(); // Avec Spatie HasRoles
        $menus = Menu::query()
            ->whereJsonContains('roles', $role)
            ->orderBy('id')
            ->get();

        return $menus;
    }

    public function blockUser(): bool
    {
        return $this->update(['is_blocked' => true]);
    }

    public function unblockUser(): bool
    {
        return $this->update(['is_blocked' => false]);
    }

    public function isBlocked(): bool
    {
        return $this->is_blocked === true;
    }

    public function toggleBlock(): bool
    {
        return $this->update(['is_blocked' => ! $this->is_blocked]);
    }

    public function getFileSystemDefault(): string
    {
        return config('filesystems.default');
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
