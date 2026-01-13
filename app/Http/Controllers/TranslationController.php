<?php

namespace App\Http\Controllers;

use App\Services\OllamaTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TranslationController extends Controller
{
    protected OllamaTranslationService $translationService;

    public function __construct(OllamaTranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Translate text to multiple languages
     * POST /api/translate
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function translate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string',
            'target_languages' => 'required|array',
            'target_languages.*' => 'required|string|in:en,fr,pt,es,de,it,ar,zh,ja,ko,ru',
            'source_language' => 'nullable|string|in:auto,en,fr,pt,es,de,it,ar,zh,ja,ko,ru',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if service is available
            if (!$this->translationService->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Translation service is currently unavailable'
                ], 503);
            }

            $text = $request->input('text');
            $targetLanguages = $request->input('target_languages');
            $sourceLanguage = $request->input('source_language', 'auto');

            $translations = $this->translationService->translateToMultiple(
                $text,
                $targetLanguages,
                $sourceLanguage
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'original_text' => $text,
                    'source_language' => $sourceLanguage,
                    'translations' => $translations
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Translation request failed', [
                'error' => $e->getMessage(),
                'text' => substr($request->input('text', ''), 0, 50)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Translate batch of texts to a single language
     * POST /api/translate/batch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function translateBatch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'texts' => 'required|array',
            'texts.*' => 'required|string',
            'target_language' => 'required|string|in:en,fr,pt,es,de,it,ar,zh,ja,ko,ru',
            'source_language' => 'nullable|string|in:auto,en,fr,pt,es,de,it,ar,zh,ja,ko,ru',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if (!$this->translationService->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Translation service is currently unavailable'
                ], 503);
            }

            $texts = $request->input('texts');
            $targetLanguage = $request->input('target_language');
            $sourceLanguage = $request->input('source_language', 'auto');

            $translations = $this->translationService->translateBatch(
                $texts,
                $targetLanguage,
                $sourceLanguage
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'target_language' => $targetLanguage,
                    'source_language' => $sourceLanguage,
                    'translations' => $translations
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Batch translation request failed', [
                'error' => $e->getMessage(),
                'count' => count($request->input('texts', []))
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Batch translation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if translation service is available
     * GET /api/translate/status
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        try {
            $isAvailable = $this->translationService->isAvailable();
            $models = $isAvailable ? $this->translationService->getAvailableModels() : [];

            return response()->json([
                'success' => true,
                'data' => [
                    'available' => $isAvailable,
                    'models' => $models,
                    'current_model' => config('services.ollama.model'),
                    'base_url' => config('services.ollama.url')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check service status',
                'data' => [
                    'available' => false,
                    'error' => $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Get translation job status for a module
     * GET /api/translate/job-status/{moduleName}
     *
     * @param string $moduleName
     * @return JsonResponse
     */
    public function getJobStatus(string $moduleName): JsonResponse
    {
        try {
            // Find the most recent translation file for this module
            $files = \Storage::disk('local')->files('temp_translations');
            $moduleFiles = array_filter($files, function($file) use ($moduleName) {
                return str_starts_with(basename($file), $moduleName . '_') &&
                       str_ends_with($file, '_status.json');
            });

            if (empty($moduleFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No translation job found for this module'
                ], 404);
            }

            // Get the most recent status file
            rsort($moduleFiles);
            $statusFile = $moduleFiles[0];

            $statusData = json_decode(\Storage::disk('local')->get($statusFile), true);

            return response()->json([
                'success' => true,
                'data' => $statusData
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get translation job status', [
                'module' => $moduleName,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get job status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get completed translations for a module
     * GET /api/translate/completed/{moduleName}
     *
     * @param string $moduleName
     * @return JsonResponse
     */
    public function getCompletedTranslations(string $moduleName): JsonResponse
    {
        try {
            // Find the most recent translation file for this module
            $files = \Storage::disk('local')->files('temp_translations');
            $moduleFiles = array_filter($files, function($file) use ($moduleName) {
                return str_starts_with(basename($file), $moduleName . '_') &&
                       str_ends_with($file, '.json') &&
                       !str_ends_with($file, '_status.json');
            });

            if (empty($moduleFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No translation data found for this module'
                ], 404);
            }

            // Get the most recent translation file
            rsort($moduleFiles);
            $translationFile = $moduleFiles[0];

            $translationData = json_decode(\Storage::disk('local')->get($translationFile), true);

            // Check if translations are completed
            if (!isset($translationData['completed_at'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Translations are still in progress',
                    'data' => [
                        'status' => 'pending',
                        'created_at' => $translationData['created_at'] ?? null
                    ]
                ], 202); // 202 Accepted - processing not complete
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'module' => $translationData['module'],
                    'translations' => $translationData['translations'],
                    'created_at' => $translationData['created_at'],
                    'completed_at' => $translationData['completed_at']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get completed translations', [
                'module' => $moduleName,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get translations: ' . $e->getMessage()
            ], 500);
        }
    }
}
