<?php

namespace App\Http\Controllers;

use App\Events\DeploymentStatusUpdated;
use App\Http\Requests\ModuleManagerRequest;
use App\Http\Resources\Collections\ModuleManagerCollection;
use App\Http\Resources\ModuleManagerResource;
use App\Models\Deployment;
use App\Models\ModuleManager;
use App\Services\BackendModuleGenerator;
use App\Services\OllamaTranslationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class ModuleManagerController extends Controller
{

    use DisableAuthorization;
    /**
     * The Eloquent model associated with this controller.
     *
     * @var string
     */
    protected $model = ModuleManager::class;

    /**
     * Store old module name before update for rename detection
     *
     * @var string|null
     */
    private $oldModuleName = null;

    /**
     * Store old module fields before update for change detection
     *
     * @var array
     */
    private $oldModuleFields = [];

    /**
     * Store old route path before update for route change detection
     *
     * @var string|null
     */
    private $oldRoutePath = null;

    /**
     * The resource associated with the model.
     *
     * @var string
     */
    protected $resource = ModuleManagerResource::class;

    /**
     * The collection resource associated with the model.
     *
     * @var string
     */
    protected $collectionResource = ModuleManagerCollection::class;

    /**
     * The request class for validation.
     *
     * @var string
     */
    protected $request = ModuleManagerRequest::class;

    public function keyName(): string
    {
        return 'slug';
    }

    /**
     * Default pagination limit.
     */
    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    /**
     * Maximum pagination limit.
     */
    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }

    /**
     * The attributes that are used for searching.
     */
    public function searchableBy(): array
    {
        return ['module_name', 'display_name'];
    }

    public function sortableBy(): array
    {
        return ['module_name', 'display_name', 'created_at'];
    }

    public function filterableBy(): array
    {
        return ['enabled', 'dev_mode'];
    }

    /**
     * Generate module files using Node.js script
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateModule(Request $request)
    {
        try {
            // Auto-generate translations if not provided
            $translations = $request->input('translations');
            if (!$translations) {
                $translations = $this->generateTranslations($request);
            }

            // Default actions if not provided
            $defaultActions = [
                'create' => ['enabled' => true],
                'edit' => ['enabled' => true],
                'delete' => ['enabled' => true],
                'deleteAll' => ['enabled' => false],
                'show' => ['enabled' => true],
                'search' => ['enabled' => true],
                'export' => ['enabled' => false],
            ];

            $actions = $request->input('actions', $defaultActions);

            $moduleManager = ModuleManager::create([
                'module_name' => $request->input('moduleName'),
                'display_name' => $request->input('displayName'),
                'display_name_singular' => $request->input('displayNameSingular'),
                'resource_type' => $request->input('resourceType', $request->input('moduleName')),
                'identifier_field' => $request->input('identifierField', 'id'),
                'identifier_type' => $request->input('identifierType', 'number'),
                'requires_auth' => $request->input('requiresAuth', true),
                'route_path' => $request->input('routePath', $request->input('moduleName')),
                'fields' => $request->input('fields', []),
                'enabled' => $request->input('enabled', true),
                'dev_mode' => $request->input('devMode', false),
                'roles' => $request->input('roles', ['user']),
                'translations' => $translations,
                'actions' => $actions,
            ]);

            // Execute frontend generation script
            $this->executeGenerationScript($moduleManager);

            // Push generated frontend files to GitHub
            $this->pushFrontendChanges($moduleManager);

            // Generate backend files automatically
            $backendResult = $this->generateBackendModule($moduleManager, $moduleManager->roles ?? ['user']);

            if (! $backendResult['success']) {
                \Log::warning('Backend generation completed with errors', $backendResult);
            }

            // Regenerate module seeder to persist modules across database refreshes
            $this->regenerateModuleSeeder();

            // Handle Git operations if requested
            $gitResult = null;
            if ($request->has('gitConfig') && $request->input('gitConfig.createBranch')) {
                $gitResult = $this->handleGitOperations($request->input('gitConfig'), $moduleManager);
            }

            return response()->json([
                'success' => true,
                'message' => 'Module generated successfully!' . ($gitResult ? ' Git branch created and pushed.' : ''),
                'data' => [
                    'module' => new ModuleManagerResource($moduleManager),
                    'module_slug' => $moduleManager->slug,
                    'branch_name' => $gitResult['branch'] ?? null,
                    'deployment_triggered' => $gitResult['deployment_triggered'] ?? false,
                    'deployment_id' => $gitResult['deployment_id'] ?? null,
                ],
                'backend' => $backendResult,
                'git' => $gitResult,
            ]);
        } catch (\Exception $e) {
            \Log::error('Module generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Module generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle module enabled status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleModule(string $slug, Request $request)
    {
        $moduleManager = ModuleManager::where('slug', $slug)->firstOrFail();
        $moduleManager->enabled = $request->input('enabled', ! $moduleManager->enabled);
        $moduleManager->save();

        // Update the config file
        $this->updateModuleConfig($moduleManager);

        return response()->json([
            'success' => true,
            'message' => $moduleManager->enabled ? 'Module enabled successfully!' : 'Module disabled successfully!',
            'data' => new ModuleManagerResource($moduleManager),
        ]);
    }

    /**
     * Regenerate module files
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function regenerateModule(string $slug)
    {
        $moduleManager = ModuleManager::where('slug', $slug)->firstOrFail();

        // Execute the generation script
        $this->executeGenerationScript($moduleManager);

        return response()->json([
            'success' => true,
            'message' => 'Module regenerated successfully!',
            'data' => new ModuleManagerResource($moduleManager),
        ]);
    }

    /**
     * Get module files content
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getModuleFiles(string $slug)
    {
        $moduleManager = ModuleManager::where('slug', $slug)->firstOrFail();
        $frontendPath = config('app.frontend_path');
        $modulePath = "{$frontendPath}/src/app/pages/{$moduleManager->module_name}";

        if (! File::exists($modulePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Module files not found',
            ], 404);
        }

        $files = [];
        $filesList = File::allFiles($modulePath);

        foreach ($filesList as $file) {
            $relativePath = str_replace($modulePath . '/', '', $file->getPathname());
            $files[$relativePath] = File::get($file->getPathname());
        }

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    /**
     * Execute the module generation script
     */
    private function executeGenerationScript(ModuleManager $moduleManager): void
    {
        $frontendPath = config('app.frontend_path');
        $scriptPath = "{$frontendPath}/scripts/generate-module-api.js";

        \Log::info('Starting module generation', [
            'module' => $moduleManager->module_name,
            'frontend_path' => $frontendPath,
            'script_path' => $scriptPath,
        ]);

        // Verify paths exist
        if (! File::exists($frontendPath)) {
            \Log::error('Frontend path does not exist', ['path' => $frontendPath]);
            throw new \Exception("Frontend path does not exist: {$frontendPath}");
        }

        if (! File::exists($scriptPath)) {
            \Log::error('Script file does not exist', ['path' => $scriptPath]);
            throw new \Exception("Script file does not exist: {$scriptPath}");
        }

        // Create a temporary JSON file with module data
        $tempFile = storage_path('app/temp_module_' . $moduleManager->id . '.json');
        $data = [
            'moduleName' => $moduleManager->module_name,
            'displayName' => $moduleManager->display_name,
            'displayNameSingular' => $moduleManager->display_name_singular,
            'resourceType' => $moduleManager->resource_type,
            'identifierField' => $moduleManager->identifier_field,
            'identifierType' => $moduleManager->identifier_type,
            'requiresAuth' => $moduleManager->requires_auth,
            'routePath' => $moduleManager->route_path,
            'fields' => $moduleManager->fields,
            'devMode' => $moduleManager->dev_mode,
            'roles' => $moduleManager->roles ?? ['user'],
            'actions' => $moduleManager->actions,
        ];

        File::put($tempFile, json_encode($data, JSON_PRETTY_PRINT));
        \Log::info('Temporary file created', ['file' => $tempFile]);

        try {
            // Execute Node.js script using configured node path
            $nodePath = config('app.node_path');
            $result = Process::path($frontendPath)->run("\"{$nodePath}\" \"{$scriptPath}\" \"{$tempFile}\"");

            \Log::info('Script execution completed', [
                'exit_code' => $result->exitCode(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ]);

            if (! $result->successful()) {
                \Log::error('Script execution failed', [
                    'exit_code' => $result->exitCode(),
                    'error' => $result->errorOutput(),
                ]);
                throw new \Exception('Module generation script failed: ' . $result->errorOutput());
            }
        } finally {
            // Clean up temporary file
            if (File::exists($tempFile)) {
                File::delete($tempFile);
                \Log::info('Temporary file deleted', ['file' => $tempFile]);
            }
        }
    }

    /**
     * Update module config file to toggle enabled status
     */
    private function updateModuleConfig(ModuleManager $moduleManager): void
    {
        $frontendPath = config('app.frontend_path');
        $configPath = "{$frontendPath}/src/app/pages/{$moduleManager->module_name}/{$moduleManager->module_name}.config.ts";

        if (File::exists($configPath)) {
            $content = File::get($configPath);
            $content = preg_replace(
                '/enabled:\s*(true|false)/',
                'enabled: ' . ($moduleManager->enabled ? 'true' : 'false'),
                $content
            );
            File::put($configPath, $content);
        }
    }

    /**
     * Execute the module update script
     */
    private function executeUpdateScript(ModuleManager $moduleManager, ?string $oldModuleName = null): void
    {
        $frontendPath = config('app.frontend_path');
        $scriptPath = "{$frontendPath}/scripts/update-module-api.js";

        \Log::info('Starting module update', [
            'old_module' => $oldModuleName,
            'new_module' => $moduleManager->module_name,
            'script_path' => $scriptPath,
        ]);

        // Verify script exists
        if (! File::exists($scriptPath)) {
            \Log::error('Update script file does not exist', ['path' => $scriptPath]);
            throw new \Exception("Update script file does not exist: {$scriptPath}");
        }

        // Create a temporary JSON file with module data
        $tempFile = storage_path('app/temp_update_module_' . $moduleManager->id . '.json');
        $data = [
            'oldModuleName' => $oldModuleName ?? $moduleManager->module_name,
            'moduleName' => $moduleManager->module_name,
            'displayName' => $moduleManager->display_name,
            'displayNameSingular' => $moduleManager->display_name_singular,
            'resourceType' => $moduleManager->resource_type,
            'identifierField' => $moduleManager->identifier_field,
            'identifierType' => $moduleManager->identifier_type,
            'requiresAuth' => $moduleManager->requires_auth,
            'routePath' => $moduleManager->route_path,
            'oldRoutePath' => $this->oldRoutePath ?? ($oldModuleName ?? $moduleManager->module_name),
            'fields' => $moduleManager->fields,
            'devMode' => $moduleManager->dev_mode,
        ];

        File::put($tempFile, json_encode($data, JSON_PRETTY_PRINT));
        \Log::info('Temporary update file created', ['file' => $tempFile]);

        try {
            // Execute Node.js script using configured node path
            $nodePath = config('app.node_path');
            $result = Process::path($frontendPath)->run("\"{$nodePath}\" \"{$scriptPath}\" \"{$tempFile}\"");

            \Log::info('Update script execution completed', [
                'exit_code' => $result->exitCode(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ]);

            if (! $result->successful()) {
                \Log::error('Update script execution failed', [
                    'exit_code' => $result->exitCode(),
                    'error' => $result->errorOutput(),
                ]);
                throw new \Exception('Module update script failed: ' . $result->errorOutput());
            }
        } finally {
            // Clean up temporary file
            if (File::exists($tempFile)) {
                File::delete($tempFile);
                \Log::info('Temporary update file deleted', ['file' => $tempFile]);
            }
        }
    }

    /**
     * After store hook
     */
    protected function afterStore($request, $moduleManager): void
    {
        \Log::info("Module created: {$moduleManager->id}");
    }

    /**
     * Before update hook
     */
    protected function beforeUpdate($request, $moduleManager): void
    {
        // Get the current module from database before changes
        $currentModule = ModuleManager::find($moduleManager->id);
        if ($currentModule) {
            $this->oldModuleName = $currentModule->module_name;
            $this->oldModuleFields = $currentModule->fields;
            $this->oldRoutePath = $currentModule->route_path;
            \Log::info('Storing old module data from database', [
                'old_name' => $this->oldModuleName,
                'old_route_path' => $this->oldRoutePath,
                'old_fields_count' => count($this->oldModuleFields),
            ]);

            // Note: route_path will be auto-updated in afterUpdate hook if needed
        } else {
            $this->oldModuleName = $moduleManager->module_name;
            $this->oldModuleFields = [];
            $this->oldRoutePath = null;
            \Log::info("Storing old module name from parameter: {$this->oldModuleName}");
        }
    }

    /**
     * After update hook
     */
    protected function afterUpdate($request, $moduleManager): void
    {
        try {
            // Refresh the model to get the latest data from database
            $moduleManager->refresh();

            // ALWAYS synchronize route_path with module_name when module_name changes
            // unless route_path was explicitly provided in the request
            if ($this->oldModuleName !== $moduleManager->module_name) {
                // Check if route_path was explicitly provided in the request
                $routePathProvidedInRequest = $request->has('route_path') &&
                    $request->input('route_path') !== $this->oldRoutePath &&
                    $request->input('route_path') !== $this->oldModuleName;

                if (! $routePathProvidedInRequest) {
                    // Auto-sync route_path to match module_name
                    $newRoutePath = $moduleManager->module_name;

                    // Direct database update to bypass Orion
                    ModuleManager::where('id', $moduleManager->id)
                        ->update(['route_path' => $newRoutePath]);

                    // Refresh to get the updated value
                    $moduleManager->refresh();

                    \Log::info('Auto-synchronized route_path with module_name', [
                        'old_module_name' => $this->oldModuleName,
                        'new_module_name' => $moduleManager->module_name,
                        'old_route_path' => $this->oldRoutePath,
                        'new_route_path' => $newRoutePath,
                        'reason' => 'route_path not explicitly customized in request',
                    ]);
                } else {
                    \Log::info('Keeping custom route_path from request', [
                        'custom_route_path' => $request->input('route_path'),
                    ]);
                }
            }

            \Log::info('Module data after save', [
                'module_name' => $moduleManager->module_name,
                'route_path' => $moduleManager->route_path,
                'old_module_name' => $this->oldModuleName,
                'old_route_path' => $this->oldRoutePath,
            ]);

            // Update frontend files
            $this->executeUpdateScript($moduleManager, $this->oldModuleName);

            // Check if backend needs regeneration
            $nameChanged = $this->oldModuleName !== $moduleManager->module_name;
            $fieldsChanged = json_encode($this->oldModuleFields) !== json_encode($moduleManager->fields);

            if ($nameChanged || $fieldsChanged) {
                \Log::info('Module structure changed, regenerating backend', [
                    'name_changed' => $nameChanged,
                    'fields_changed' => $fieldsChanged,
                    'old_name' => $this->oldModuleName,
                    'new_name' => $moduleManager->module_name,
                ]);

                // Save seeder content before deletion
                $oldSingularName = \Str::singular($this->oldModuleName);
                $oldStudlySingular = \Str::studly($oldSingularName);
                $oldSeederPath = database_path("seeders/{$oldStudlySingular}Seeder.php");
                $savedSeederContent = null;

                if (File::exists($oldSeederPath)) {
                    $savedSeederContent = File::get($oldSeederPath);
                    \Log::info('Seeder content saved for preservation', [
                        'seeder' => $oldSeederPath,
                        'size' => strlen($savedSeederContent),
                    ]);
                }

                // Delete old backend module
                $oldModuleForDeletion = new ModuleManager;
                $oldModuleForDeletion->module_name = $this->oldModuleName;
                $this->deleteBackendModule($oldModuleForDeletion);

                // Generate new backend module
                $generator = new \App\Services\BackendModuleGenerator(
                    $moduleManager->module_name,
                    $moduleManager->fields,
                    $moduleManager->identifier_field
                );
                $result = $generator->generate();

                if (! $result['success']) {
                    throw new \Exception('Backend regeneration failed: ' . $result['message']);
                }

                // Restore seeder content if it was saved
                if ($savedSeederContent !== null) {
                    $newSingularName = \Str::singular($moduleManager->module_name);
                    $newStudlySingular = \Str::studly($newSingularName);
                    $newSeederPath = database_path("seeders/{$newStudlySingular}Seeder.php");

                    // Update class name in seeder if module name changed
                    if ($nameChanged) {
                        $savedSeederContent = str_replace(
                            "class {$oldStudlySingular}Seeder",
                            "class {$newStudlySingular}Seeder",
                            $savedSeederContent
                        );
                        $savedSeederContent = str_replace(
                            "use App\Models\\{$oldStudlySingular};",
                            "use App\Models\\{$newStudlySingular};",
                            $savedSeederContent
                        );
                        $savedSeederContent = str_replace(
                            "{$oldStudlySingular}::",
                            "{$newStudlySingular}::",
                            $savedSeederContent
                        );
                    }

                    File::put($newSeederPath, $savedSeederContent);
                    \Log::info('Seeder content restored successfully', [
                        'new_seeder' => $newSeederPath,
                    ]);
                }

                \Log::info('Backend module regenerated successfully');
            }

            // Regenerate module seeder after update
            $this->regenerateModuleSeeder();

            \Log::info("Module updated successfully: {$moduleManager->id}");
        } catch (\Exception $e) {
            \Log::error('Module update failed', [
                'module_id' => $moduleManager->id,
                'error' => $e->getMessage(),
            ]);
            // Continue even if script fails
        }
    }


    /**
     * Before destroy hook
     */
    protected function beforeDestroy($request, $moduleManager): void
    {
        try {
            // Delete frontend files
            $this->executeDeletionScript($moduleManager);
            
            // Push changes (deletion) to GitHub
            $this->pushFrontendChanges($moduleManager, 'Delete');

            // Delete backend files
            $this->deleteBackendModule($moduleManager);

            \Log::info("Module deleted successfully: {$moduleManager->id}");
        } catch (\Exception $e) {
            \Log::error('Module deletion failed', [
                'module_id' => $moduleManager->id,
                'error' => $e->getMessage(),
            ]);
            // Continue with deletion even if script fails
        }
    }

    /**
     * After destroy hook - Regenerate module seeder
     */
    protected function afterDestroy($request, $moduleManager): void
    {
        // Regenerate module seeder after deletion
        $this->regenerateModuleSeeder();
    }

    /**
     * Execute the module deletion script
     */
    private function executeDeletionScript(ModuleManager $moduleManager): void
    {
        $frontendPath = config('app.frontend_path');
        $scriptPath = "{$frontendPath}/scripts/delete-module-api.js";

        \Log::info('Starting module deletion', [
            'module' => $moduleManager->module_name,
            'script_path' => $scriptPath,
        ]);

        // Verify script exists, create if missing
        if (! File::exists($scriptPath)) {
            \Log::warning('Deletion script file does not exist, creating it...', ['path' => $scriptPath]);
            
            $scriptContent = <<<JS
const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
const tempFile = args[0];

if (!tempFile || !fs.existsSync(tempFile)) {
    console.error('Temporary file not found');
    process.exit(1);
}

const data = JSON.parse(fs.readFileSync(tempFile, 'utf8'));
const moduleName = data.moduleName;

// Define paths
const projectRoot = path.resolve(__dirname, '..');
const modulePath = path.join(projectRoot, 'src/app/pages', moduleName);

console.log(`Deleting module directory: \${modulePath}`);

if (fs.existsSync(modulePath)) {
    fs.rmSync(modulePath, { recursive: true, force: true });
    console.log('Module directory deleted successfully');
} else {
    console.log('Module directory does not exist, skipping');
}
JS;
            File::put($scriptPath, $scriptContent);
            
            // Verify creation
             if (! File::exists($scriptPath)) {
                throw new \Exception("Failed to create deletion script at: {$scriptPath}");
            }
        }

        // Create a temporary JSON file with module data
        $tempFile = storage_path('app/temp_delete_module_' . $moduleManager->id . '.json');
        $data = [
            'moduleName' => $moduleManager->module_name,
            'routePath' => $moduleManager->route_path,
        ];

        File::put($tempFile, json_encode($data, JSON_PRETTY_PRINT));
        \Log::info('Temporary deletion file created', ['file' => $tempFile]);

        try {
            // Execute Node.js script using configured node path
            $nodePath = config('app.node_path');
            $result = Process::path($frontendPath)->run("\"{$nodePath}\" \"{$scriptPath}\" \"{$tempFile}\"");

            \Log::info('Deletion script execution completed', [
                'exit_code' => $result->exitCode(),
                'output' => $result->output(),
                'error' => $result->errorOutput(),
            ]);

            if (! $result->successful()) {
                \Log::error('Deletion script execution failed', [
                    'exit_code' => $result->exitCode(),
                    'error' => $result->errorOutput(),
                ]);
                throw new \Exception('Module deletion script failed: ' . $result->errorOutput());
            }
        } finally {
            // Clean up temporary file
            if (File::exists($tempFile)) {
                File::delete($tempFile);
                \Log::info('Temporary deletion file deleted', ['file' => $tempFile]);
            }
        }
    }

    /**
     * Generate backend Laravel files for the module
     */
    private function generateBackendModule(ModuleManager $moduleManager, array $roles = ['user']): array
    {
        \Log::info('Starting backend generation', [
            'module' => $moduleManager->module_name,
            'fields' => $moduleManager->fields,
            'roles' => $roles,
        ]);

        try {
            $generator = new BackendModuleGenerator(
                $moduleManager->module_name,
                $moduleManager->fields,
                $moduleManager->identifier_field,
                $roles
            );

            $result = $generator->generate();

            \Log::info('Backend generation completed', $result);

            return $result;
        } catch (\Exception $e) {
            \Log::error('Backend generation failed', [
                'module' => $moduleManager->module_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Backend generation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete backend Laravel files for the module
     */
    private function deleteBackendModule(ModuleManager $moduleManager): void
    {
        \Log::info('Starting backend deletion', [
            'module' => $moduleManager->module_name,
        ]);

        $singularName = \Str::singular($moduleManager->module_name);
        $studlyName = \Str::studly($moduleManager->module_name);
        $studlySingular = \Str::studly($singularName);

        // First, clean up media records BEFORE deleting the model class file
        try {
            $modelClass = "App\\Models\\{$studlySingular}";
            if (class_exists($modelClass)) {
                $mediaCount = \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', $modelClass)->count();
                if ($mediaCount > 0) {
                    \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', $modelClass)->delete();
                    \Log::info("Deleted {$mediaCount} media records for model: {$modelClass}");
                }
            }
        } catch (\Exception $e) {
            \Log::error("Failed to delete media records for model: {$studlySingular}", [
                'error' => $e->getMessage(),
            ]);
        }

        $filesToDelete = [
            app_path("Http/Controllers/{$studlySingular}Controller.php"),
            app_path("Models/{$studlySingular}.php"),
            app_path("Http/Resources/{$studlySingular}Resource.php"),
            app_path("Http/Resources/Collections/{$studlySingular}Collection.php"),
            app_path("Http/Requests/{$studlySingular}Request.php"),
            app_path("Policies/{$studlySingular}Policy.php"),
            app_path("Jobs/Process{$studlySingular}Uploads.php"),
            database_path("factories/{$studlySingular}Factory.php"),
            database_path("seeders/{$studlySingular}Seeder.php"),
            database_path("seeders/{$studlySingular}MenuSeeder.php"),
            database_path("seeders/lang/{$moduleManager->module_name}.json"),
        ];

        $deletedCount = 0;

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                File::delete($file);
                \Log::info("Deleted backend file: {$file}");
                $deletedCount++;
            }
        }

        // Delete migrations
        $this->deleteMigrations($moduleManager->module_name);

        // Remove route from api.php
        $this->removeBackendRoute($moduleManager->module_name, $studlySingular);

        // Delete module disk and storage directory
        \App\Services\BackendModuleGenerator::deleteModuleDisk($moduleManager->module_name);

        // Delete menu entry
        \App\Models\Menu::where('name', $studlyName)->orWhere('route', strtolower($moduleManager->module_name))->delete();
        \Log::info('Deleted menu entry for module', ['module' => $moduleManager->module_name]);

        // Remove seeders from DatabaseSeeder
        \App\Services\BackendModuleGenerator::removeSeedersFromDatabaseSeeder($moduleManager->module_name);

        \Log::info('Backend deletion completed', [
            'files_deleted' => $deletedCount,
        ]);
    }

    /**
     * Remove route from api.php
     */
    private function removeBackendRoute(string $moduleName, string $controllerName): void
    {
        $routesPath = base_path('routes/api.php');

        if (! File::exists($routesPath)) {
            return;
        }

        $content = File::get($routesPath);
        $originalContent = $content;

        // Remove the Orion resource route line (with or without indentation)
        $routePatterns = [
            "Orion::resource('{$moduleName}', {$controllerName}Controller::class);",
            "    Orion::resource('{$moduleName}', {$controllerName}Controller::class);",
        ];

        foreach ($routePatterns as $pattern) {
            $content = str_replace($pattern, '', $content);
            $content = str_replace("\n{$pattern}", '', $content);
        }

        // Remove the media deletion route (with or without indentation)
        $mediaDeletePatterns = [
            "Route::delete('{$moduleName}/{resourceId}/media/{mediaId}', [{$controllerName}Controller::class, 'deleteMedia']);",
            "    Route::delete('{$moduleName}/{resourceId}/media/{mediaId}', [{$controllerName}Controller::class, 'deleteMedia']);",
        ];

        foreach ($mediaDeletePatterns as $pattern) {
            $content = str_replace($pattern, '', $content);
            $content = str_replace("\n{$pattern}", '', $content);
        }

        // Remove the import line
        $importLine = "use App\Http\Controllers\\{$controllerName}Controller;";
        $content = str_replace($importLine, '', $content);
        $content = str_replace("\n{$importLine}", '', $content);

        // Clean up extra newlines
        $content = preg_replace("/\n\n\n+/", "\n\n", $content);
        $content = preg_replace("/\n\n\}/", "\n}", $content);

        if ($content !== $originalContent) {
            File::put($routesPath, $content);
            \Log::info('Removed routes and import from api.php', [
                'module' => $moduleName,
                'routes_removed' => ['orion_resource', 'media_delete']
            ]);
        } else {
            \Log::warning('Route not found in api.php', [
                'module' => $moduleName,
                'controller' => $controllerName,
            ]);
        }
    }

    /**
     * Delete migrations for the module
     */
    private function deleteMigrations(string $moduleName): void
    {
        $migrationsPath = database_path('migrations');
        $tableName = \Str::snake($moduleName);

        // Drop the table if it exists
        try {
            if (\Schema::hasTable($tableName)) {
                \Schema::dropIfExists($tableName);
                \Log::info("Dropped table: {$tableName}");
            }
        } catch (\Exception $e) {
            \Log::error("Failed to drop table: {$tableName}", [
                'error' => $e->getMessage(),
            ]);
        }

        // Find migrations that match the module name
        $migrations = File::glob("{$migrationsPath}/*_create_{$tableName}_table.php");

        $deletedCount = 0;

        foreach ($migrations as $migration) {
            if (File::exists($migration)) {
                File::delete($migration);
                \Log::info("Deleted migration: {$migration}");
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            \Log::info("Deleted {$deletedCount} migration(s) for module: {$moduleName}");
        } else {
            \Log::warning("No migrations found for module: {$moduleName}");
        }
    }

    /**
     * Generate translations using Ollama service (Job-based)
     *
     * @param Request $request
     * @return array|null
     */
    protected function generateTranslations(Request $request): ?array
    {
        try {
            $translationService = app(OllamaTranslationService::class);

            // Check if Ollama is available
            if (!$translationService->isAvailable()) {
                \Log::warning('Ollama translation service not available, skipping auto-translation');
                return null;
            }

            $moduleName = $request->input('moduleName');
            $displayName = $request->input('displayName');
            $displayNameSingular = $request->input('displayNameSingular');
            $fields = $request->input('fields', []);

            $textsToTranslate = [];

            // Add display names
            $textsToTranslate[] = $displayName;
            $textsToTranslate[] = $displayNameSingular;

            // Add field labels and placeholders
            foreach ($fields as $field) {
                if (isset($field['label'])) {
                    $textsToTranslate[] = $field['label'];
                }
                if (isset($field['placeholder'])) {
                    $textsToTranslate[] = $field['placeholder'];
                }
            }

            // Remove duplicates
            $textsToTranslate = array_unique($textsToTranslate);
            $textsToTranslate = array_values($textsToTranslate); // Re-index array

            // Create default translation structure (English only)
            $translations = [
                'en' => [],
                'fr' => [],
                'pt' => [],
            ];

            // Fill English translations
            foreach ($textsToTranslate as $text) {
                $translations['en'][$text] = $text;
                // Fill with English text as fallback for other languages
                $translations['fr'][$text] = $text;
                $translations['pt'][$text] = $text;
            }

            // Save default translation JSON to temporary storage
            $translationFilePath = "temp_translations/{$moduleName}_" . time() . ".json";
            $translationData = [
                'module' => $moduleName,
                'texts' => $textsToTranslate,
                'translations' => $translations,
                'created_at' => now()->toISOString(),
                'status' => 'pending',
            ];

            \Storage::disk('local')->put(
                $translationFilePath,
                json_encode($translationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            // Create status file
            $statusFilePath = str_replace('.json', '_status.json', $translationFilePath);
            $statusData = [
                'module' => $moduleName,
                'status' => 'pending',
                'message' => 'Translation job queued',
                'progress' => 0,
                'created_at' => now()->toISOString(),
            ];

            \Storage::disk('local')->put(
                $statusFilePath,
                json_encode($statusData, JSON_PRETTY_PRINT)
            );

            \Log::info('Translation files created, dispatching job', [
                'module' => $moduleName,
                'file' => $translationFilePath,
                'texts_count' => count($textsToTranslate),
            ]);

            // Dispatch translation job with current user ID for notification
            \App\Jobs\TranslateModuleJob::dispatch(
                $moduleName,
                $translationFilePath,
                ['fr', 'pt'],
                auth()->id()
            );

            // Return default translations (will be updated by job)
            return $translations;
        } catch (\Exception $e) {
            \Log::error('Failed to generate translations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Regenerate ModuleManagerSeeder with current database state
     * This ensures modules persist after database refresh
     */
    private function regenerateModuleSeeder(): void
    {
        try {
            \Artisan::call('generate:module-seeder');
            \Log::info('ModuleManagerSeeder regenerated successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to regenerate ModuleManagerSeeder', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle Git operations (branch creation, commit, push)
     *
     * @param array $gitConfig
     * @param ModuleManager $moduleManager
     * @return array
     */
    private function handleGitOperations(array $gitConfig, ModuleManager $moduleManager): array
    {
        try {
            // Check if repositorySlug is provided
            if (!isset($gitConfig['repositorySlug']) || empty($gitConfig['repositorySlug'])) {
                return [
                    'success' => false,
                    'message' => 'Repository slug is required for GitHub operations'
                ];
            }

            $repositorySlug = $gitConfig['repositorySlug'];
            $branchName = $gitConfig['branchName'] ?? 'module/' . $moduleManager->module_name;
            $commitMessage = $gitConfig['commitMessage'] ?? 'feat: Initialize ' . $moduleManager->display_name . ' module

Generated files:
- Model, Controller, Request, Resource, Collection
- Migration, Factory, Seeder
- Translations and Menu configuration

Co-Authored-By: Resurex Module Generator <noreply@resurex.com>';

            $createBranch = $gitConfig['createBranch'] ?? true;
            $sourceBranch = $gitConfig['sourceBranch'] ?? null;

            \Log::info('Starting GitHub API operations', [
                'repository' => $repositorySlug,
                'branch' => $branchName,
                'create_branch' => $createBranch
            ]);

            // Use ModuleGitService to push files
            $gitService = new \App\Services\ModuleGitService();

            // Get the generated backend files
            $backendPath = base_path();
            $generatedFiles = [];

            // Read Model file
            $modelPath = app_path("Models/" . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($moduleManager->module_name)) . ".php");
            if (file_exists($modelPath)) {
                $generatedFiles[str_replace($backendPath . DIRECTORY_SEPARATOR, '', $modelPath)] = file_get_contents($modelPath);
            }

            // Read Controller file
            $controllerPath = app_path("Http/Controllers/" . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($moduleManager->module_name)) . "Controller.php");
            if (file_exists($controllerPath)) {
                $generatedFiles[str_replace($backendPath . DIRECTORY_SEPARATOR, '', $controllerPath)] = file_get_contents($controllerPath);
            }

            // Read Resource file
            $resourcePath = app_path("Http/Resources/" . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($moduleManager->module_name)) . "Resource.php");
            if (file_exists($resourcePath)) {
                $generatedFiles[str_replace($backendPath . DIRECTORY_SEPARATOR, '', $resourcePath)] = file_get_contents($resourcePath);
            }

            // Read Collection file
            $collectionPath = app_path("Http/Resources/Collections/" . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($moduleManager->module_name)) . "Collection.php");
            if (file_exists($collectionPath)) {
                $generatedFiles[str_replace($backendPath . DIRECTORY_SEPARATOR, '', $collectionPath)] = file_get_contents($collectionPath);
            }

            // Read Request file
            $requestPath = app_path("Http/Requests/" . \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($moduleManager->module_name)) . "Request.php");
            if (file_exists($requestPath)) {
                $generatedFiles[str_replace($backendPath . DIRECTORY_SEPARATOR, '', $requestPath)] = file_get_contents($requestPath);
            }

            // Add README for the module
            $readme = $gitService->generateModuleReadme($moduleManager->module_name);
            $generatedFiles["docs/modules/{$moduleManager->module_name}/README.md"] = $readme;

            // Convert paths to use forward slashes for GitHub
            $filesForGit = [];
            foreach ($generatedFiles as $path => $content) {
                $filesForGit[str_replace('\\', '/', $path)] = $content;
            }

            // Push to GitHub
            $result = $gitService->pushModuleFiles(
                $repositorySlug,
                $branchName,
                $filesForGit,
                $commitMessage,
                $createBranch,
                $sourceBranch
            );

            if ($result['success']) {
                // Update module manager with GitHub information
                $moduleManager->update([
                    'github_repository_slug' => $repositorySlug,
                    'github_branch' => $branchName,
                    'github_commit_sha' => $result['commit_sha'] ?? null,
                    'github_pushed_at' => now(),
                ]);

                // Create deployment record to track the deployment status
                $deployment = Deployment::create([
                    'user_id' => auth()->id(),
                    'module_manager_id' => $moduleManager->id,
                    'module_slug' => $moduleManager->slug,
                    'branch_name' => $branchName,
                    'status' => Deployment::STATUS_PENDING,
                    'message' => 'Git push successful, waiting for Dokploy deployment...',
                    'started_at' => now(),
                ]);

                // Broadcast initial deployment status to frontend via WebSocket
                event(DeploymentStatusUpdated::fromDeployment($deployment));

                // Add deployment info to result
                $result['deployment_triggered'] = true;
                $result['deployment_id'] = $deployment->id;

                \Log::info('Deployment tracking created', [
                    'deployment_id' => $deployment->id,
                    'module_slug' => $moduleManager->slug,
                    'branch' => $branchName,
                    'user_id' => auth()->id()
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            \Log::error('GitHub operations failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'GitHub operations failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute Git commands for a specific repository
     *
     * @param string $repoPath
     * @param string $branchName
     * @param string $commitMessage
     * @param string $repoType
     * @return array
     */
    private function executeGitCommands(string $repoPath, string $branchName, string $commitMessage, string $repoType): array
    {
        try {
            if (!file_exists($repoPath)) {
                throw new \Exception("Repository path does not exist: $repoPath");
            }

            $commands = [
                // Check if branch already exists
                "cd \"$repoPath\" && git rev-parse --verify $branchName 2>/dev/null",
            ];

            // Check if branch exists
            $branchExists = false;
            exec($commands[0], $output, $returnCode);
            $branchExists = ($returnCode === 0);

            if (!$branchExists) {
                // Create new branch from current branch
                $createBranchCmd = "cd \"$repoPath\" && git checkout -b $branchName 2>&1";
                exec($createBranchCmd, $createOutput, $createReturnCode);

                \Log::info("$repoType: Branch creation", [
                    'command' => $createBranchCmd,
                    'output' => $createOutput,
                    'return_code' => $createReturnCode
                ]);

                if ($createReturnCode !== 0) {
                    throw new \Exception("Failed to create branch: " . implode("\n", $createOutput));
                }
            } else {
                // Checkout existing branch
                $checkoutCmd = "cd \"$repoPath\" && git checkout $branchName 2>&1";
                exec($checkoutCmd, $checkoutOutput, $checkoutReturnCode);

                \Log::info("$repoType: Branch checkout", [
                    'command' => $checkoutCmd,
                    'output' => $checkoutOutput
                ]);
            }

            // Add all changes
            $addCmd = "cd \"$repoPath\" && git add . 2>&1";
            exec($addCmd, $addOutput, $addReturnCode);

            \Log::info("$repoType: Git add", [
                'output' => $addOutput
            ]);

            // Check if there are changes to commit
            $statusCmd = "cd \"$repoPath\" && git status --porcelain 2>&1";
            exec($statusCmd, $statusOutput, $statusReturnCode);

            if (empty($statusOutput)) {
                \Log::info("$repoType: No changes to commit");
                return [
                    'success' => true,
                    'message' => 'No changes to commit',
                    'skipped' => true
                ];
            }

            // Commit changes
            $commitCmd = "cd \"$repoPath\" && git commit -m " . escapeshellarg($commitMessage) . " 2>&1";
            exec($commitCmd, $commitOutput, $commitReturnCode);

            \Log::info("$repoType: Git commit", [
                'command' => $commitCmd,
                'output' => $commitOutput,
                'return_code' => $commitReturnCode
            ]);

            if ($commitReturnCode !== 0 && !str_contains(implode("\n", $commitOutput), 'nothing to commit')) {
                throw new \Exception("Failed to commit: " . implode("\n", $commitOutput));
            }

            // Push to reviewcode branch to trigger CI/CD
            $pushCmd = "cd \"$repoPath\" && git push origin $branchName:reviewcode --force 2>&1";
            exec($pushCmd, $pushOutput, $pushReturnCode);

            \Log::info("$repoType: Git push to reviewcode", [
                'command' => $pushCmd,
                'output' => $pushOutput,
                'return_code' => $pushReturnCode
            ]);

            if ($pushReturnCode !== 0) {
                throw new \Exception("Failed to push: " . implode("\n", $pushOutput));
            }

            return [
                'success' => true,
                'message' => 'Git operations completed successfully',
                'branch' => $branchName,
                'commit' => $commitMessage,
                'outputs' => [
                    'add' => $addOutput,
                    'commit' => $commitOutput,
                    'push' => $pushOutput
                ]
            ];

        } catch (\Exception $e) {
            \Log::error("$repoType: Git command failed", [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    /**
     * Push frontend changes to GitHub
     */
    private function pushFrontendChanges(ModuleManager $moduleManager, string $action = 'Generate'): void
    {
        $frontendPath = config('app.frontend_path');

        if (! File::exists($frontendPath)) {
            \Log::warning('Frontend path does not exist, skipping push', ['path' => $frontendPath]);
            return;
        }

        try {
            // Configure Git user
            Process::path($frontendPath)->run('git config user.email "bot@resurex.com"');
            Process::path($frontendPath)->run('git config user.name "Resurex Bot"');

            // Add all changes
            Process::path($frontendPath)->run('git add .');

            // Check if there are changes to commit
            $status = Process::path($frontendPath)->run('git status --porcelain');
            
            if (empty($status->output())) {
                \Log::info('No frontend changes to commit');
                return;
            }

            // Commit changes
            $commitMessage = "feat: {$action} frontend for module {$moduleManager->module_name}";
            Process::path($frontendPath)->run('git commit -m "' . $commitMessage . '"');

            // Push to main using explicit token to avoid credential issues
            // We get the repo URL from env or use the default one, but inject the token
            $token = config('services.github.token');
            $repoUrl = env('FRONTEND_REPO', 'https://github.com/smt197/resurex-frontend-automation.git');
            
            if ($token) {
                // Inject token into URL: https://TOKEN@github.com/...
                $authenticatedUrl = str_replace('https://', "https://{$token}@", $repoUrl);
                $result = Process::path($frontendPath)->run("git push \"{$authenticatedUrl}\" main");
            } else {
                // Fallback to origin if no token (unlikely to work if private)
                \Log::warning('GITHUB_TOKEN not found in config, falling back to origin');
                $result = Process::path($frontendPath)->run('git push origin main');
            }

            if (!$result->successful()) {
                // Mask token in error message
                $errorOutput = $result->errorOutput();
                if ($token) {
                    $errorOutput = str_replace($token, '***TOKEN***', $errorOutput);
                }
                throw new \Exception('Failed to push frontend changes: ' . $errorOutput);
            }

            \Log::info('Frontend changes pushed successfully', [
                'module' => $moduleManager->module_name,
                'output' => $result->output()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to push frontend changes', [
                'module' => $moduleManager->module_name,
                'error' => $e->getMessage()
            ]);
            // Don't throw exception to avoid failing the whole request
        }
    }
}
