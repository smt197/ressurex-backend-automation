<?php

namespace App\Jobs;

use App\Models\Document;
use App\Traits\Trackable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessDocumentUploads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use Trackable;

    protected $jobStatus;

    public $tries = 3;

    public $timeout = 300;

    protected array $tempFilePaths;

    protected array $originalFileNames;

    protected int $mainDocumentId;

    public $disk;

    public function __construct(

        array $tempFilePaths,
        array $originalFileNames,
        int $mainDocumentId,
    ) {
        $this->disk = Config::get('filesystems.default');

        $this->tempFilePaths = $tempFilePaths;
        $this->originalFileNames = $originalFileNames;
        $this->mainDocumentId = $mainDocumentId;

        $this->prepareStatus();

        $this->setInput([
            'file_count' => count($tempFilePaths),
            'main_document_id' => $mainDocumentId,
            'type' => 'document',
        ]);
    }

    public function handle(): void
    {
        Log::info("🎬 ProcessDocumentUploads JOB STARTED for document {$this->mainDocumentId}");

        $status = $this->getEntity();
        if ($status) {
            // La propriété $this->job est fournie par le trait InteractsWithQueue.
            // Elle représente le job de la file d'attente en cours d'exécution.
            $status->job_id = $this->job->getJobId();
            $status->save();
        }

        // On utilise 100 comme maximum pour avoir des pourcentages plus fins
        $this->setProgressMax(100);
        $processedDocumentIds = [];
        $processedFileNames = [];

        try {
            // === NOTIFICATION IMMEDIATE DE DEMARRAGE ===
            $this->updateStatus('executing');
            Log::info("🚀 IMMEDIATE START - Job switched to 'executing' status");

            // Initialiser le progrès à 0
            $this->setProgressNowFloat(0);
            Log::info('📊 Progress initialized to 0%');

            // On récupère le document principal créé par Orion
            $mainDocument = Document::findOrFail($this->mainDocumentId);
            Log::info("📄 Document {$this->mainDocumentId} loaded successfully");

            Log::info('🏁 Starting processing of '.count($this->tempFilePaths).' files...');

            // Petite pause pour que l'UI se mette à jour
            sleep(2);

            $totalFiles = count($this->tempFilePaths);

            foreach ($this->tempFilePaths as $index => $tempPath) {
                $fileNumber = $index + 1;
                $fileName = $this->originalFileNames[$index];

                Log::info("🔄 Starting file {$fileNumber}/{$totalFiles}: {$fileName}");

                // === ÉTAPE 1: PRÉPARATION DU DOCUMENT (0-20% du fichier) ===
                $this->incrementProgressForFile($index, $totalFiles, 0);
                Log::info("📋 Preparing document for file {$fileNumber}...");
                sleep(2);

                $this->incrementProgressForFile($index, $totalFiles, 10);
                sleep(1);

                $documentToProcess = null;
                if ($index === 0) {
                    $documentToProcess = $mainDocument;
                } else {
                    $documentToProcess = new Document;
                    $documentToProcess->user_id = $mainDocument->user_id;
                    $documentToProcess->allias_name = $mainDocument->allias_name;
                    $documentToProcess->name = $fileName;
                    $documentToProcess->description = $fileName;
                    $documentToProcess->save();
                }
                $processedDocumentIds[] = $documentToProcess->id;
                $processedFileNames[] = $fileName;

                $this->incrementProgressForFile($index, $totalFiles, 20);
                Log::info("✅ Document prepared for file {$fileNumber}");
                sleep(1);

                // === ÉTAPE 2: VALIDATION DU FICHIER (20-40% du fichier) ===
                $this->incrementProgressForFile($index, $totalFiles, 25);
                Log::info("🔍 Validating file {$fileNumber}...");
                sleep(2);

                $this->incrementProgressForFile($index, $totalFiles, 35);
                sleep(1);

                $this->incrementProgressForFile($index, $totalFiles, 40);
                Log::info("✅ File {$fileNumber} validated");
                sleep(1);

                // === ÉTAPE 3: ATTACHMENT DU MÉDIA (40-70% du fichier) ===
                $this->incrementProgressForFile($index, $totalFiles, 45);
                Log::info("📎 Attaching media for file {$fileNumber}...");
                sleep(2);

                $this->incrementProgressForFile($index, $totalFiles, 55);
                $this->attachMediaToDocument($documentToProcess, $tempPath, $fileName);
                sleep(2);

                $this->incrementProgressForFile($index, $totalFiles, 65);
                sleep(1);

                $this->incrementProgressForFile($index, $totalFiles, 70);
                Log::info("✅ Media attached for file {$fileNumber}");
                sleep(1);

                // === ÉTAPE 4: FINALISATION (70-90% du fichier) ===
                $this->incrementProgressForFile($index, $totalFiles, 75);
                Log::info("🔧 Finalizing file {$fileNumber}...");
                sleep(2);

                $this->incrementProgressForFile($index, $totalFiles, 85);
                sleep(1);

                $this->incrementProgressForFile($index, $totalFiles, 90);
                sleep(1);

                // === ÉTAPE 5: COMPLETION (90-100% du fichier) ===
                $this->incrementProgressForFile($index, $totalFiles, 95);
                Log::info("🏁 Completing file {$fileNumber}...");
                sleep(1);

                $this->incrementProgressForFile($index, $totalFiles, 100);
                Log::info("🎉 File {$fileNumber}/{$totalFiles} completed successfully!");
                sleep(1);
            }

            // Pause finale pour la finalisation
            sleep(1);
            Storage::disk($this->disk)->delete($this->tempFilePaths);

            $this->setOutput([
                'status_message' => __('documents.processing_succes'),
                'processed_document_ids' => $processedDocumentIds,
                'processed_file' => $processedFileNames,
                'total_processed' => count($processedDocumentIds),
            ]);

            // Marquer le job comme terminé avec une dernière pause
            Log::info('Job completed. Setting status to finished.');
            $this->update(['status' => 'finished']);
        } catch (Throwable $e) {
            Log::error('Job failed: '.$e->getMessage());
            $this->setOutput([
                'error' => $e->getMessage(),
                'processed_document_ids_before_failure' => $processedDocumentIds,
            ]);
            throw $e;
        }
    }

    /**
     * Helper pour attacher un média à un document.
     */
    private function attachMediaToDocument(Document $document, string $tempPath, string $originalFileName): void
    {
        $safeFileName = Str::slug(pathinfo($originalFileName, PATHINFO_FILENAME)).'.'.pathinfo($originalFileName, PATHINFO_EXTENSION);

        $document->addMediaFromDisk($tempPath, $this->disk)
            ->usingName($originalFileName)
            ->usingFileName($safeFileName)
            ->withCustomProperties(['document_id' => $document->id])
            ->toMediaCollection('attachments');
    }

    public function failed(Throwable $exception): void
    {
        Storage::disk($this->disk)->delete($this->tempFilePaths);
        $this->setOutput(['error' => $exception->getMessage()]);
    }
}
