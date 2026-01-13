<?php

namespace App\Http\Controllers;

use App\Jobs\SimpleBackupJob; // Notre nouveau Job
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function run(Request $request)
    {
        // Vérification simple pour éviter de lancer plusieurs jobs si le précédent n'est pas encore marqué comme terminé
        // (le SimpleBackupJob::CACHE_KEY_JOB_RUNNING_ID est nettoyé dans le finally ou failed() du job)
        if (Cache::has(SimpleBackupJob::CACHE_KEY_JOB_RUNNING_ID)) {
            $runningJobId = Cache::get(SimpleBackupJob::CACHE_KEY_JOB_RUNNING_ID);
            Log::warning('BackupController@run: Attempt to run new backup while a job (ID: '.$runningJobId.') is marked as running.');

            return response()->json([
                'message' => 'A backup process is already in progress (Job ID: '.$runningJobId.'). Please wait.',
                'status' => 'already_running', // Un statut client pour indiquer cela
            ], 409); // Conflict
        }

        try {
            $user = Auth::user();
            $job = new SimpleBackupJob($user); // Crée une instance pour obtenir jobIdentifier si besoin
            Log::info('BackupController@run: Dispatching SimpleBackupJob with ID: '.$job->jobIdentifier);

            // Optionnel: mettre un indicateur que le dispatch a eu lieu, le job le remplacera.
            // Cache::put(SimpleBackupJob::CACHE_KEY_JOB_RUNNING_ID, $job->jobIdentifier, now()->addMinutes(5)); // TTL court

            dispatch($job); // Dispatche le job

            // Répondre immédiatement au client
            return response()->json([
                'message' => __('backup.notify'),
                'status' => 'dispatched', // Le client sait qu'il doit attendre une notif WebSocket
                'backup_identifier' => $job->jobIdentifier, // Si le client veut le suivre (optionnel)
            ]);
        } catch (\Exception $e) {
            Log::error('BackupController@run: Exception during job dispatch: '.$e->getMessage());

            return response()->json(['message' => 'Failed to dispatch backup job: '.$e->getMessage()], 500);
        }
    }

    // Endpoint optionnel pour que le client vérifie si un backup est marqué comme "en cours"
    // Utile si l'utilisateur recharge la page.
    public function getStatus(Request $request)
    {
        $runningJobId = Cache::get(SimpleBackupJob::CACHE_KEY_JOB_RUNNING_ID);
        if ($runningJobId) {
            return response()->json([
                'status' => 'running',
                'message' => 'A backup process (Job ID: '.$runningJobId.') is currently in progress.',
                'backup_identifier' => $runningJobId,
            ]);
        } else {
            return response()->json([
                'status' => 'idle',
                'message' => 'No backup process is currently active.',
            ]);
        }
    }
}
