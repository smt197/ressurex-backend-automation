<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use App\Services\OllamaTranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranslateModuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour for long translations

    protected string $moduleName;
    protected string $translationFilePath;
    protected array $targetLanguages;
    protected ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $moduleName, string $translationFilePath, array $targetLanguages = ['fr', 'pt'], ?int $userId = null)
    {
        $this->moduleName = $moduleName;
        $this->translationFilePath = $translationFilePath;
        $this->targetLanguages = $targetLanguages;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(OllamaTranslationService $translationService): void
    {
        Log::info('TranslateModuleJob started', [
            'module' => $this->moduleName,
            'file' => $this->translationFilePath,
            'languages' => $this->targetLanguages,
        ]);

        try {
            // Check if Ollama is available
            if (!$translationService->isAvailable()) {
                Log::warning('Ollama translation service not available', [
                    'module' => $this->moduleName,
                ]);
                $this->updateTranslationStatus('failed', 'Ollama service not available');
                return;
            }

            // Read the default translation JSON
            if (!Storage::disk('local')->exists($this->translationFilePath)) {
                Log::error('Translation file not found', [
                    'file' => $this->translationFilePath,
                ]);
                $this->updateTranslationStatus('failed', 'Translation file not found');
                return;
            }

            $translationData = json_decode(Storage::disk('local')->get($this->translationFilePath), true);

            if (!$translationData || !isset($translationData['texts'])) {
                Log::error('Invalid translation data structure', [
                    'file' => $this->translationFilePath,
                ]);
                $this->updateTranslationStatus('failed', 'Invalid translation data');
                return;
            }

            // Update status to processing
            $this->updateTranslationStatus('processing', 'Translating texts...');

            // Get texts to translate from English
            $textsToTranslate = $translationData['texts'];
            $totalTexts = count($textsToTranslate);
            $translatedCount = 0;

            // Initialize translations structure
            $translations = [
                'en' => [],
            ];

            foreach ($this->targetLanguages as $lang) {
                $translations[$lang] = [];
            }

            // Translate each text
            foreach ($textsToTranslate as $text) {
                // English is always the source
                $translations['en'][$text] = $text;

                // Translate to target languages
                foreach ($this->targetLanguages as $targetLang) {
                    try {
                        Log::info('Translating text', [
                            'text' => substr($text, 0, 50) . (strlen($text) > 50 ? '...' : ''),
                            'target' => $targetLang,
                            'progress' => ($translatedCount + 1) . '/' . ($totalTexts * count($this->targetLanguages)),
                        ]);

                        $translation = $translationService->translate($text, $targetLang, 'en');
                        $translations[$targetLang][$text] = $translation;

                        Log::info('Translation successful', [
                            'text' => substr($text, 0, 50) . (strlen($text) > 50 ? '...' : ''),
                            'target' => $targetLang,
                            'translation' => substr($translation, 0, 50) . (strlen($translation) > 50 ? '...' : ''),
                        ]);

                        $translatedCount++;

                        // Update progress
                        $progress = round(($translatedCount / ($totalTexts * count($this->targetLanguages))) * 100);
                        $this->updateTranslationStatus('processing', "Translating... {$progress}%", $progress);
                    } catch (\Exception $e) {
                        Log::error('Translation failed', [
                            'text' => substr($text, 0, 50),
                            'target' => $targetLang,
                            'error' => $e->getMessage(),
                        ]);
                        // Fallback to original text
                        $translations[$targetLang][$text] = $text;
                    }
                }
            }

            // Update the translation file with completed translations
            $translationData['translations'] = $translations;
            $translationData['completed_at'] = now()->toISOString();

            Storage::disk('local')->put(
                $this->translationFilePath,
                json_encode($translationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            // Update status to completed
            $this->updateTranslationStatus('completed', 'Translation completed successfully', 100);

            // Update the seeders lang file
            $this->updateSeedersLangFile($translations);

            // Run the TranslationLoaderSeeder to load new translations into the database
            $this->runTranslationSeeder();

            // Update frontend i18n files with Ollama translations
            $this->updateFrontendI18nFiles($translations);

            Log::info('TranslateModuleJob completed successfully', [
                'module' => $this->moduleName,
                'texts_count' => $totalTexts,
                'languages' => $this->targetLanguages,
            ]);

            // Send notification to user if userId is provided
            $this->sendCompletionNotification(true);
        } catch (\Exception $e) {
            Log::error('TranslateModuleJob failed', [
                'module' => $this->moduleName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateTranslationStatus('failed', $e->getMessage());

            // Send failure notification
            $this->sendCompletionNotification(false, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Send completion notification to user
     */
    protected function sendCompletionNotification(bool $success, ?string $errorMessage = null): void
    {
        if (!$this->userId) {
            return;
        }

        try {
            $user = User::find($this->userId);

            if (!$user) {
                Log::warning('User not found for notification', [
                    'user_id' => $this->userId,
                    'module' => $this->moduleName,
                ]);
                return;
            }

            if ($success) {
                $message = __('translation.job_completed', [
                    'module' => $this->moduleName,
                    'languages' => implode(', ', $this->targetLanguages),
                ]);
            } else {
                $message = __('translation.job_failed', [
                    'module' => $this->moduleName,
                    'error' => $errorMessage ?? 'Unknown error',
                ]);
            }

            NotificationService::sendNotificationToUser($user, $message);

            Log::info('Translation completion notification sent', [
                'user_id' => $this->userId,
                'module' => $this->moduleName,
                'success' => $success,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send translation completion notification', [
                'user_id' => $this->userId,
                'module' => $this->moduleName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update translation status file
     */
    protected function updateTranslationStatus(string $status, string $message, int $progress = 0): void
    {
        $statusFilePath = str_replace('.json', '_status.json', $this->translationFilePath);

        $statusData = [
            'module' => $this->moduleName,
            'status' => $status, // pending, processing, completed, failed
            'message' => $message,
            'progress' => $progress,
            'updated_at' => now()->toISOString(),
        ];

        Storage::disk('local')->put(
            $statusFilePath,
            json_encode($statusData, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Update seeders lang file with translations
     */
    protected function updateSeedersLangFile(array $translations): void
    {
        try {
            $langFilePath = database_path("seeders/lang/{$this->moduleName}.json");

            if (!File::exists($langFilePath)) {
                Log::warning('Lang file not found for update', [
                    'module' => $this->moduleName,
                    'path' => $langFilePath,
                ]);
                return;
            }

            // Read existing file
            $existingData = json_decode(File::get($langFilePath), true);

            if (!is_array($existingData)) {
                Log::error('Invalid lang file format', ['module' => $this->moduleName]);
                return;
            }

            $translationService = app(OllamaTranslationService::class);

            // Update translations in existing data
            foreach ($existingData as &$item) {
                if (isset($item['text']) && is_array($item['text'])) {
                    $englishText = $item['text']['en'] ?? '';

                    if (empty($englishText)) {
                        continue;
                    }

                    Log::info('Translating lang file entry', [
                        'key' => $item['key'] ?? 'unknown',
                        'text' => substr($englishText, 0, 50),
                    ]);

                    // Translate to French
                    if (isset($item['text']['fr'])) {
                        try {
                            $frTranslation = $translationService->translate($englishText, 'fr', 'en');
                            $item['text']['fr'] = $frTranslation;

                            Log::info('French translation completed', [
                                'original' => substr($englishText, 0, 30),
                                'translated' => substr($frTranslation, 0, 30),
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('French translation failed', [
                                'text' => substr($englishText, 0, 50),
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    // Translate to Portuguese
                    if (isset($item['text']['pt'])) {
                        try {
                            $ptTranslation = $translationService->translate($englishText, 'pt', 'en');
                            $item['text']['pt'] = $ptTranslation;

                            Log::info('Portuguese translation completed', [
                                'original' => substr($englishText, 0, 30),
                                'translated' => substr($ptTranslation, 0, 30),
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('Portuguese translation failed', [
                                'text' => substr($englishText, 0, 50),
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }

            // Write updated data back to file
            File::put(
                $langFilePath,
                json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            Log::info('Seeders lang file updated successfully', [
                'module' => $this->moduleName,
                'path' => $langFilePath,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update seeders lang file', [
                'module' => $this->moduleName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Run TranslationLoaderSeeder to load updated translations into database
     */
    protected function runTranslationSeeder(): void
    {
        try {
            Log::info('Running TranslationLoaderSeeder after translation update', [
                'module' => $this->moduleName,
            ]);

            \Artisan::call('db:seed', [
                '--class' => 'TranslationLoaderSeeder',
                '--force' => true,
            ]);

            $output = \Artisan::output();

            Log::info('TranslationLoaderSeeder executed successfully', [
                'module' => $this->moduleName,
                'output' => trim($output),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to run TranslationLoaderSeeder', [
                'module' => $this->moduleName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update frontend i18n files with Ollama translations
     */
    protected function updateFrontendI18nFiles(array $translations): void
    {
        try {
            $frontendPath = config('app.frontend_path', env('FRONTEND_PATH'));

            if (!$frontendPath || !file_exists($frontendPath)) {
                Log::warning('Frontend path not configured or does not exist', [
                    'path' => $frontendPath,
                ]);
                return;
            }

            $i18nPath = $frontendPath . '/src/assets/i18n';

            if (!file_exists($i18nPath)) {
                Log::warning('Frontend i18n path does not exist', [
                    'path' => $i18nPath,
                ]);
                return;
            }

            Log::info('Updating frontend i18n files with Ollama translations', [
                'module' => $this->moduleName,
                'path' => $i18nPath,
            ]);

            $translationService = app(OllamaTranslationService::class);
            $singularName = rtrim($this->moduleName, 's');

            // Pour chaque langue (en, fr, pt)
            foreach (['en', 'fr', 'pt'] as $lang) {
                try {
                    $filePath = $i18nPath . "/{$lang}.json";

                    if (!file_exists($filePath)) {
                        Log::warning("i18n file not found: {$lang}.json");
                        continue;
                    }

                    // Lire le fichier JSON existant
                    $content = File::get($filePath);
                    $jsonData = json_decode($content, true);

                    if (!is_array($jsonData)) {
                        Log::error("Invalid JSON in {$lang}.json");
                        continue;
                    }

                    // Skip English - already set by generate-module.js
                    if ($lang === 'en') {
                        continue;
                    }

                    Log::info("Translating {$lang}.json", [
                        'module' => $this->moduleName,
                    ]);

                    // Translate menu
                    if (isset($jsonData['menu'][$this->moduleName])) {
                        $englishText = $jsonData['menu'][$this->moduleName];
                        $translation = $translationService->translate($englishText, $lang, 'en');
                        $jsonData['menu'][$this->moduleName] = $translation;

                        Log::info("Translated menu.{$this->moduleName}", [
                            'lang' => $lang,
                            'from' => $englishText,
                            'to' => $translation,
                        ]);
                    }

                    // Translate global types
                    if (isset($jsonData['global']['types'][$this->moduleName])) {
                        $englishText = $jsonData['global']['types'][$this->moduleName];
                        $translation = $translationService->translate($englishText, $lang, 'en');
                        $jsonData['global']['types'][$this->moduleName] = $translation;

                        Log::info("Translated global.types.{$this->moduleName}", [
                            'lang' => $lang,
                            'from' => $englishText,
                            'to' => $translation,
                        ]);
                    }

                    // Module-specific sections
                    if (isset($jsonData[$singularName])) {
                        // Create button
                        if (isset($jsonData[$singularName]["{$singularName}_create"])) {
                            $englishText = $jsonData[$singularName]["{$singularName}_create"];
                            $translation = $translationService->translate($englishText, $lang, 'en');
                            $jsonData[$singularName]["{$singularName}_create"] = $translation;

                            Log::info("Translated {$singularName}.{$singularName}_create", [
                                'lang' => $lang,
                                'from' => $englishText,
                                'to' => $translation,
                            ]);
                        }

                        // Detail title
                        if (isset($jsonData[$singularName]['detail']['title'])) {
                            $englishText = $jsonData[$singularName]['detail']['title'];
                            $translation = $translationService->translate($englishText, $lang, 'en');
                            $jsonData[$singularName]['detail']['title'] = $translation;

                            Log::info("Translated {$singularName}.detail.title", [
                                'lang' => $lang,
                                'from' => $englishText,
                                'to' => $translation,
                            ]);
                        }

                        // Labels
                        if (isset($jsonData[$singularName]['label'])) {
                            foreach ($jsonData[$singularName]['label'] as $fieldName => $labelValue) {
                                $englishText = $labelValue;
                                $translation = $translationService->translate($englishText, $lang, 'en');
                                $jsonData[$singularName]['label'][$fieldName] = $translation;

                                Log::info("Translated {$singularName}.label.{$fieldName}", [
                                    'lang' => $lang,
                                    'from' => $englishText,
                                    'to' => $translation,
                                ]);
                            }
                        }

                        // Placeholders
                        if (isset($jsonData[$singularName]['placeholder'])) {
                            foreach ($jsonData[$singularName]['placeholder'] as $fieldName => $placeholderValue) {
                                $englishText = $placeholderValue;
                                $translation = $translationService->translate($englishText, $lang, 'en');
                                $jsonData[$singularName]['placeholder'][$fieldName] = $translation;

                                Log::info("Translated {$singularName}.placeholder.{$fieldName}", [
                                    'lang' => $lang,
                                    'from' => $englishText,
                                    'to' => $translation,
                                ]);
                            }
                        }

                        // Validation messages
                        if (isset($jsonData[$singularName]['validation'])) {
                            foreach ($jsonData[$singularName]['validation'] as $validationKey => $validationValue) {
                                $englishText = $validationValue;
                                $translation = $translationService->translate($englishText, $lang, 'en');
                                $jsonData[$singularName]['validation'][$validationKey] = $translation;

                                Log::info("Translated {$singularName}.validation.{$validationKey}", [
                                    'lang' => $lang,
                                    'from' => $englishText,
                                    'to' => $translation,
                                ]);
                            }
                        }
                    }

                    // Écrire le fichier avec formatage (2 espaces)
                    File::put($filePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    Log::info("Frontend i18n file updated: {$lang}.json", [
                        'module' => $this->moduleName,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to update {$lang}.json", [
                        'module' => $this->moduleName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('All frontend i18n files updated successfully', [
                'module' => $this->moduleName,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update frontend i18n files', [
                'module' => $this->moduleName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TranslateModuleJob failed permanently', [
            'module' => $this->moduleName,
            'error' => $exception->getMessage(),
        ]);

        $this->updateTranslationStatus('failed', 'Translation job failed: ' . $exception->getMessage());

        // Send failure notification
        $this->sendCompletionNotification(false, $exception->getMessage());
    }
}
