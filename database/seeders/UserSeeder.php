<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'user']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'admin']);

        $availableLanguages = [
            ['code' => 'en', 'name' => 'English', 'flag' => 'https://cdn.jsdelivr.net/npm/country-flag-emoji-json@2.0.0/dist/images/GB.svg'],
            ['code' => 'fr', 'name' => 'Français', 'flag' => 'https://cdn.jsdelivr.net/npm/country-flag-emoji-json@2.0.0/dist/images/FR.svg'],
            ['code' => 'pt', 'name' => 'Portuguese', 'flag' => 'https://cdn.jsdelivr.net/npm/country-flag-emoji-json@2.0.0/dist/images/PT.svg'],
        ];

        // Create regular users
        User::factory(150)->create()->each(function ($user) use ($availableLanguages) {
            $user->assignRole('user');
            $user->givePermissionTo(['browse_admin_read']);

            $country = Country::inRandomOrder()->first();
            if ($country) {
                $user->country()->associate($country);
                if ($country->dial_code) {
                    $randomNumber = str_pad((string) rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
                    $user->phone = $country->dial_code.$randomNumber;
                }
                $user->save();
            }

            // Attach languages to user (assuming many-to-many relationship)
            $languagesToAttach = [];
            foreach ($availableLanguages as $index => $languageData) {
                $language = Language::updateOrCreate(
                    ['code' => $languageData['code']],
                    [
                        'name' => $languageData['name'],
                        'flag' => $languageData['flag'],
                    ]
                );
                $languagesToAttach[$language->id] = ['is_preferred' => ($index === 0)];
            }
            // $user->languages()->sync($languagesToAttach);
        });

        // Create tekie user
        $tekieCountry = Country::where('country_code', 'FR')->first();
        $tekieUser = User::factory()->create([
            'first_name' => 'Tekie',
            'last_name' => 'Developer',
            'photo' => '',
            'email' => 'tekie@tekie.com',
            'phone' => '+33634578291',
            'birthday' => '1990-01-01',
            'password' => Hash::make('P@sser12'),
            'country_id' => $tekieCountry?->id,
            'email_verified_at' => now(),
            'is_blocked' => false,
        ]);
        $tekieUser->assignRole(['user', 'manager']);
        $tekieUser->givePermissionTo(['browse_admin_create', 'browse_admin_read']);

        // Attach languages to tekie user
        $tekieLanguages = [];
        foreach ($availableLanguages as $index => $languageData) {
            $language = Language::updateOrCreate(
                ['code' => $languageData['code']],
                [
                    'name' => $languageData['name'],
                    'flag' => $languageData['flag'],
                ]
            );
            $tekieLanguages[$language->id] = ['is_preferred' => ($index === 0)];
        }
        // $tekieUser->languages()->sync($tekieLanguages);

        // Create admin user
        $adminCountry = Country::where('country_code', 'US')->first();
        $adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'Administrator',
            'photo' => '',
            'email' => 'admin@admin.com',
            'phone' => $adminCountry?->dial_code.rand(100000000, 999999999),
            'birthday' => '1985-05-15',
            'password' => Hash::make('P@sser12'),
            'country_id' => $adminCountry?->id,
            'email_verified_at' => now(),
            'is_blocked' => false,
        ]);
        $adminUser->assignRole('admin');
        $adminUser->givePermissionTo(['browse_admin_create', 'browse_admin_read', 'browse_admin_update', 'browse_admin_delete']);

        // Attach languages to admin user
        $adminLanguages = [];
        foreach ($availableLanguages as $index => $languageData) {
            $language = Language::updateOrCreate(
                ['code' => $languageData['code']],
                [
                    'name' => $languageData['name'],
                    'flag' => $languageData['flag'],
                ]
            );
            $adminLanguages[$language->id] = ['is_preferred' => ($index === 0)];
        }
        // $this->setDefaultLanguages($adminUser);

        // Create manager user
        $managerCountry = Country::where('country_code', 'US')->first();
        $managerUser = User::factory()->create([
            'first_name' => 'Manager',
            'last_name' => 'Manager',
            'photo' => '',
            'email' => 'manager@manager.com',
            'phone' => $managerCountry?->dial_code.rand(100000000, 999999999),
            'birthday' => '1985-05-15',
            'password' => Hash::make('P@sser12'),
            'country_id' => $managerCountry?->id,
            'email_verified_at' => now(),
            'is_blocked' => false,
        ]);
        $managerUser->assignRole('manager');
        $managerUser->givePermissionTo(['browse_admin_create', 'browse_admin_read']);

        // Attach languages to admin user
        $adminLanguages = [];
        foreach ($availableLanguages as $index => $languageData) {
            $language = Language::updateOrCreate(
                ['code' => $languageData['code']],
                [
                    'name' => $languageData['name'],
                    'flag' => $languageData['flag'],
                ]
            );
            $adminLanguages[$language->id] = ['is_preferred' => ($index === 0)];
        }
    }

    public function setDefaultLanguages($user): void
    {
        $getAppLanguage = app()->getLocale();
        $newLanguageId = DB::table('languages')->where('code', $getAppLanguage)->value('id');
        $user->languages()->attach($newLanguageId, [
            'is_preferred' => true, // Set the new language as preferred
        ]);
    }
}
