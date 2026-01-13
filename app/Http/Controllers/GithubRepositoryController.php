<?php

namespace App\Http\Controllers;

use App\Http\Requests\GithubRepositoryRequest;
use App\Http\Resources\GithubRepositoryCollection;
use App\Http\Resources\GithubRepositoryResource;
use App\Http\Resources\GithubBranchResource;
use App\Http\Resources\GithubBranchCollection;
use App\Models\GithubRepository;
use App\Models\GithubBranch;
use App\Services\GithubApiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Http\Controllers\Controller;

class GithubRepositoryController extends Controller
{
    
    protected $model = GithubRepository::class;

    protected $request = GithubRepositoryRequest::class;

    protected $resource = GithubRepositoryResource::class;

    protected $collectionResource = GithubRepositoryCollection::class;

    public function keyName(): string
    {
        return 'slug';
    }

    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    public function maxLimit(): int
    {
        return 1000;
    }

    public function searchableBy(): array
    {
        return ['name', 'full_name', 'owner', 'description'];
    }

    public function filterableBy(): array
    {
        // Note: is_owner est géré manuellement dans buildIndexFetchQuery
        // car Orion ne gère pas correctement les filtres boolean (traite "false" comme truthy)
        return ['private', 'visibility','name', 'owner'];
    }

    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        // Filtrer par utilisateur connecté
        $query->where('user_id', $request->user()->id);

        // Gérer le filtre is_owner correctement (boolean)
        // Orion traite les filtres comme des strings, donc "false" = truthy
        if ($request->has('filter.is_owner')) {
            $isOwnerFilter = $request->input('filter.is_owner');
            // Convertir la string en boolean
            $isOwner = filter_var($isOwnerFilter, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($isOwner !== null) {
                $query->where('is_owner', $isOwner);
            }
        }

        return $query;
    }

    protected function performStore(Request $request, $model, array $attributes): void
    {
        // Ajouter l'utilisateur connecté
        $attributes['user_id'] = $request->user()->id;

        // Créer le repository sur GitHub
        $githubApi = new GithubApiService();

        $githubData = [
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'private' => $attributes['private'] ?? false,
            'auto_init' => true, // Initialize with README
        ];

        $result = $githubApi->createRepositoryOnGithub($githubData);

        if (!$result) {
            throw new Exception(__('github.sync_error') . ' Failed to create repository on GitHub.');
        }

        // Ajouter les données retournées par GitHub
        $attributes['github_id'] = $result['id'];
        $attributes['full_name'] = $result['full_name'];
        $attributes['owner'] = $result['owner']['login'];
        $attributes['html_url'] = $result['html_url'];
        $attributes['default_branch'] = $result['default_branch'] ?? 'main';
        $attributes['visibility'] = $result['visibility'] ?? ($result['private'] ? 'private' : 'public');
        $attributes['is_owner'] = true;
        $attributes['last_synced_at'] = now();

        parent::performStore($request, $model, $attributes);

        // Synchroniser les branches après création
        if (isset($result['default_branch'])) {
            try {
                $this->syncRepositoryBranches($githubApi, $model, $result['owner']['login'], $result['name']);
            } catch (Exception $e) {
                Log::warning("Could not sync initial branches for {$result['full_name']}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get branches for a repository
     */
    public function getBranches(Request $request, string $slug): JsonResponse
    {
        try {
            $repository = GithubRepository::where('slug', $slug)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            // Récupérer les paramètres de pagination
            $perPage = $request->input('per_page', config('app.limit_pagination', 15));
            $page = $request->input('page', 1);

            // Paginer les branches
            $branches = $repository->branches()->paginate($perPage, ['*'], 'page', $page);

            return response()->json(
                (new GithubBranchCollection($branches))->toArray($request),
                200
            );
        } catch (\Exception $e) {
            Log::error('Error fetching branches: ' . $e->getMessage());
            return response()->json([
                'message' => __('github.branch_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new branch
     */
    public function createBranch(Request $request, string $slug): JsonResponse
    {
        try {
            $repository = GithubRepository::where('slug', $slug)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'source' => 'nullable|string|max:255',
            ]);

            // Vérifier si la branche existe déjà
            $existingBranch = GithubBranch::where('github_repository_id', $repository->id)
                ->where('name', $validated['name'])
                ->first();

            if ($existingBranch) {
                return response()->json([
                    'message' => __('github.branch_already_exists'),
                ], 409);
            };

            // Récupérer la branche source
            $sourceBranch = $validated['source'] ?? $repository->default_branch;
            $sourceBranchData = GithubBranch::where('github_repository_id', $repository->id)
                ->where('name', $sourceBranch)
                ->first();

            if (!$sourceBranchData) {
                return response()->json([
                    'message' => __('github.branch_not_found') . ' (source: ' . $sourceBranch . ')',
                ], 404);
            }

            // Créer la branche sur GitHub via l'API
            $githubApi = new GithubApiService();
            $result = $githubApi->createBranch(
                $repository->owner,
                $repository->name,
                $validated['name'],
                $sourceBranchData->commit_sha
            );

            if (!$result) {
                return response()->json([
                    'message' => __('github.branch_error') . ' Failed to create branch on GitHub.',
                ], 500);
            }

            $branch = GithubBranch::create([
                'name' => $validated['name'],
                'github_repository_id' => $repository->id,
                'protected' => false,
                'commit_sha' => $result['object']['sha'] ?? $sourceBranchData->commit_sha,
            ]);

            return response()->json([
                'message' => __('github.branch_created'),
                'data' => new GithubBranchResource($branch),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating branch: ' . $e->getMessage());
            return response()->json([
                'message' => __('github.branch_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a branch
     */
    public function deleteBranch(Request $request, string $slug, string $branchName): JsonResponse
    {
        try {
            $repository = GithubRepository::where('slug', $slug)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $branch = GithubBranch::where('github_repository_id', $repository->id)
                ->where('name', $branchName)
                ->firstOrFail();

            // Vérifier que ce n'est pas la branche par défaut
            if ($branch->name === $repository->default_branch) {
                return response()->json([
                    'message' => __('github.cannot_delete_default_branch'),
                ], 403);
            };

            // Supprimer la branche sur GitHub via l'API
            $githubApi = new GithubApiService();
            $success = $githubApi->deleteBranchOnGithub(
                $repository->owner,
                $repository->name,
                $branchName
            );

            if (!$success) {
                return response()->json([
                    'message' => __('github.branch_error') . ' Failed to delete branch on GitHub.',
                ], 500);
            }

            $branch->delete();

            return response()->json([
                'message' => __('github.branch_deleted'),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting branch: ' . $e->getMessage());
            return response()->json([
                'message' => __('github.branch_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Protect a branch
     */
    public function protectBranch(Request $request, string $slug, string $branchName): JsonResponse
    {
        try {
            $repository = GithubRepository::where('slug', $slug)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $branch = GithubBranch::where('github_repository_id', $repository->id)
                ->where('name', $branchName)
                ->firstOrFail();

            $branch->update(['protected' => true]);

            return response()->json([
                'message' => __('github.branch_protected'),
                'data' => new GithubBranchResource($branch),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error protecting branch: ' . $e->getMessage());
            return response()->json([
                'message' => __('github.branch_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unprotect a branch
     */
    public function unprotectBranch(Request $request, string $slug, string $branchName): JsonResponse
    {
        try {
            $repository = GithubRepository::where('slug', $slug)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $branch = GithubBranch::where('github_repository_id', $repository->id)
                ->where('name', $branchName)
                ->firstOrFail();

            $branch->update(['protected' => false]);

            return response()->json([
                'message' => __('github.branch_unprotected'),
                'data' => new GithubBranchResource($branch),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error unprotecting branch: ' . $e->getMessage());
            return response()->json([
                'message' => __('github.branch_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync repository with GitHub
     */
    public function syncRepository(Request $request, string $slug): JsonResponse
    {
        try {
            $repository = GithubRepository::where('slug', $slug)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            // Mettre à jour la date de dernière synchronisation
            $repository->update(['last_synced_at' => now()]);

            return response()->json([
                'message' => __('github.synced'),
                'data' => new GithubRepositoryResource($repository),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error syncing repository: ' . $e->getMessage());
            return response()->json([
                'message' => __('github.sync_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync all repositories from GitHub
     */
    public function syncFromGithub(Request $request): JsonResponse
    {
        try {
            $githubApi = new GithubApiService();

            // Vérifier si le token GitHub est configuré
            if (!$githubApi->hasToken()) {
                return response()->json([
                    'message' => __('github.test.token_missing'),
                    'synced' => 0,
                ], 400);
            }

            // Tester la connexion
            if (!$githubApi->testConnection()) {
                return response()->json([
                    'message' => __('github.test.connection_failed'),
                    'synced' => 0,
                ], 401);
            }

            $user = $request->user();
            $repositories = $githubApi->getUserRepositories();

            $syncedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;

            DB::beginTransaction();

            foreach ($repositories as $repoData) {
                try {
                    // Chercher si le repository existe déjà
                    $repository = GithubRepository::where('github_id', $repoData['id'])
                        ->where('user_id', $user->id)
                        ->first();

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

                    if ($repository) {
                        // Mettre à jour le repository existant
                        $repository->update($data);
                        $updatedCount++;
                    } else {
                        // Créer un nouveau repository
                        $repository = GithubRepository::create($data);
                        $syncedCount++;
                    }

                    // Synchroniser les branches
                    $this->syncRepositoryBranches($githubApi, $repository, $repoData['owner']['login'], $repoData['name']);

                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Error syncing repository {$repoData['full_name']}: " . $e->getMessage());
                    continue;
                }
            }

            DB::commit();

            $message = "Synchronization completed: {$syncedCount} new, {$updatedCount} updated";
            if ($errorCount > 0) {
                $message .= ", {$errorCount} errors";
            }

            return response()->json([
                'message' => $message,
                'synced' => $syncedCount,
                'updated' => $updatedCount,
                'errors' => $errorCount,
                'total' => count($repositories),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing from GitHub: ' . $e->getMessage());
            return response()->json([
                'message' => __('github.sync_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync branches for a repository
     */
    protected function syncRepositoryBranches(GithubApiService $githubApi, GithubRepository $repository, string $owner, string $repoName): void
    {
        try {
            $branches = $githubApi->getRepositoryBranches($owner, $repoName);

            foreach ($branches as $branchData) {
                try {
                    // Récupérer les détails du commit depuis les données de la branche
                    $commitData = $branchData['commit'] ?? null;
                    $commitSha = $commitData['sha'] ?? null;

                    // Utiliser le message et la date du commit depuis les données de la branche (plus rapide)
                    // GitHub API retourne déjà ces infos dans la liste des branches
                    $commitMessage = null;
                    $commitDate = null;

                    // Certaines branches ont déjà les infos de commit
                    if (isset($commitData['commit'])) {
                        $commitMessage = $commitData['commit']['message'] ?? null;
                        $commitDate = $commitData['commit']['author']['date'] ?? null;
                    }

                    // Vérifier si la branche est protégée (skip pour repos privés sans Pro)
                    // L'erreur 403 est maintenant gérée silencieusement
                    $protection = null;
                    if (!$repository->private) {
                        // Vérifier protection seulement pour repos publics
                        $protection = $githubApi->getBranchProtection($owner, $repoName, $branchData['name']);
                    }
                    $isProtected = $protection !== null;

                    // Chercher ou créer la branche
                    GithubBranch::updateOrCreate(
                        [
                            'github_repository_id' => $repository->id,
                            'name' => $branchData['name'],
                        ],
                        [
                            'protected' => $isProtected,
                            'commit_sha' => $commitSha,
                            'commit_message' => $commitMessage,
                            'commit_date' => $commitDate ? date('Y-m-d H:i:s', strtotime($commitDate)) : null,
                        ]
                    );

                } catch (\Exception $e) {
                    Log::error("Error syncing branch {$branchData['name']} for {$owner}/{$repoName}: " . $e->getMessage());
                    continue;
                }
            }

        } catch (\Exception $e) {
            Log::error("Error syncing branches for {$owner}/{$repoName}: " . $e->getMessage());
        }
    }
}
