<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class Init extends Command
{
    protected $signature = 'app:init {--force : Force operation to run}';

    protected $description = 'Initialize application (Cache, Logo, Migrations, Seeds, Storage, Frontend)';

    public function handle()
    {
        $this->info('🚀 Starting App Initialization...');

        // 0. NETTOYAGE DU CACHE (PRIORITÉ ABSOLUE)
        $this->handleCache();

        // 1. Wait for Database
        $this->waitForDb();

        // 2. Setup Frontend Repository
        $this->handleFrontendSetup();

        // 3. Storage Link
        $this->handleStorageLink();

        // 4. Migrations
        $this->handleMigrations();

        // 5. Seeders
        $this->handleSeeders();

        $this->info('✅ App Initialization Complete!');

        return 0;
    }

    private function handleCache(): void
    {
        $this->info('🧹 Clearing application cache...');
        try {
            $this->call('optimize:clear');
            $this->call('view:clear');
            $this->info('✅ Cache cleared (Config, Routes, Views, Events).');
        } catch (\Exception $e) {
            $this->warn('⚠️ Could not clear cache: ' . $e->getMessage());
        }
    }

    private function waitForDb(): void
    {
        $this->info('Checking Database connection...');
        $maxRetries = 30;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                DB::purge();
                DB::connection()->getPdo();
                $this->info('✅ Database connected.');

                return;
            } catch (\Exception $e) {
                $this->warn('Waiting for Database... (' . ($attempt + 1) . "/$maxRetries)");
                sleep(1);
                $attempt++;
            }
        }
        $this->error('❌ Database connection failed.');
    }

    private function handleStorageLink(): void
    {
        if (File::exists(public_path('storage'))) {
            $this->comment('✓ Storage link already exists.');

            return;
        }

        $this->call('storage:link');
        $this->info('✅ Storage link created.');
    }

    private function handleMigrations(): void
    {
        try {
            $this->info('📊 Running Migrations...');

            // Check if this is a fresh deployment by looking for essential columns
            $needsFresh = false;
            try {
                $columns = DB::getSchemaBuilder()->getColumnListing('users');
                if (! empty($columns) && ! in_array('first_name', $columns)) {
                    $needsFresh = true;
                    $this->warn('⚠️ Detected incompatible users table structure. Running fresh migration...');
                }
            } catch (\Exception $e) {
                // Table doesn't exist, normal migrate will work
            }

            if ($needsFresh) {
                $this->call('migrate:fresh', ['--force' => true]);
            } else {
                $this->call('migrate', ['--force' => true]);
            }

            $this->info('✅ Migrations completed.');
        } catch (\Exception $e) {
            $this->error('❌ Migration error: ' . $e->getMessage());
        }
    }

    private function handleSeeders(): void
    {
        $flagFile = storage_path('app/.seeded');

        if (! File::exists($flagFile) || $this->option('force')) {
            $this->info('🌱 Running Seeders...');
            try {
                $this->call('db:seed', ['--force' => true]);
                File::put($flagFile, date('Y-m-d H:i:s'));
                $this->info('✅ Seeders completed.');
            } catch (\Exception $e) {
                $this->error('❌ Seeding failed: ' . $e->getMessage());
            }
        } else {
            $this->comment('✓ Seeders already ran.');
        }
    }

    private function handleFrontendSetup(): void
    {
        $frontendPath = config('app.frontend_path');
        $frontendRepo = env('FRONTEND_REPO', 'https://github.com/smt197/resurex-frontend-automation.git');
        $githubToken = env('GITHUB_TOKEN', '');

        $this->info('🔧 Setting up frontend repository...');

        // Skip if we're in local development (path exists and is not in /var/www)
        if (File::exists($frontendPath) && ! str_starts_with($frontendPath, '/var/www')) {
            $this->comment('✓ Frontend path exists (local development).');

            return;
        }

        // Check if already cloned
        if (File::exists($frontendPath . '/.git')) {
            $this->comment('✓ Frontend repository already exists.');
            $this->pullLatestFrontend($frontendPath);

            return;
        }

        // Create parent directory
        $parentDir = dirname($frontendPath);
        if (! File::exists($parentDir)) {
            File::makeDirectory($parentDir, 0755, true);
        }

        // Clone repository
        $this->info('📥 Cloning frontend repository...');

        $repoUrl = $frontendRepo;
        if ($githubToken) {
            $repoUrl = str_replace('https://', "https://{$githubToken}@", $frontendRepo);
        }

        $result = Process::run("git clone --depth 1 {$repoUrl} {$frontendPath}");

        if (! $result->successful()) {
            $this->warn('⚠️ Failed to clone frontend repository: ' . $result->errorOutput());
            $this->warn('   Module generation will not work without the frontend repository.');

            return;
        }

        // Configure git
        Process::path($frontendPath)->run('git config user.email "bot@resurex.com"');
        Process::path($frontendPath)->run('git config user.name "Resurex Bot"');

        // Install npm dependencies
        $this->info('📦 Installing frontend npm dependencies...');
        $npmResult = Process::path($frontendPath)->timeout(300)->run('npm ci --legacy-peer-deps --silent');

        if ($npmResult->successful()) {
            $this->info('✅ Frontend repository setup complete!');
        } else {
            $this->warn('⚠️ npm install had issues: ' . $npmResult->errorOutput());
            // Try fallback
            Process::path($frontendPath)->timeout(300)->run('npm install --legacy-peer-deps --silent');
        }
    }

    private function pullLatestFrontend(string $frontendPath): void
    {
        $this->info('📥 Pulling latest frontend changes...');

        Process::path($frontendPath)->run('git config --global --add safe.directory ' . $frontendPath);
        $result = Process::path($frontendPath)->run('git pull origin main');

        if ($result->successful()) {
            $this->comment('✓ Frontend updated.');
        } else {
            $this->warn('⚠️ Could not pull latest changes: ' . $result->errorOutput());
        }
    }
}
