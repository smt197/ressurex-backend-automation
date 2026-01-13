<?php

namespace App\Http\Controllers;

use App\Models\TemporyFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
     * Upload a file chunk (max 10MB per file)
     */
    public function uploadChunk(Request $request)
    {
        // Augmenter les limites pour les uploads
        set_time_limit(300);
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '256M');

        $request->validate([
            'file' => 'required|file',
            'chunkIndex' => 'required|integer',
            'totalChunks' => 'required|integer',
            'fileIdentifier' => 'required|string',
            'originalName' => 'required|string',
            'collection' => 'required|string',
            'hashField' => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $fileIdentifier = $request->input('fileIdentifier');
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');
        $originalName = $request->input('originalName');
        $collection = $request->input('collection');
        $hashField = $request->input('hashField', 'document_hash');

        try {
            $defaultDisk = $request->user()->getFileSystemDefault();

            // Créer un répertoire temporaire pour les chunks
            $chunksDir = "temp/chunks/{$userId}/{$fileIdentifier}";

            // Sauvegarder le chunk
            $chunkFile = $request->file('file');
            $chunkPath = "{$chunksDir}/chunk_{$chunkIndex}";

            Storage::disk($defaultDisk)->put($chunkPath, file_get_contents($chunkFile->getRealPath()));

            Log::info("Chunk {$chunkIndex}/{$totalChunks} uploadé pour {$fileIdentifier}");

            // Si tous les chunks sont uploadés, assembler le fichier
            if ($this->areAllChunksUploaded($defaultDisk, $chunksDir, $totalChunks)) {
                // Calculer la taille totale avant assemblage
                $totalSize = $this->calculateTotalChunksSize($defaultDisk, $chunksDir, $totalChunks);
                $maxSize = 10 * 1024 * 1024; // 10MB

                if ($totalSize > $maxSize) {
                    // Nettoyer les chunks
                    Storage::disk($defaultDisk)->deleteDirectory($chunksDir);

                    Log::warning("Fichier rejeté : {$originalName} ({$totalSize} bytes) dépasse la limite de {$maxSize} bytes");

                    return response()->json([
                        'success' => false,
                        'message' => 'Le fichier est trop volumineux ('.round($totalSize / (1024 * 1024), 2).' MB). La taille maximale autorisée est de 10 MB.',
                        'completed' => false,
                    ], 413); // 413 Payload Too Large
                }

                $finalPath = $this->assembleChunks($defaultDisk, $chunksDir, $totalChunks, $userId, $originalName);

                // Enregistrer dans TemporyFile
                $tempFile = TemporyFile::create([
                    'user_id' => $userId,
                    'original_name' => $originalName,
                    'path' => $finalPath,
                    'type' => pathinfo($originalName, PATHINFO_EXTENSION),
                    'collection' => $collection,
                    'hash_field' => $hashField,
                    'uploaded_at' => now(),
                    'confirmed' => false,
                ]);

                // Nettoyer les chunks
                Storage::disk($defaultDisk)->deleteDirectory($chunksDir);

                return response()->json([
                    'success' => true,
                    'message' => 'Fichier assemblé avec succès',
                    'completed' => true,
                    'data' => $tempFile,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Chunk {$chunkIndex}/{$totalChunks} reçu",
                'completed' => false,
                'progress' => round(($chunkIndex + 1) / $totalChunks * 100, 2),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload du chunk: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du chunk: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if all chunks are uploaded
     */
    private function areAllChunksUploaded(string $disk, string $chunksDir, int $totalChunks): bool
    {
        $uploadedChunks = 0;

        for ($i = 0; $i < $totalChunks; $i++) {
            if (Storage::disk($disk)->exists("{$chunksDir}/chunk_{$i}")) {
                $uploadedChunks++;
            }
        }

        return $uploadedChunks === $totalChunks;
    }

    /**
     * Calculate total size of all chunks
     */
    private function calculateTotalChunksSize(string $disk, string $chunksDir, int $totalChunks): int
    {
        $totalSize = 0;

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "{$chunksDir}/chunk_{$i}";
            if (Storage::disk($disk)->exists($chunkPath)) {
                $totalSize += Storage::disk($disk)->size($chunkPath);
            }
        }

        return $totalSize;
    }

    /**
     * Assemble all chunks into a single file
     */
    private function assembleChunks(string $disk, string $chunksDir, int $totalChunks, int $userId, string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = Str::uuid().'.'.$extension;
        $finalPath = "temp/uploads/{$userId}/{$fileName}";

        // Créer un fichier temporaire local pour assembler les chunks
        $tempLocalFile = storage_path('app/temp_assembly/'.$fileName);
        $tempDir = dirname($tempLocalFile);

        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $handle = fopen($tempLocalFile, 'wb');

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = "{$chunksDir}/chunk_{$i}";
                $chunkContent = Storage::disk($disk)->get($chunkPath);
                fwrite($handle, $chunkContent);
            }
        } finally {
            fclose($handle);
        }

        // Uploader le fichier assemblé vers le stockage
        Storage::disk($disk)->put($finalPath, file_get_contents($tempLocalFile));

        // Nettoyer le fichier temporaire local
        @unlink($tempLocalFile);

        Log::info("Fichier assemblé avec succès: {$finalPath}");

        return $finalPath;
    }

    /**
     * Upload a complete file (fallback for non-chunked upload, max 10MB)
     */
    public function uploadComplete(Request $request)
    {
        // Augmenter les limites pour les uploads
        set_time_limit(300);
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '256M');

        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
            'originalName' => 'required|string',
            'collection' => 'required|string',
            'hashField' => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $file = $request->file('file');
        $originalName = $request->input('originalName');
        $collection = $request->input('collection');
        $hashField = $request->input('hashField', 'document_hash');

        try {
            $defaultDisk = $request->user()->getFileSystemDefault();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid().'.'.$extension;
            $filePath = "temp/uploads/{$userId}/{$fileName}";

            // Sauvegarder le fichier
            Storage::disk($defaultDisk)->put($filePath, file_get_contents($file->getRealPath()));

            // Enregistrer dans TemporyFile
            $tempFile = TemporyFile::create([
                'user_id' => $userId,
                'original_name' => $originalName,
                'path' => $filePath,
                'type' => $extension,
                'collection' => $collection,
                'hash_field' => $hashField,
                'uploaded_at' => now(),
                'confirmed' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fichier uploadé avec succès',
                'data' => $tempFile,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload du fichier: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a temporary file
     */
    public function deleteFile(Request $request, int $fileId)
    {
        $userId = $request->user()->id;

        try {
            $tempFile = TemporyFile::where('id', $fileId)
                ->where('user_id', $userId)
                ->where('confirmed', false)
                ->firstOrFail();

            $defaultDisk = $request->user()->getFileSystemDefault();

            // Supprimer le fichier du stockage
            if (Storage::disk($defaultDisk)->exists($tempFile->path)) {
                Storage::disk($defaultDisk)->delete($tempFile->path);
            }

            // Supprimer l'entrée de la base de données
            $tempFile->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fichier supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du fichier: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
            ], 500);
        }
    }

    /**
     * Get all unconfirmed temporary files for current user
     */
    public function getUserFiles(Request $request)
    {
        $userId = $request->user()->id;

        $files = TemporyFile::where('user_id', $userId)
            ->where('confirmed', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $files,
        ]);
    }

    /**
     * Clear all unconfirmed temporary files
     */
    public function clearUserFiles(Request $request)
    {
        $userId = $request->user()->id;

        try {
            $files = TemporyFile::where('user_id', $userId)
                ->where('confirmed', false)
                ->get();

            $defaultDisk = $request->user()->getFileSystemDefault();

            foreach ($files as $file) {
                // Supprimer le fichier du stockage
                if (Storage::disk($defaultDisk)->exists($file->path)) {
                    Storage::disk($defaultDisk)->delete($file->path);
                }

                // Supprimer l'entrée de la base de données
                $file->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Fichiers temporaires supprimés avec succès',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression des fichiers temporaires: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
            ], 500);
        }
    }
}
