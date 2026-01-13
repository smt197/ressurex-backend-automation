<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Http\Resources\DocumentCollection;
use App\Http\Resources\DocumentResource;
use App\Jobs\ProcessDocumentUploads;
use App\Models\Document;
use App\Models\JobStatus;
use App\Models\TemporyFile;
use App\Notifications\JobStatusUpdatedNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Orion\Http\Controllers\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentController extends Controller
{
    /**
     * Used to store the original locale for restoration.
     *
     * @var string|null
     */
    protected $originalLocale = null;

    protected $model = Document::class;

    protected $request = DocumentRequest::class;

    protected $resource = DocumentResource::class;

    protected $collectionResource = DocumentCollection::class;

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
        return config('app.max_pagination');
    }

    public function searchableBy(): array
    {
        return ['name', 'description', 'allias_name'];
    }

    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);

        // Get the first document ID for each alias_name (assuming you want MIN, not MAX)
        $firstIds = DB::table('documents')
            ->where('user_id', $request->user()->id)
            ->select('allias_name', DB::raw('MIN(id) as min_id'))
            ->groupBy('allias_name');

        // Join with the main query to get full records
        $query->joinSub($firstIds, 'first', function ($join) {
            $join->on('documents.id', '=', 'first.min_id');
        });

        return $query;
    }

    public function show(Request $request, ...$args)
    {
        $allias_name = $args[0];
        $documents = $this->model::where('allias_name', $allias_name)->get();

        return $this->getFileInfos($documents);
    }

    public function getFileInfos($documents)
    {
        $media = $documents->map(function ($doc) {
            return $doc->getInfoMedia($doc);
        });
        $medias = $media->collapse()->values()->all();

        return response()->json([
            'data' => [
                'files_info' => $medias,
            ],
        ], 200);
    }

    protected function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
        $query->where('user_id', $request->user()->id);

        return $query;
    }

    protected function buildDestroyQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildDestroyQuery($request, $requestedRelations);
        $query->where('user_id', $request->user()->id);

        return $query;
    }

    protected function beforeStore(Request $request, $document)
    {
        $document->user_id = $request->user()->id;
        $document->allias_name = $this->randomAliasName();
    }

    /**
     * S'exécute APRES la création pour traiter l'upload des fichiers.
     * Supporte maintenant les fichiers uploadés via le système de chunking (TemporyFile)
     */
    protected function afterStore(Request $request, $document)
    {
        // Récupérer les fichiers temporaires uploadés par l'utilisateur
        $tempFiles = TemporyFile::where('user_id', $request->user()->id)
            ->where('collection', 'documents')
            ->where('confirmed', false)
            ->get();

        if ($tempFiles->isEmpty()) {
            return response()->json([
                'message' => __('documents.no_files_to_process'),
                'data' => new $this->resource($document),
            ], 400);
        }

        $tempFilePaths = [];
        $originalFileNames = [];
        $disk = $request->user()->getFileSystemDefault();
        $fileHashes = []; // Pour détecter les doublons

        // Ajouter les hashes des fichiers déjà attachés au document pour éviter les doublons cross-session
        $existingMedia = $document->getMedia('attachments');
        foreach ($existingMedia as $media) {
            try {
                $mediaPath = $media->getPath();
                if (Storage::disk($disk)->exists($mediaPath)) {
                    $existingContent = Storage::disk($disk)->get($mediaPath);
                    $existingHash = md5($existingContent);
                    $fileHashes[] = $existingHash;
                    Log::info("Hash du média existant {$media->id} ({$media->file_name}): {$existingHash}");
                }
            } catch (\Exception $e) {
                Log::warning("Impossible de lire le média existant {$media->id}: {$e->getMessage()}");
            }
        }

        // Utiliser les fichiers temporaires déjà uploadés
        // Filtrer uniquement les fichiers qui existent physiquement et éliminer les doublons
        foreach ($tempFiles as $tempFile) {
            if (! Storage::disk($disk)->exists($tempFile->path)) {
                // Supprimer le TemporyFile de la BDD si le fichier n'existe pas
                Log::warning("TemporyFile {$tempFile->id} ({$tempFile->original_name}) n'existe pas sur le disque, suppression de l'entrée BDD");
                $tempFile->delete();

                continue;
            }

            // Calculer le hash du fichier pour détecter les doublons
            $fileContent = Storage::disk($disk)->get($tempFile->path);
            $fileHash = md5($fileContent);

            // Vérifier si ce fichier est un doublon (dans la session actuelle ou déjà attaché au document)
            if (in_array($fileHash, $fileHashes)) {
                Log::warning("TemporyFile {$tempFile->id} ({$tempFile->original_name}) est un doublon (hash: {$fileHash}), suppression");
                // Supprimer le fichier physique et l'entrée BDD
                Storage::disk($disk)->delete($tempFile->path);
                $tempFile->delete();

                continue;
            }

            // Ajouter le fichier à la liste
            $fileHashes[] = $fileHash;
            $tempFilePaths[] = $tempFile->path;
            $originalFileNames[] = $tempFile->original_name;
        }

        // Vérifier qu'il reste au moins un fichier valide après filtrage
        if (empty($tempFilePaths)) {
            return response()->json([
                'message' => __('documents.no_valid_files_to_process'),
                'data' => new $this->resource($document),
            ], 400);
        }

        // Créer le job pour traiter les fichiers
        $job = new ProcessDocumentUploads(
            $tempFilePaths,
            $originalFileNames,
            $document->id,
        );

        // === CONFIGURATION DU JOB AVANT DISPATCH ===
        $jobStatusId = $job->getJobStatusId();
        $jobStatus = JobStatus::find($jobStatusId);
        if ($jobStatus) {
            $jobStatus->user_id = $request->user()->id;
            $jobStatus->status = 'queued';
            $jobStatus->save();

            Log::info("🎯 Job {$jobStatusId} configured for user {$request->user()->id}");
        }

        // NE PAS marquer les fichiers comme confirmés ici
        // Le job ProcessDocumentUploads les marquera comme confirmés après succès
        // Ou les supprimera en cas d'échec

        // === NOTIFICATION IMMEDIATE ===
        $activeJobs = JobStatus::where('user_id', $request->user()->id)
            ->whereIn('status', ['queued', 'executing'])
            ->latest()
            ->get();

        Log::info("📢 IMMEDIATE notification for user {$request->user()->id} with {$activeJobs->count()} active jobs");
        $request->user()->notify(new JobStatusUpdatedNotification($activeJobs, $request->user()));

        // === DISPATCH DU JOB ===
        $this->dispatch($job);
        Log::info("🚀 Job {$jobStatusId} dispatched to queue");

        // === NOTIFICATION DE CRÉATION ===
        NotificationService::sendNotificationToUser(
            $request->user(),
            __('documents.document_created_notification')
        );
        Log::info("📬 Document creation notification sent to user {$request->user()->id}");

        $formatted = (new $this->resource($document))
            ->toArray(request());

        return response()->json([
            'message' => __('documents.processing_started'),
            'data' => $formatted,
        ], 202);
    }

    /**
     * Génère un nom de dossier alias aléatoire.
     *
     * @param  int  $length  Longueur souhaitée du nom (défaut : 16)
     */
    public function randomAliasName(): string
    {
        $length = 16;
        // Génère un identifiant aléatoire en base64 et le nettoie
        $random = bin2hex(random_bytes($length / 2));

        // Préfixe optionnel pour indiquer un dossier
        return 'dir_'.$random;
    }

    protected function beforeUpdate(Request $request, $document)
    {
        $document->user_id = $request->user()->id;
    }

    public function update(Request $request, ...$args)
    {
        try {
            $name = $args[0];
            Log::info($name);
            $originalDocument = $this->model::where('allias_name', $name)->first();

            if ($request->hasFile('files')) {
                $files = $request->file('files');

                // Étape 1 : Cloner le document sans le slug
                $newDocument = $originalDocument->replicate(['slug']);
                $newDocument->save();

                // Étape 4 : Attacher les fichiers au nouveau document
                foreach ($files as $file) {
                    // Étape 2 : Assigner l'ID utilisateur au nouveau document
                    $newDocument->user_id = $request->user()->id;

                    $documentName = $file->getClientOriginalName();

                    if (strcmp($documentName, $originalDocument->name) == 0) {
                        $newDocument->name = $originalDocument->name.' - Copie';
                        $newDocument->description = $newDocument->name;
                    } else {
                        $newDocument->name = $documentName;
                        $newDocument->description = $documentName;
                        $newDocument->allias_name = $originalDocument->allias_name;
                    }

                    // Étape 3 : Sauvegarder le nouveau document
                    $newDocument->save();

                    Log::info('Nom du fichier uploadé : '.$documentName);

                    $newDocument->addMedia($file)
                        ->withCustomProperties([
                            'document_id' => $newDocument->id,
                        ])->toMediaCollection('attachments');
                }
            }

            return response()->json([
                'message' => __('documents.created'),
                'data' => new $this->resource($newDocument ?? $originalDocument),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du document.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, ...$args)
    {
        try {
            $mediaIdentifier = $args[0];

            DB::beginTransaction();

            if (is_numeric($mediaIdentifier)) {
                $this->deleteByMediaId($mediaIdentifier);
            } else {
                $this->deleteBySlug($mediaIdentifier);
            }

            DB::commit();

            // Récupérer la liste mise à jour
            $updatedList = $this->getUpdatedDocumentsList($request);

            // Modifier la réponse pour inclure le message de succès
            $responseData = json_decode($updatedList->getContent(), true);
            $allData = $responseData['data'];

            return response()->json([
                'message' => __('documents.deleted'),
                'data' => $allData,
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Document non trouvé',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression du document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprime un document par son ID de média
     */
    private function deleteByMediaId(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);
        $docId = $media->getCustomProperty('document_id');
        $document = $this->model::where('id', $docId)->firstOrFail();

        $media->delete();
        $document->delete();
    }

    /**
     * Supprime tous les documents ayant le même alias_name
     */
    private function deleteBySlug(string $slug): void
    {
        $document = $this->model::where('slug', $slug)->firstOrFail();
        $documentsWithAlias = $this->model::where('allias_name', $document->allias_name)->get();

        foreach ($documentsWithAlias as $doc) {
            $this->deleteDocumentWithMedia($doc);
        }
    }

    /**
     * Supprime un document avec tous ses médias associés
     */
    private function deleteDocumentWithMedia($document): void
    {
        $mediaItems = $document->getMedia('attachments');

        foreach ($mediaItems as $media) {
            $documentId = $media->getCustomProperty('document_id');

            if ($documentId && (int) $documentId === $document->id) {
                $media->delete();
            }
        }

        $document->delete();
    }

    /**
     * Retourne la liste mise à jour des documents de l'utilisateur
     */
    private function getUpdatedDocumentsList($request)
    {
        $documents = $this->model::where('user_id', $request->user()->id)->get();

        return $this->getFileInfos($documents);
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $slugs = $request->input('resources', []);

        if (empty($slugs)) {
            return response()->json(['message' => 'Aucun document à supprimer.'], 400);
        }

        DB::beginTransaction();

        try {
            foreach ($slugs as $slug) {
                $document = $this->model::where('slug', $slug)->first();

                if ($document) {
                    $documentsWithAlias = $this->model::where('allias_name', $document->allias_name)->get();

                    foreach ($documentsWithAlias as $doc) {
                        $this->deleteDocumentWithMedia($doc);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => __('documents.deleted'),
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erreur suppression en lot', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Erreur lors de la suppression des documents.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateFile(UpdateFileRequest $request, Media $media): JsonResponse
    {
        try {
            $document = $media->model;
            $disk = $request->user()->getFileSystemDefault();

            if (! $document instanceof \App\Models\Document) {
                return response()->json(['message' => 'Fichier non trouvé.'], 404);
            }

            if (! $request->hasFile('file')) {
                return response()->json(['message' => 'Aucun fichier fourni.'], 400);
            }

            DB::beginTransaction();

            try {
                $file = $request->file('file');
                $fileName = $file->getClientOriginalName();

                // 1. Supprimer l'ancien fichier physique
                $media->delete();

                // 2. Mettre à jour le document
                $document->name = $fileName;
                $document->description = $fileName;
                $document->save();

                // 3. Ajouter le nouveau fichier
                $newMedia = $document->addMedia($file)
                    ->withCustomProperties([
                        'document_id' => $document->id,
                    ])
                    ->usingName($fileName)
                    ->toMediaCollection('attachments', $disk);

                // 4. Forcer la synchronisation avec le stockage SFTP
                $newMedia->refresh(); // Rafraîchir l'instance média

                DB::commit();

                return response()->json([
                    'message' => __('documents.updated'),
                    'data' => new $this->resource($document ?? $newMedia),
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erreur lors du remplacement du fichier', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Erreur lors du remplacement du fichier.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du fichier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function serveFile(Request $request, $documentIdentifier, $filename)
    {
        try {
            // Retrouver le document par son slug ou son ID
            $document = Document::where('slug', $documentIdentifier)
                ->orWhere('id', $documentIdentifier)
                ->firstOrFail();

            // Retrouver le média spécifique attaché à ce document par son nom de fichier
            $media = $document->getMedia('attachments')
                ->where('file_name', $filename)
                ->first();

            // Si le média n'est pas trouvé dans la base de données, renvoyer une erreur 404
            if (! $media) {
                abort(404, 'File not found in database');
            }

            // Générer la réponse de base à partir de l'objet Media
            $response = $media->toResponse($request);

            $response->headers->add([
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
                'X-Content-Type-Options' => 'nosniff',
                'Access-Control-Allow-Origin' => '*',
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('File serving error', [
                'error' => $e->getMessage(),
                'document' => $documentIdentifier,
                'filename' => $filename,
            ]);
            abort(404, 'Unable to serve file.');
        }
    }

    protected function disableLocaleHandling()
    {
        $this->originalLocale = app()->getLocale();
        app()->setLocale('en');
        Carbon::setLocale('en');
    }

    protected function restoreLocaleHandling()
    {
        if (isset($this->originalLocale)) {
            app()->setLocale($this->originalLocale);
            Carbon::setLocale($this->originalLocale);
        }
    }
}
