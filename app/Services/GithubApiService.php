<?php

namespace App\Services;

use App\Settings\GithubSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubApiService
{
    protected string $baseUrl = 'https://api.github.com';
    protected ?string $token;

    public function __construct(?string $token = null)
    {
        if ($token) {
            $this->token = $token;
            return;
        }

        try {
            $githubSettings = app(GithubSettings::class);
            $this->token = $githubSettings->github_token ?? config('services.github.token');
        } catch (\Exception $e) {
            // Si les settings ne sont pas encore migrés, utiliser le config
            $this->token = config('services.github.token');
        }
    }

    /**
     * Get authenticated user information
     */
    public function getAuthenticatedUser(): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/user");

            if (!$response->successful()) {
                Log::error('GitHub API Error fetching authenticated user: ' . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Error fetching authenticated user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all repositories for the authenticated user
     */
    public function getUserRepositories(int $perPage = 100): array
    {
        try {
            $repositories = [];
            $page = 1;

            // Get authenticated user to identify ownership
            $authenticatedUser = $this->getAuthenticatedUser();
            $authenticatedUserLogin = $authenticatedUser['login'] ?? null;

            do {
                $response = Http::withHeaders($this->getHeaders())
                    ->get("{$this->baseUrl}/user/repos", [
                        'per_page' => $perPage,
                        'page' => $page,
                        'sort' => 'updated',
                        'affiliation' => 'owner,collaborator,organization_member'
                    ]);

                if (!$response->successful()) {
                    Log::error('GitHub API Error: ' . $response->body());
                    break;
                }

                $pageRepos = $response->json();

                if (empty($pageRepos)) {
                    break;
                }

                // Add is_owner flag based on repository owner
                if ($authenticatedUserLogin) {
                    foreach ($pageRepos as &$repo) {
                        $repo['is_owner'] = ($repo['owner']['login'] ?? null) === $authenticatedUserLogin;
                    }
                }

                $repositories = array_merge($repositories, $pageRepos);
                $page++;

                // GitHub API returns Link header for pagination
                $linkHeader = $response->header('Link');
                if (!$linkHeader || !str_contains($linkHeader, 'rel="next"')) {
                    break;
                }

            } while (true);

            return $repositories;

        } catch (\Exception $e) {
            Log::error('Error fetching GitHub repositories: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get repository details
     */
    public function getRepository(string $owner, string $repo): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/repos/{$owner}/{$repo}");

            if (!$response->successful()) {
                Log::error("GitHub API Error fetching {$owner}/{$repo}: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error fetching repository {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get branches for a repository
     */
    public function getRepositoryBranches(string $owner, string $repo): array
    {
        try {
            $branches = [];
            $page = 1;
            $perPage = 100;

            do {
                $response = Http::withHeaders($this->getHeaders())
                    ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/branches", [
                        'per_page' => $perPage,
                        'page' => $page
                    ]);

                if (!$response->successful()) {
                    Log::error("GitHub API Error fetching branches for {$owner}/{$repo}: " . $response->body());
                    break;
                }

                $pageBranches = $response->json();

                if (empty($pageBranches)) {
                    break;
                }

                $branches = array_merge($branches, $pageBranches);
                $page++;

                // Check for next page
                $linkHeader = $response->header('Link');
                if (!$linkHeader || !str_contains($linkHeader, 'rel="next"')) {
                    break;
                }

            } while (true);

            return $branches;

        } catch (\Exception $e) {
            Log::error("Error fetching branches for {$owner}/{$repo}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get branch protection status
     */
    public function getBranchProtection(string $owner, string $repo, string $branch): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/branches/{$branch}/protection");

            if ($response->status() === 404) {
                // Branch is not protected
                return null;
            }

            if ($response->status() === 403) {
                // GitHub Pro required or private repo - skip without logging error
                return null;
            }

            if (!$response->successful()) {
                Log::warning("GitHub API Error fetching protection for {$owner}/{$repo}/{$branch}: " . $response->status());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error fetching branch protection for {$owner}/{$repo}/{$branch}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get commit details
     */
    public function getCommit(string $owner, string $repo, string $sha): ?array
    {
        try {
            // Use Git Data API endpoint to get commit with tree information
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/git/commits/{$sha}");

            if (!$response->successful()) {
                Log::error("GitHub API getCommit failed", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error fetching commit {$sha} for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get request headers with authentication
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        if ($this->token) {
            $headers['Authorization'] = "Bearer {$this->token}";
        }

        return $headers;
    }

    /**
     * Check if API token is configured
     */
    public function hasToken(): bool
    {
        return !empty($this->token);
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/user");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('GitHub API connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a branch on GitHub
     */
    public function createBranch(string $owner, string $repo, string $branchName, string $sha): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/repos/{$owner}/{$repo}/git/refs", [
                    'ref' => "refs/heads/{$branchName}",
                    'sha' => $sha
                ]);

            if (!$response->successful()) {
                Log::error("GitHub API Error creating branch {$branchName} for {$owner}/{$repo}: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error creating branch {$branchName} for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a branch on GitHub
     */
    public function deleteBranchOnGithub(string $owner, string $repo, string $branchName): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->delete("{$this->baseUrl}/repos/{$owner}/{$repo}/git/refs/heads/{$branchName}");

            if (!$response->successful()) {
                Log::error("GitHub API Error deleting branch {$branchName} for {$owner}/{$repo}: " . $response->body());
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Error deleting branch {$branchName} for {$owner}/{$repo}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a repository on GitHub
     */
    public function createRepositoryOnGithub(array $data): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/user/repos", $data);

            if (!$response->successful()) {
                Log::error("GitHub API Error creating repository: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error creating repository: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a blob (file content) on GitHub
     */
    public function createBlob(string $owner, string $repo, string $content): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/repos/{$owner}/{$repo}/git/blobs", [
                    'content' => base64_encode($content),
                    'encoding' => 'base64'
                ]);

            if (!$response->successful()) {
                Log::error("GitHub API Error creating blob for {$owner}/{$repo}: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error creating blob for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a tree on GitHub
     */
    public function createTree(string $owner, string $repo, array $tree, ?string $baseTree = null): ?array
    {
        try {
            $data = ['tree' => $tree];
            if ($baseTree) {
                $data['base_tree'] = $baseTree;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/repos/{$owner}/{$repo}/git/trees", $data);

            if (!$response->successful()) {
                Log::error("GitHub API Error creating tree for {$owner}/{$repo}: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error creating tree for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a commit on GitHub
     */
    public function createCommit(string $owner, string $repo, string $message, string $treeSha, array $parents): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/repos/{$owner}/{$repo}/git/commits", [
                    'message' => $message,
                    'tree' => $treeSha,
                    'parents' => $parents
                ]);

            if (!$response->successful()) {
                Log::error("GitHub API Error creating commit for {$owner}/{$repo}: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error creating commit for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update a reference (like a branch) on GitHub
     */
    public function updateRef(string $owner, string $repo, string $ref, string $sha, bool $force = false): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->patch("{$this->baseUrl}/repos/{$owner}/{$repo}/git/refs/{$ref}", [
                    'sha' => $sha,
                    'force' => $force
                ]);

            if (!$response->successful()) {
                Log::error("GitHub API Error updating ref {$ref} for {$owner}/{$repo}: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error updating ref {$ref} for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get a reference (like a branch) from GitHub
     */
    public function getRef(string $owner, string $repo, string $ref): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/repos/{$owner}/{$repo}/git/refs/{$ref}");

            if (!$response->successful()) {
                Log::error("GitHub API Error getting ref {$ref} for {$owner}/{$repo}: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Error getting ref {$ref} for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }
}
