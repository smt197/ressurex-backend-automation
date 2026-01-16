<?php

namespace App\Services;

use App\Models\GithubRepository;
use App\Models\GithubBranch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModuleGitService
{
    protected GithubApiService $githubApi;

    public function __construct()
    {
        $this->githubApi = new GithubApiService();
    }

    /**
     * Push module files to GitHub
     *
     * @param string $repositorySlug Repository slug
     * @param string $branchName Branch name to push to
     * @param array $files Array of files ['path' => 'content']
     * @param string $commitMessage Commit message
     * @param bool $createBranch Whether to create a new branch
     * @param string|null $sourceBranch Source branch for new branch
     * @return array Result with success status and message
     */
    public function pushModuleFiles(
        string $repositorySlug,
        string $branchName,
        array $files,
        string $commitMessage,
        bool $createBranch = false,
        ?string $sourceBranch = null
    ): array {
        try {
            // Get repository from database
            $repository = GithubRepository::where('slug', $repositorySlug)->firstOrFail();

            $owner = $repository->owner;
            $repoName = $repository->name;

            // If creating a new branch, create it first
            if ($createBranch) {
                $sourceBranch = $sourceBranch ?? $repository->default_branch;

                // Get source branch SHA
                $sourceBranchData = GithubBranch::where('github_repository_id', $repository->id)
                    ->where('name', $sourceBranch)
                    ->first();

                if (!$sourceBranchData) {
                    return [
                        'success' => false,
                        'message' => "Source branch '{$sourceBranch}' not found"
                    ];
                }

                // Create new branch on GitHub
                $branchResult = $this->githubApi->createBranch($owner, $repoName, $branchName, $sourceBranchData->commit_sha);

                if (!$branchResult) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create branch on GitHub'
                    ];
                }

                // Save branch to database
                // Save branch to database (update if exists)
                GithubBranch::updateOrCreate(
                    [
                        'name' => $branchName,
                        'github_repository_id' => $repository->id,
                    ],
                    [
                        'protected' => false,
                        'commit_sha' => $branchResult['object']['sha'] ?? $sourceBranchData->commit_sha,
                        'updated_at' => now(),
                    ]
                );

                Log::info("Created new branch: {$branchName}");
            }

            // Get current branch reference
            $refName = "heads/{$branchName}";
            $refData = $this->githubApi->getRef($owner, $repoName, $refName);

            if (!$refData) {
                return [
                    'success' => false,
                    'message' => "Branch '{$branchName}' not found"
                ];
            }

            $currentCommitSha = $refData['object']['sha'];

            // Get current commit to get tree SHA
            $currentCommit = $this->githubApi->getCommit($owner, $repoName, $currentCommitSha);

            if (!$currentCommit) {
                return [
                    'success' => false,
                    'message' => 'Failed to get current commit'
                ];
            }

            $baseTreeSha = $currentCommit['tree']['sha'];

            // Create blobs for all files
            $treeItems = [];
            foreach ($files as $path => $content) {
                $blob = $this->githubApi->createBlob($owner, $repoName, $content);

                if (!$blob) {
                    Log::error("Failed to create blob for file: {$path}");
                    continue;
                }

                $treeItems[] = [
                    'path' => $path,
                    'mode' => '100644', // Regular file
                    'type' => 'blob',
                    'sha' => $blob['sha']
                ];
            }

            if (empty($treeItems)) {
                return [
                    'success' => false,
                    'message' => 'No files were uploaded'
                ];
            }

            // Create new tree
            $tree = $this->githubApi->createTree($owner, $repoName, $treeItems, $baseTreeSha);

            if (!$tree) {
                return [
                    'success' => false,
                    'message' => 'Failed to create tree'
                ];
            }

            // Create commit
            $commit = $this->githubApi->createCommit(
                $owner,
                $repoName,
                $commitMessage,
                $tree['sha'],
                [$currentCommitSha]
            );

            if (!$commit) {
                return [
                    'success' => false,
                    'message' => 'Failed to create commit'
                ];
            }

            // Update branch reference
            $updateResult = $this->githubApi->updateRef($owner, $repoName, $refName, $commit['sha']);

            if (!$updateResult) {
                return [
                    'success' => false,
                    'message' => 'Failed to update branch reference'
                ];
            }

            // Update branch SHA in database
            $branchRecord = GithubBranch::where('github_repository_id', $repository->id)
                ->where('name', $branchName)
                ->first();

            if ($branchRecord) {
                $branchRecord->update([
                    'commit_sha' => $commit['sha'],
                    'commit_message' => $commitMessage,
                'commit_date' => now()
                ]);
            }

            // --- TRIGGER CI/CD ---
            // Push to main branch to trigger CI/CD (force update)
            // This replicates: git push origin $branchName:main --force
            $mainRef = "heads/main";
            $mainParams = [
                'sha' => $commit['sha'],
                'force' => true 
            ];
            
            // Try to update existing main branch
            $mainResult = $this->githubApi->updateRef($owner, $repoName, $mainRef, $commit['sha'], true);

            if (!$mainResult) {
                // If update failed, maybe it doesn't exist (unlikely for main but safe fallback), try creating it
                Log::info("Branch main not found, creating it...");
                $this->githubApi->createBranch($owner, $repoName, 'main', $commit['sha']);
            }

            Log::info("Triggered CI/CD by pushing to {$owner}/{$repoName}:main");
            // ---------------------

            Log::info("Successfully pushed module files to {$owner}/{$repoName}:{$branchName}", [
                'files_count' => count($files),
                'commit_sha' => $commit['sha']
            ]);

            return [
                'success' => true,
                'message' => 'Module files pushed successfully to GitHub',
                'commit_sha' => $commit['sha'],
                'commit_url' => $commit['html_url'] ?? null,
                'branch' => $branchName
            ];

        } catch (\Exception $e) {
            Log::error('Error pushing module files to GitHub: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error pushing to GitHub: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate module files content for Git
     *
     * @param array $generatedFiles Files generated by BackendModuleGenerator
     * @param string $moduleName Module name
     * @return array Files ready for Git ['relative/path' => 'content']
     */
    public function prepareModuleFilesForGit(array $generatedFiles, string $moduleName): array
    {
        $files = [];

        foreach ($generatedFiles as $type => $path) {
            if (is_string($path) && file_exists($path)) {
                // Convert absolute path to relative path from Laravel root
                $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
                $relativePath = str_replace('\\', '/', $relativePath);

                $content = file_get_contents($path);
                $files[$relativePath] = $content;
            }
        }

        // Add a README for the module
        $readme = $this->generateModuleReadme($moduleName);
        $files["docs/modules/{$moduleName}/README.md"] = $readme;

        Log::info("Prepared {count($files)} files for Git push", ['module' => $moduleName]);

        return $files;
    }

    /**
     * Generate README content for module (public for external use)
     */
    public function generateModuleReadme(string $moduleName): string
    {
        $studlyName = Str::studly(Str::singular($moduleName));
        $timestamp = now()->format('Y-m-d H:i:s');

        return "# {$studlyName} Module

## Overview
This module was automatically generated and pushed to GitHub.

**Generated at:** {$timestamp}
**Module Name:** {$moduleName}

## Files Included
- Model: `app/Models/{$studlyName}.php`
- Controller: `app/Http/Controllers/{$studlyName}Controller.php`
- Request: `app/Http/Requests/{$studlyName}Request.php`
- Resource: `app/Http/Resources/{$studlyName}Resource.php`
- Collection: `app/Http/Resources/Collections/{$studlyName}Collection.php`
- Policy: `app/Policies/{$studlyName}Policy.php`
- Migration: Database migration file
- Factory: `database/factories/{$studlyName}Factory.php`
- Seeder: `database/seeders/{$studlyName}Seeder.php`
- Menu Seeder: `database/seeders/{$studlyName}MenuSeeder.php`
- Translations: `database/seeders/lang/{$moduleName}.json`

## API Endpoints
- GET    `/api/{$moduleName}` - List all records
- POST   `/api/{$moduleName}` - Create new record
- GET    `/api/{$moduleName}/{id}` - Get single record
- PUT    `/api/{$moduleName}/{id}` - Update record
- DELETE `/api/{$moduleName}/{id}` - Delete record

## Notes
This module follows Laravel Orion REST conventions and includes complete CRUD operations.

---
Generated by Resurex Module Generator
";
    }
}
