<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('general', function ($blueprint) {
            $blueprint->add('site_name', config('app.appname'));
            $blueprint->add('site_description', "Plongez dans une expérience utilisateur intuitive et intelligente. Nous mettons la puissance de l'IA au service de votre productivité, avec une interface conçue pour l'humain.");
            $blueprint->add('site_logo');
            $blueprint->add('site_subtitle', config('app.appname'));
            $blueprint->add('site_active', true);
            $blueprint->add('site_web', 'resurex.com');
        });
    }

    // Optionnel : méthode down si vous souhaitez pouvoir annuler la migration
    public function down(): void
    {
        $this->migrator->inGroup('general', function ($blueprint) {
            $blueprint->delete('site_name');
            $blueprint->delete('site_description');
            $blueprint->delete('site_logo');
            $blueprint->delete('site_active');
            $blueprint->delete('site_web');
        });
    }
};
