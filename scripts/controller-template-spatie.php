<?php
/**
 * Template de contrôleur pour les modules avec fichiers (Spatie Media Library)
 *
 * Ce template peut être utilisé comme base pour créer des contrôleurs
 * qui gèrent des uploads de fichiers avec Spatie Media Library
 */

namespace App\Http\Controllers;

use App\Http\Requests\{{ModelName}}Request;
use App\Http\Resources\{{ModelName}}Collection;
use App\Http\Resources\{{ModelName}}Resource;
use App\Models\{{ModelName}};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Orion\Http\Controllers\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class {{ModelName}}Controller extends Controller
{
    protected $model = {{ModelName}}::class;

    protected $request = {{ModelName}}Request::class;

    protected $resource = {{ModelName}}Resource::class;

    protected $collectionResource = {{ModelName}}Collection::class;

    /**
     * Field to use as key (id, slug, uuid, etc.)
     */
    public function keyName(): string
    {
        return '{{keyName}}';
    }

    /**
     * Pagination limit
     */
    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }

    /**
     * Searchable fields
     */
    public function searchableBy(): array
    {
        return [{{searchableFields}}];
    }

    /**
     * Filter query by authenticated user
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        $query->where('user_id', $request->user()->id);

        return $query;
    }

    /**
     * Set user_id before creating
     */
    protected function beforeStore(Request $request, $model)
    {
        $model->user_id = $request->user()->id;
    }

    /**
     * Handle file uploads after creating the model
     */
    protected function afterStore(Request $request, $model)
    {
        if ($request->hasFile('{{fileFieldName}}')) {
            $files = is_array($request->file('{{fileFieldName}}'))
                ? $request->file('{{fileFieldName}}')
                : [$request->file('{{fileFieldName}}')];

            foreach ($files as $file) {
                $model->addMedia($file)
                    ->withCustomProperties([
                        '{{modelLowerName}}_id' => $model->id,
                        'user_id' => $request->user()->id,
                    ])
                    ->toMediaCollection('{{mediaCollectionName}}');
            }

            // Refresh to get media relations
            $model->refresh();
        }

        return $this->getResourceResponse($model);
    }

    /**
     * Set user_id before updating
     */
    protected function beforeUpdate(Request $request, $model)
    {
        $model->user_id = $request->user()->id;
    }

    /**
     * Handle file uploads when updating
     */
    protected function afterUpdate(Request $request, $model)
    {
        if ($request->hasFile('{{fileFieldName}}')) {
            // Option 1: Replace all existing files
            // $model->clearMediaCollection('{{mediaCollectionName}}');

            // Option 2: Add new files (keep existing ones)
            $files = is_array($request->file('{{fileFieldName}}'))
                ? $request->file('{{fileFieldName}}')
                : [$request->file('{{fileFieldName}}')];

            foreach ($files as $file) {
                $model->addMedia($file)
                    ->withCustomProperties([
                        '{{modelLowerName}}_id' => $model->id,
                        'user_id' => $request->user()->id,
                    ])
                    ->toMediaCollection('{{mediaCollectionName}}');
            }

            $model->refresh();
        }

        return $this->getResourceResponse($model);
    }

    /**
     * Delete model with its media files
     */
    public function destroy(Request $request, ...$args): JsonResponse
    {
        try {
            $identifier = $args[0];

            $model = $this->model::where($this->keyName(), $identifier)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            DB::beginTransaction();

            // Delete all media files
            $model->clearMediaCollection('{{mediaCollectionName}}');

            // Delete the model
            $model->delete();

            DB::commit();

            return response()->json([
                'message' => __('{{translationKey}}.deleted'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting {{modelLowerName}}', [
                'error' => $e->getMessage(),
                'identifier' => $identifier ?? null
            ]);

            return response()->json([
                'message' => __('{{translationKey}}.delete_error'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch delete with media cleanup
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $identifiers = $request->input('resources', []);

        if (empty($identifiers)) {
            return response()->json([
                'message' => __('{{translationKey}}.no_items_to_delete')
            ], 400);
        }

        DB::beginTransaction();

        try {
            $models = $this->model::whereIn($this->keyName(), $identifiers)
                ->where('user_id', $request->user()->id)
                ->get();

            foreach ($models as $model) {
                // Delete all media files
                $model->clearMediaCollection('{{mediaCollectionName}}');

                // Delete the model
                $model->delete();
            }

            DB::commit();

            return response()->json([
                'message' => __('{{translationKey}}.deleted_multiple', ['count' => $models->count()])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch delete error', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('{{translationKey}}.delete_error'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific media file
     */
    public function deleteMedia(Request $request, $mediaId): JsonResponse
    {
        try {
            $media = Media::findOrFail($mediaId);

            // Verify ownership through the model
            $model = $media->model;
            if ($model->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => __('{{translationKey}}.unauthorized')
                ], 403);
            }

            $media->delete();

            return response()->json([
                'message' => __('{{translationKey}}.media_deleted')
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting media', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => __('{{translationKey}}.media_delete_error'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format response with resource
     */
    private function getResourceResponse($model)
    {
        $formatted = (new $this->resource($model))->toArray(request());

        return response()->json([
            'message' => __('{{translationKey}}.success'),
            'data' => $formatted,
        ], 200);
    }
}
