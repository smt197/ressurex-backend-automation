<?php

namespace App\Http\Controllers;

use App\Http\Requests\GithubSettingsRequest;
use App\Http\Resources\GithubSettingsCollection;
use App\Http\Resources\GithubSettingsResource;
use App\Models\GithubSettingsModel;
use App\Services\GithubApiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;

class GithubSettingsController extends Controller
{
    use DisableAuthorization;

    protected $model = GithubSettingsModel::class;

    protected $request = GithubSettingsRequest::class;

    protected $resource = GithubSettingsResource::class;

    protected $collectionResource = GithubSettingsCollection::class;

    public function keyName(): string
    {
        return 'id';
    }

    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }

    public function searchableBy(): array
    {
        return ['name', 'group', 'payload'];
    }

    public function filterableBy(): array
    {
        return ['name', 'group', 'locked'];
    }

    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        // Filter by github group
        $query->github();

        return $query;
    }

    protected function performStore(Request $request, $model, array $attributes): void
    {
        if (isset($attributes['github_token'])) {
            $model->github_token = $attributes['github_token'];
        }
        
        $model->save();
    }

    protected function performUpdate(Request $request, $model, array $attributes): void
    {
        if (isset($attributes['github_token'])) {
            $model->github_token = $attributes['github_token'];
        }

        $model->save();
    }

    /**
     * Test GitHub connection
     */
    public function test(Request $request): JsonResponse
    {
        try {
            // Retrieve ID from request
            $id = $request->input('id');
            $token = null;

            if ($id) {
                $githubSettings = GithubSettingsModel::find($id);
                
                if (!$githubSettings) {
                    return response()->json([
                        'message' => __('github.settings.not_found'),
                    ], 404);
                }
                
                $token = $githubSettings->github_token;
            }

            $githubApi = new GithubApiService($token);


            if (!$githubApi->hasToken()) {
                return response()->json([
                    'message' => __('github.test.token_missing'),
                ], 400);
            }

            if (!$githubApi->testConnection()) {
                return response()->json([
                    'message' => __('github.test.connection_failed'),
                ], 401);
            }

            return response()->json([
                'message' => __('github.test.success'),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error testing GitHub connection: ' . $e->getMessage());
            return response()->json([
                'message' => __('github.test.error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
