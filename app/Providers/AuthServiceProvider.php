<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Country;
use App\Models\Document;
use App\Models\GithubRepository;
use App\Models\Language;
use App\Models\Menu;
use App\Models\ModuleManager;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\GithubSettingsModel;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\CountryPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\GithubRepositoryPolicy;
use App\Policies\GithubSettingsPolicy;
use App\Policies\LanguagePolicy;
use App\Policies\MenuPolicy;
use App\Policies\ModuleManagerPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Document::class => DocumentPolicy::class,
        User::class => UserPolicy::class,
        Category::class => CategoryPolicy::class,
        Menu::class => MenuPolicy::class,
        Language::class => LanguagePolicy::class,
        Country::class => CountryPolicy::class,
        Roles::class => RolePolicy::class,
        Permissions::class => PermissionPolicy::class,
        ModuleManager::class => ModuleManagerPolicy::class,
        GithubRepository::class => GithubRepositoryPolicy::class,
        GithubSettingsModel::class => GithubSettingsPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
