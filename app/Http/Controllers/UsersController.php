<?php

namespace App\Http\Controllers;

use App\Events\PartnerBlockStatusUpdated;
use App\Helpers\ResponseServer;
use App\Http\Requests\UsersRequest;
use App\Http\Resources\Collections\UsersCollection;
use App\Http\Resources\UsersResource;
use App\Jobs\ProcessUserFiles;
use App\Models\Conversation; // Make sure this import is present
use App\Models\User;
use App\Notifications\sendEmailNotification;
use App\Notifications\UserBlockedNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Orion\Http\Controllers\Controller;

class UsersController extends Controller
{
    /**
     * The Eloquent model associated with this controller.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * The resource associated with the model.
     *
     * @var string
     */
    protected $resource = UsersResource::class;

    /**
     * The collection resource associated with the model.
     *
     * @var string
     */
    protected $collectionResource = UsersCollection::class;

    /**
     * The request class for validation.
     *
     * @var string
     */
    protected $request = UsersRequest::class;

    /**
     * Default pagination limit.
     */
    public function limit(): int
    {
        return config('app.limit_pagination');
    }

    /**
     * Maximum pagination limit.
     */
    public function maxLimit(): int
    {
        return config('app.max_pagination');
    }

    /**
     * The attributes that are used for searching.
     */
    public function searchableBy(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    /**
     * Endpoint pour obtenir les statistiques des utilisateurs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $startDate = Carbon::now()->subDays(29)->startOfDay(); // Inclut aujourd'hui
        $endDate = Carbon::now()->endOfDay();

        // Données groupées par jour : nombre d'utilisateurs créés par date
        $dailyUsers = User::selectRaw('DATE(created_at) as date, COUNT(*) as uniqueUsers')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Construction du tableau complet avec 0 pour les jours sans inscription
        $historicalData = [];
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->toDateString();
            $historicalData[] = [
                'date' => $formattedDate,
                'uniqueUsers' => $dailyUsers[$formattedDate]->uniqueUsers ?? 0,
            ];
        }

        return response()->json([
            'historical_data' => $historicalData,
        ]);
    }

    /**
     * Block a user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function blockUser(string $slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();

        if ($user->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already blocked',
            ], 400);
        }
        $user->blockUser();
        $user->notify(new UserBlockedNotification($user, true));
        $type = 'account_blocked';
        $user->notify(new sendEmailNotification($type, $user, config('app.url_frontend'), $user->getLocale()));
        ResponseServer::logoutSucessforUserBlocked($user);

        $messageContent = __('user.blocked', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]);

        NotificationService::sendNewNotification($messageContent);

        // Notifier tous les partenaires de conversation
        $this->notifyConversationPartners($user, true);

        return response()->json([
            'success' => true,
            'message' => __('user.blocked'),
            'data' => new UsersResource($user),
        ]);
    }

    /**
     * Unblock a user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function unblockUser(string $slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();

        if (! $user->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not blocked',
            ], 400);
        }
        $user->unblockUser();
        $user->notify(new UserBlockedNotification($user, false));
        $type = 'unaccount_blocked';
        $user->notify(new sendEmailNotification($type, $user, config('app.url_frontend'), $user->getLocale()));

        if ($user->isBlocked()) {
            ResponseServer::logoutSucessforUserBlocked($user);
        }

        $messageContent = __('user.unblocked', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ]);

        NotificationService::sendNewNotification($messageContent);

        // Notifier tous les partenaires de conversation
        $this->notifyConversationPartners($user, false);

        return response()->json([
            'success' => true,
            'message' => __('user.unblocked'),
            'data' => new UsersResource($user),
        ]);
    }

    /**
     * Toggle user block status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleBlock(string $slug)
    {
        $user = User::where('slug', $slug)->firstOrFail();
        $user->toggleBlock();
        $user->notify(new UserBlockedNotification($user, $user->isBlocked()));

        $type = $user->isBlocked() ? 'account_blocked' : 'unaccount_blocked';
        $user->notify(new sendEmailNotification($type, $user, config('app.url_frontend'), $user->getLocale()));

        $messageContent = $user->isBlocked()
            ? __('user.blocked', ['first_name' => $user->first_name, 'last_name' => $user->last_name])
            : __('user.unblocked', ['first_name' => $user->first_name, 'last_name' => $user->last_name]);
        NotificationService::sendNewNotification($messageContent);

        // Notifier tous les partenaires de conversation
        $this->notifyConversationPartners($user, $user->isBlocked());

        $message = $user->isBlocked() ? __('user.blocked') : __('user.unblocked');

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new UsersResource($user),
        ]);
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     */
    protected function afterStore($request, $user)
    {

        if (! $request->filled('password')) {
            $generatedPassword = 'P@sser12';
            $user->password = Hash::make($generatedPassword);
        }

        // Générer remember_token et marquer l'email comme vérifié lors de la création
        $user->remember_token = Str::random(60);
        $user->email_verified_at = now();
        $user->save();

        if ($request->has('roles')) {
            $roles = is_array($request->roles) ? $request->roles : explode(',', $request->roles);
            $user->syncRoles($roles);
        } else {
            // Rôle par défaut
            $user->assignRole('user');
        }
        if ($request->has('permissions')) {
            $permissions = is_array($request->permissions)
                ? $request->permissions
                : explode(',', $request->permissions);
            $user->syncPermissions($permissions);
        }
        $this->processUploadFile($request, $user);

        \Log::info("User created: {$user->id}");
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     */
    protected function afterUpdate($request, $user)
    {
        // Gestion du pays
        if ($request->has('country_id')) {
            $countryId = $request->country_id;

            // Convertir en null si vide ou 0
            $countryId = ! empty($countryId) ? (int) $countryId : null;

            $user->country_id = $countryId;
            $user->save();
        }

        // Gestion des rôles et permissions...
        if ($request->has('roles')) {
            $roles = is_array($request->roles) ? $request->roles : explode(',', $request->roles);
            $user->syncRoles($roles);
        }

        if ($request->has('permissions')) {
            $permissions = is_array($request->permissions)
                ? $request->permissions
                : explode(',', $request->permissions);
            $user->syncPermissions($permissions);
        }

        $this->processUploadFile($request, $user);
        \Log::info("User updated: {$user->id}");
    }

    /**
     * Met à jour le pays de l'utilisateur
     */
    protected function updateUserCountry($user, $countryData)
    {

        if ($countryData === 'undefined' || $countryData === 'null') {
            $user->country()->dissociate();
            $user->save();

            return;
        }
        // Si c'est un objet/tableau avec ID
        if (is_array($countryData) && isset($countryData['id'])) {
            $countryId = $countryData['id'];
        }
        // Si c'est directement l'ID
        elseif (is_numeric($countryData)) {
            $countryId = $countryData;
        }
        // Si c'est un objet JSON stringifié
        elseif (is_string($countryData)) {
            $decoded = json_decode($countryData, true);
            $countryId = $decoded['id'] ?? null;
        } else {
            $countryId = null;
        }

        if ($countryId) {
            $user->country()->associate($countryId);
            $user->save();
        } elseif ($countryData === null) {
            // Pour retirer le pays
            $user->country()->dissociate();
            $user->save();
        }
    }

    public function keyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     */
    protected function beforeDestroy($request, $user)
    {
        $disk = 'profils';

        // Vérifier si l'utilisateur a des médias associés
        if ($user->media) {
            try {
                // Supprimer tous les fichiers associés au média
                $user->media->delete(); // Cela déclenchera aussi la suppression physique si configuré

                // Optionnel : Supprimer le répertoire parent s'il est vide
                $directory = $disk.'/'.$user->media->id;
                if (Storage::disk($disk)->exists($directory)) {
                    Storage::disk($disk)->deleteDirectory($directory);
                }
            } catch (\Exception $e) {
                \Log::error("Erreur lors de la suppression des médias pour l'utilisateur {$user->id}: ".$e->getMessage());
            }
        }
        \Log::info("User updated: {$user->id}");
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     */
    protected function beforeBatchDestroy($request): void
    {
        $disk = 'profils';

        // Récupère les IDs des utilisateurs à supprimer depuis la requête
        $userIds = $request->input('resources', []);

        if (empty($userIds)) {
            return;
        }

        // Charge les utilisateurs avec leurs médias en une seule requête
        $users = \App\Models\User::with('media')
            ->whereIn('id', $userIds)
            ->get();

        foreach ($users as $user) {
            if ($user->media) {
                try {
                    // Supprime le média et les fichiers associés
                    $mediaId = $user->media->id;
                    $user->media->delete();

                    // Supprime le répertoire associé
                    $directory = "{$disk}/{$mediaId}";
                    if (Storage::disk($disk)->exists($directory)) {
                        Storage::disk($disk)->deleteDirectory($directory);
                    }

                    \Log::info("Média supprimé pour l'utilisateur", ['user_id' => $user->id]);
                } catch (\Exception $e) {
                    \Log::error('Échec de la suppression des médias', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function processUploadFile($request, $user)
    {
        if ($request->hasFile('photo')) {
            // Get the file from the request
            $file = $request->file('photo');
            $collection = 'profils';

            // Generate a unique name for the file
            $originalName = $file->getClientOriginalName();
            $fileName = pathinfo($originalName, PATHINFO_FILENAME);
            $fileExtension = $file->getClientOriginalExtension();
            $storedFileName = $fileName.'_'.time().'.'.$fileExtension;

            // stockage des fichiers dans le repertoire TemporaryDirectory
            $disk = config('filesystems.default'); // Utilisez le disque par défaut
            $temporaryDirectoryDisk = 'temporaryDirectory';
            $tempDirectory = $collection.'/'.$user->id;

            $finalPath = "{$temporaryDirectoryDisk}/{$tempDirectory}";
            $storage = Storage::disk($disk);

            // Préparer le stockage
            // Storage::disk($temporaryDirectoryDisk)->makeDirectory($tempDirectory);
            $storage->makeDirectory($finalPath);

            // Store the file content, not just the filename
            $result = $storage->putFileAs(
                $finalPath,
                $file,
                $storedFileName
            );

            ProcessUserFiles::dispatch($user, $collection, $temporaryDirectoryDisk);
            \Log::info("Photo uploaded for user: {$user->id} - Filename: {$storedFileName}");
        }
    }

    /**
     * Notify all conversation partners about the user block status change
     */
    private function notifyConversationPartners(User $user, bool $isBlocked): void
    {
        // Récupérer toutes les conversations où l'utilisateur participe
        $conversations = Conversation::whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        // Déclencher l'événement pour chaque conversation
        foreach ($conversations as $conversation) {
            event(new PartnerBlockStatusUpdated($user, $isBlocked, $conversation));
        }

        Log::info('Notified '.$conversations->count()." conversations about user {$user->id} block status change: ".($isBlocked ? 'blocked' : 'unblocked'));
    }
}
