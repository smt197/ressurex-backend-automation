<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaTranslationService
{
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.url', 'http://192.168.1.10:11434');
        $this->model = config('services.ollama.model', 'llama3.2:1b');
        $this->timeout = config('services.ollama.timeout', 600);
    }

    /**
     * Translate text to multiple languages
     *
     * @param string $text The text to translate
     * @param array $targetLanguages Target languages (e.g., ['en', 'fr', 'pt'])
     * @param string $sourceLanguage Source language (default: 'auto')
     * @return array Translations keyed by language code
     */
    public function translateToMultiple(string $text, array $targetLanguages, string $sourceLanguage = 'auto'): array
    {
        $translations = [];

        foreach ($targetLanguages as $targetLang) {
            try {
                $translation = $this->translate($text, $targetLang, $sourceLanguage);
                $translations[$targetLang] = $translation;

                Log::info('Translation successful', [
                    'source' => $sourceLanguage,
                    'target' => $targetLang,
                    'text' => substr($text, 0, 50) . '...',
                ]);
            } catch (\Exception $e) {
                Log::error('Translation failed', [
                    'target' => $targetLang,
                    'error' => $e->getMessage(),
                ]);
                $translations[$targetLang] = $text; // Fallback to original text
            }
        }

        return $translations;
    }

    /**
     * Translate text to a single language
     *
     * @param string $text The text to translate
     * @param string $targetLanguage Target language code
     * @param string $sourceLanguage Source language (default: 'auto')
     * @return string Translated text
     */
    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'auto'): string
    {
        $prompt = $this->buildTranslationPrompt($text, $targetLanguage, $sourceLanguage);

        $response = $this->callOllama($prompt);

        return $this->extractTranslation($response);
    }

    /**
     * Translate an array of texts (batch translation)
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @param string $sourceLanguage Source language (default: 'auto')
     * @return array Translated texts
     */
    public function translateBatch(array $texts, string $targetLanguage, string $sourceLanguage = 'auto'): array
    {
        $translations = [];

        foreach ($texts as $key => $text) {
            $translations[$key] = $this->translate($text, $targetLanguage, $sourceLanguage);
        }

        return $translations;
    }

    /**
     * Translate multiple texts in a single batch request (optimized for speed)
     *
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @param string $sourceLanguage Source language (default: 'auto')
     * @return array Translated texts in the same order as input
     */
    public function translateBatchOptimized(array $texts, string $targetLanguage, string $sourceLanguage = 'auto'): array
    {
        if (empty($texts)) {
            return [];
        }

        $languageNames = $this->getLanguageName($targetLanguage);
        $sourceLangText = $sourceLanguage === 'auto' ? 'English' : $this->getLanguageName($sourceLanguage)['name'];

        // Build JSON array of texts
        $textsJson = json_encode(array_values($texts), JSON_UNESCAPED_UNICODE);

        // Prompt simplifié pour llama3.2:1b
        $prompt = <<<PROMPT
Translate these texts from {$sourceLangText} to {$languageNames['name']}. Return only a JSON array of translations in the same order.

Input:
{$textsJson}

Output JSON array:
PROMPT;

        try {
            $response = $this->callOllama($prompt);

            // Clean up the response - remove markdown code blocks if present
            $response = preg_replace('/^```json\s*/m', '', $response);
            $response = preg_replace('/^```\s*/m', '', $response);
            $response = trim($response);

            // Try to decode JSON
            $translations = json_decode($response, true);

            if (!is_array($translations)) {
                throw new \Exception('Invalid JSON response from Ollama');
            }

            // Ensure we have the same number of translations as inputs
            if (count($translations) !== count($texts)) {
                Log::warning('Batch translation count mismatch', [
                    'expected' => count($texts),
                    'received' => count($translations),
                ]);
            }

            return $translations;

        } catch (\Exception $e) {
            Log::error('Batch translation failed, falling back to individual translations', [
                'error' => $e->getMessage(),
                'response_preview' => substr($response ?? '', 0, 200),
            ]);

            // Fallback to individual translations
            $result = [];
            foreach ($texts as $text) {
                try {
                    $result[] = $this->translate($text, $targetLanguage, $sourceLanguage);
                } catch (\Exception $ex) {
                    $result[] = $text; // Fallback to original text
                }
            }
            return $result;
        }
    }

    /**
     * Build translation prompt for Ollama
     *
     * @param string $text Text to translate
     * @param string $targetLanguage Target language CODE (e.g., 'fr', 'pt', 'en')
     * @param string $sourceLanguage Source language CODE (e.g., 'en', 'fr') or 'auto'
     * @return string The prompt for Ollama
     */
    protected function buildTranslationPrompt(string $text, string $targetLanguage, string $sourceLanguage): string
    {
        // Convert language codes to full names
        $sourceLangText = $sourceLanguage === 'auto' ? 'English' : $this->getLanguageName($sourceLanguage)['name'];
        $targetLangText = $this->getLanguageName($targetLanguage)['name'];

        // Prompt simplifié pour llama3.2:1b
        return <<<PROMPT
Translate from {$sourceLangText} to {$targetLangText}. Only output the translation, nothing else.

Text: {$text}

Translation:
PROMPT;
    }

    /**
     * Call Ollama API
     */
    protected function callOllama(string $prompt): string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'keep_alive' => '1h', // garde le modèle chargé 1 heure (doit être au niveau racine)
                    'options' => [
                        'temperature' => 0.1, // Lower temperature for more consistent translations
                        'top_p' => 0.9,
                        'num_predict' => 4096, // Increase max tokens for batch translations
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Ollama API request failed: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['response'])) {
                throw new \Exception('Invalid response from Ollama API');
            }

            return $data['response'];
        } catch (\Exception $e) {
            Log::error('Ollama API call failed', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);

            throw new \Exception('Translation service unavailable: ' . $e->getMessage());
        }
    }

    /**
     * Extract clean translation from Ollama response
     */
    protected function extractTranslation(string $response): string
    {
        // Remove common prefixes that LLM might add
        $response = trim($response);
        $response = preg_replace('/^(Translation:|Translated text:|Here is the translation:)\s*/i', '', $response);

        // Remove quotes if the entire response is wrapped in them
        if (preg_match('/^"(.+)"$/', $response, $matches)) {
            $response = $matches[1];
        }

        return trim($response);
    }

    /**
     * Get language name from language code
     */
    protected function getLanguageName(string $code): array
    {
        $languages = [
            'en' => ['name' => 'English', 'native' => 'English'],
            'fr' => ['name' => 'French', 'native' => 'Français'],
            'pt' => ['name' => 'Portuguese', 'native' => 'Português'],
            'es' => ['name' => 'Spanish', 'native' => 'Español'],
            'de' => ['name' => 'German', 'native' => 'Deutsch'],
            'it' => ['name' => 'Italian', 'native' => 'Italiano'],
            'ar' => ['name' => 'Arabic', 'native' => 'العربية'],
            'zh' => ['name' => 'Chinese', 'native' => '中文'],
            'ja' => ['name' => 'Japanese', 'native' => '日本語'],
            'ko' => ['name' => 'Korean', 'native' => '한국어'],
            'ru' => ['name' => 'Russian', 'native' => 'Русский'],
        ];

        return $languages[$code] ?? ['name' => ucfirst($code), 'native' => ucfirst($code)];
    }

    /**
     * Check if Ollama service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Ollama service not available', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get list of available models
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            return $data['models'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get Ollama models', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
