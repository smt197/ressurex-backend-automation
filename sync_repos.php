<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GithubRepository;
use App\Models\GithubBranch;
use App\Services\GithubApiService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "Starting Manual Repo Sync...\n";

$user = User::first(); // Assuming ID 1 or first user
if (!$user) {
    echo "Error: No user found in database.\n";
    exit(1);
}

$githubApi = new GithubApiService();
if (!$githubApi->hasToken()) {
    echo "Error: GitHub token missing in settings.\n";
    exit(1);
}

try {
    $repositories = $githubApi->getUserRepositories();
    echo "Found " . count($repositories) . " repositories on GitHub.\n";

    $syncedCount = 0;
    
    foreach ($repositories as $repoData) {
        echo "Syncing {$repoData['full_name']}... ";
        
        $data = [
            'name' => $repoData['name'],
            'full_name' => $repoData['full_name'],
            'owner' => $repoData['owner']['login'],
            'description' => $repoData['description'],
            'html_url' => $repoData['html_url'],
            'default_branch' => $repoData['default_branch'] ?? 'main',
            'private' => $repoData['private'],
            'visibility' => $repoData['visibility'] ?? ($repoData['private'] ? 'private' : 'public'),
            'github_id' => $repoData['id'],
            'is_owner' => $repoData['is_owner'] ?? false,
            'last_synced_at' => now(),
            'user_id' => $user->id,
        ];

        $repository = GithubRepository::updateOrCreate(
            ['github_id' => $repoData['id'], 'user_id' => $user->id],
            $data
        );

        // Sync Branches
        $branches = $githubApi->getRepositoryBranches($repoData['owner']['login'], $repoData['name']);
        foreach ($branches as $branchData) {
            GithubBranch::updateOrCreate(
                [
                    'github_repository_id' => $repository->id,
                    'name' => $branchData['name'],
                ],
                [
                    'protected' => false,
                    'commit_sha' => $branchData['commit']['sha'] ?? null,
                    'updated_at' => now(),
                ]
            );
        }
        
        echo "Done (ID: {$repository->id}, Branches: " . count($branches) . ")\n";
        $syncedCount++;
    }

    echo "Successfully synced $syncedCount repositories.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
