<?php

namespace App\Console\Commands;

use App\Models\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateModuleSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:module-seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ModuleManagerSeeder based on current database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating ModuleManagerSeeder...');

        $modules = ModuleManager::all();
        
        $modulesData = $modules->map(function ($module) {
            // We exclude id, created_at, updated_at, and slug (as it's auto-generated usually, but we might want to keep it if steady)
            // Let's keep slug to ensure consistency
            return $module->makeHidden(['id', 'created_at', 'updated_at'])->toArray();
        })->toArray();

        $seederContent = $this->generateSeederContent($modulesData);
        $seederPath = database_path('seeders/ModuleManagerSeeder.php');

        File::put($seederPath, $seederContent);

        $this->info("ModuleManagerSeeder generated successfully at: {$seederPath}");
    }

    /**
     * Generate the content of the seeder file.
     *
     * @param array $modules
     * @return string
     */
    private function generateSeederContent(array $modules): string
    {
        $exportedModules = var_export($modules, true);
        
        // Improve formatting of the array export slightly if desired, or just leave as is.
        // var_export is usually sufficient for valid PHP code.

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModuleManager;

class ModuleManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$modules = {$exportedModules};

        foreach (\$modules as \$module) {
            ModuleManager::updateOrCreate(
                ['module_name' => \$module['module_name']],
                \$module
            );
        }
    }
}
PHP;
    }
}
