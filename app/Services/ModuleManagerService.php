<?php

namespace App\Services;

use App\Http\Resources\ModuleManagerResource;
use App\Models\ModuleManager;
use App\Services\BackendModuleGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class ModuleManagerService
{
    /**
     * Generate a new module
     *
     * @param array $data Input data for module generation
     * @return array Result of the operation
     */
    public function createModule(array $data): array
    {
        try {
            // Auto-generate translations if not provided
            $translations = $data['translations'] ?? null;
            if (!$translations) {
                // Determine source for translation generation (simplified vs controller)
                // In service context, we might generate default translations or rely on what's passed
                // For now, let's assume if not passed, we generate empty or basic structure
                $translations = $this->generateDefaultTranslations($data);
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

            $actions = $data['actions'] ?? $defaultActions;

            $moduleManager = ModuleManager::create([
                'module_name' => $data['moduleName'],
                'display_name' => $data['displayName'],
                'display_name_singular' => $data['displayNameSingular'],
                'resource_type' => $data['resourceType'] ?? $data['moduleName'],
                'identifier_field' => $data['identifierField'] ?? 'id',
                'identifier_type' => $data['identifierType'] ?? 'number',
                'requires_auth' => $data['requiresAuth'] ?? true,
                'route_path' => $data['routePath'] ?? $data['moduleName'],
                'fields' => $data['fields'] ?? [],
                'enabled' => $data['enabled'] ?? true,
                'dev_mode' => $data['devMode'] ?? false,
                'roles' => $data['roles'] ?? ['user'],
                'translations' => $translations,
                'actions' => $actions,
            ]);

            Log::info('ModuleManager record created', ['id' => $moduleManager->id]);

            // Execute frontend generation script
            $this->executeGenerationScript($moduleManager);

            // Generate backend files automatically
            $backendResult = $this->generateBackendModule($moduleManager, $moduleManager->roles ?? ['user']);

            // Push generated frontend files to GitHub (Fail gracefully if token invalid)
            try {
               $this->pushFrontendChanges($moduleManager, $data['gitConfig'] ?? []);
            } catch (\Exception $e) {
                Log::error('Frontend push failed', ['error' => $e->getMessage()]);
            }

            if (!$backendResult['success']) {
                Log::warning('Backend generation completed with errors', $backendResult);
            } else {
                Log::info('Backend generation successful', ['files' => count($backendResult['generated_files'] ?? [])]);
            }

            // Regenerate module seeder
            $this->regenerateModuleSeeder();

            // Handle Git operations
            $gitResult = null;
            if (isset($data['gitConfig']) && !empty($data['gitConfig']['createBranch'])) {
                // We need to move handleGitOperations here too or pass a service
                $gitResult = $this->handleGitOperations($data['gitConfig'], $moduleManager);
            }

            return [
                'success' => true,
                'message' => 'Module generated successfully!' . ($gitResult ? ' Git branch created and pushed.' : ''),
                'data' => [
                    'module' => $moduleManager,
                    'module_slug' => $moduleManager->slug,
                    'branch_name' => $gitResult['branch'] ?? null,
                    'deployment_triggered' => $gitResult['deployment_triggered'] ?? false,
                    'deployment_id' => $gitResult['deployment_id'] ?? null,
                ],
                'backend' => $backendResult,
                'git' => $gitResult,
            ];

        } catch (\Exception $e) {
            Log::error('Module generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate default translations if none provided
     */
    private function generateDefaultTranslations(array $data): array
    {
        // Simple default generation
        $singular = $data['displayNameSingular'] ?? 'Item';
        $plural = $data['displayName'] ?? 'Items';
        
        return [
            'en' => [
                'title' => $plural,
                'subTitle' => "Manage your {$plural}",
                'add' => "Add {$singular}",
                'edit' => "Edit {$singular}",
                'delete' => "Delete {$singular}",
            ],
            'fr' => [
                'title' => $plural,
                'subTitle' => "Gérez vos {$plural}",
                'add' => "Ajouter {$singular}",
                'edit' => "Modifier {$singular}",
                'delete' => "Supprimer {$singular}",
            ]
        ];
    }

    /**
     * Execute the module generation script
     */
    private function executeGenerationScript(ModuleManager $moduleManager): void
    {
        $frontendPath = config('app.frontend_path');
        $scriptPath = "{$frontendPath}/scripts/generate-module-api.js";

        Log::info('Starting module generation', [
            'module' => $moduleManager->module_name,
            'frontend_path' => $frontendPath,
            'script_path' => $scriptPath,
        ]);

        if (!File::exists($frontendPath) || !File::exists($scriptPath)) {
            throw new \Exception("Frontend path or script does not exist.");
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

        try {
            $nodePath = config('app.node_path', 'node'); 
            $result = Process::path($frontendPath)->run("\"{$nodePath}\" \"{$scriptPath}\" \"{$tempFile}\"");

            if (!$result->successful()) {
                Log::error('Script execution failed', ['error' => $result->errorOutput()]);
                throw new \Exception('Module generation script failed: ' . $result->errorOutput());
            }
        } finally {
            if (File::exists($tempFile)) {
                File::delete($tempFile);
            }
        }
    }

    /**
     * Generate backend Laravel files for the module
     */
    private function generateBackendModule(ModuleManager $moduleManager, array $roles = ['user']): array
    {
        try {
            $generator = new BackendModuleGenerator(
                $moduleManager->module_name,
                $moduleManager->fields,
                $moduleManager->identifier_field,
                $roles
            );

            return $generator->generate();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Backend generation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Regenerate ModuleManagerSeeder with current database state
     * This ensures modules persist after database refresh
     */
    private function regenerateModuleSeeder(): void
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('generate:module-seeder');
            Log::info('ModuleManagerSeeder regenerated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to regenerate ModuleManagerSeeder', [
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
            $sourceBranch = $gitConfig['sourceBranch'] ?? 'mcp'; // Default to mcp for testing as requested

            Log::info('Starting GitHub API operations', [
                'repository' => $repositorySlug,
                'branch' => $branchName,
                'create_branch' => $createBranch,
                'source_branch' => $sourceBranch
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
                $deployment = \App\Models\Deployment::create([
                    'user_id' => auth()->id() ?? 1, // Default to 1 if no user for CLI/MCP
                    'module_manager_id' => $moduleManager->id,
                    'module_slug' => $moduleManager->slug,
                    'branch_name' => $branchName,
                    'status' => \App\Models\Deployment::STATUS_PENDING,
                    'message' => 'Git push successful, waiting for Dokploy deployment...',
                    'started_at' => now(),
                ]);

                // Broadcast initial deployment status to frontend via WebSocket
                try {
                    event(\App\Events\DeploymentStatusUpdated::fromDeployment($deployment));
                } catch (\Exception $e) {
                    Log::warning('Failed to broadcast deployment event: ' . $e->getMessage());
                }

                // Add deployment info to result
                $result['deployment_triggered'] = true;
                $result['deployment_id'] = $deployment->id;

                Log::info('Deployment tracking created', [
                    'deployment_id' => $deployment->id,
                    'module_slug' => $moduleManager->slug,
                    'branch' => $branchName,
                    'user_id' => auth()->id() ?? 1
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('GitHub operations failed', [
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
     * Push frontend changes to GitHub
     */
    private function pushFrontendChanges(ModuleManager $moduleManager, array $gitConfig = [], string $action = 'Generate'): void
    {
        $frontendPath = config('app.frontend_path');

        if (!File::exists($frontendPath)) {
            Log::warning('Frontend path does not exist, skipping push', ['path' => $frontendPath]);
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
                Log::info('No frontend changes to commit');
                return;
            }

            // Commit changes
            $commitMessage = "feat: {$action} frontend for module {$moduleManager->module_name}";
            Process::path($frontendPath)
                ->env(['GIT_TERMINAL_PROMPT' => '0'])
                ->run('git commit -m "' . $commitMessage . '"');

            // Push to main using explicit token to avoid credential issues
            // Strategy: Check Database FIRST (most reliable user override)
            $token = null;
            try {
                $settings = \App\Models\GithubSettingsModel::where('group', 'github')
                    ->where('name', 'github_token')
                    ->first();
                
                if ($settings && $settings->github_token) {
                    $token = $settings->github_token;
                    Log::info('GITHUB_TOKEN found in database settings');
                }
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve GITHUB_TOKEN from database: ' . $e->getMessage());
            }

            // Strategy: Read token from file (Docker/PHP isolation fallback)
            $tokenPath = storage_path('app/github_token.txt');
            
            if (empty($token) && File::exists($tokenPath)) {
                $token = trim(File::get($tokenPath));
            }

            // Fallbacks (just in case)
            if (empty($token)) $token = config('services.github.token');
            if (empty($token)) $token = env('GITHUB_TOKEN');
            if (empty($token)) $token = getenv('GITHUB_TOKEN');

            $branchName = $gitConfig['branchName'] ?? 'module/' . $moduleManager->module_name;
            $repoUrl = env('FRONTEND_REPO', 'https://github.com/smt197/resurex-frontend-automation.git');

            if ($token) {
                // Inject token into URL: https://TOKEN@github.com/...
                $authenticatedUrl = str_replace('https://', "https://{$token}@", $repoUrl);
                
                // Create or switch to branch
                Process::path($frontendPath)->run("git checkout -b {$branchName}");
                Process::path($frontendPath)->run("git checkout {$branchName}");
                
                $result = Process::path($frontendPath)->run("git push -u \"{$authenticatedUrl}\" {$branchName}");
            } else {
                // Fallback to origin if no token (unlikely to work if private)
                Log::warning('GITHUB_TOKEN not found in config, falling back to origin');
                
                // Create or switch to branch
                Process::path($frontendPath)->run("git checkout -b {$branchName}");
                Process::path($frontendPath)->run("git checkout {$branchName}");
                
                $result = Process::path($frontendPath)->run("git push -u origin {$branchName}");
            }

            if (!$result->successful()) {
                // Mask token in error message
                $errorOutput = $result->errorOutput();
                if ($token) {
                    $errorOutput = str_replace($token, '***TOKEN***', $errorOutput);
                }
                Log::error('Failed to push frontend changes', ['error' => $errorOutput]);
                // Don't throw for now from this method
            } else {
                 Log::info('Frontend changes pushed successfully', [
                    'module' => $moduleManager->module_name,
                    'output' => $result->output()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to push frontend changes', [
                'module' => $moduleManager->module_name,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // Helper to allow the controller to delegate
    public function getModuleBySlug(string $slug): ModuleManager
    {
        return ModuleManager::where('slug', $slug)->firstOrFail();
    }
}
