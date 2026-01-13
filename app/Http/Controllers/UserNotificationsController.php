<?php

namespace App\Http\Controllers;

use App\Http\Resources\Collections\NotificationCollection;
use App\Http\Resources\NotificationResource;
use App\Notifications\UserSpecificNotification; // Si vous utilisez des API Resources
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserNotificationsController extends Controller
{
    /**
     * Récupère les notifications de l'utilisateur (pour affichage initial ou complet).
     * CECI EST UN ENDPOINT API HTTP, PAS POUR WEBSOCKET.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = $user->notifications(); // Notifications stockées via le canal 'database'

        if ($request->boolean('unread')) { // Utiliser boolean() pour plus de robustesse
            $query->whereNull('read_at');
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10)); // Utiliser input()

        // Avec une Resource Collection (recommandé pour structurer les réponses API)
        return (new NotificationCollection($notifications))
            ->additional(['meta' => ['unreadnotificationcount' => $user->unreadNotifications()->count()]]);
    }

    /**
     * Exemple d'action qui génère une nouvelle notification pour l'utilisateur courant.
     * (Cela pourrait être dans un autre contrôleur/service, selon votre logique métier)
     */
    public function triggerNewNotification(Request $request)
    {
        $user = Auth::user();

        $messageContent = __('notifications.show');

        // Utiliser le service pour envoyer la nouvelle notification
        // Ceci va la stocker en BDD ET la diffuser via WebSocket
        NotificationService::sendNewNotification($messageContent);

        $currentUnreadCount = DatabaseNotification::where('notifiable_id', 51)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unreadnotificationcount' => $currentUnreadCount, // Le compte après l'envoi
        ]);
    }

    /**
     * Marquer des notifications comme lues.
     */
    public function markAsRead(Request $request)
    {
        $user = Auth::user();
        $notificationIds = $request->query('notification_id'); // Récupérer les IDs depuis l'URL

        // Convertir en tableau si c'est une chaîne unique
        $notificationIds = is_string($notificationIds) ? [$notificationIds] : $notificationIds;

        if (! empty($notificationIds)) {
            DatabaseNotification::where('type', UserSpecificNotification::class)->whereIn('id', $notificationIds)->update(['read_at' => now()]);
        } else {
            // Option: marquer toutes comme lues si aucun ID n'est fourni
            DatabaseNotification::where('type', UserSpecificNotification::class)->update(['read_at' => now()]);
        }

        // Après avoir marqué comme lu, diffuser le nouveau compte
        NotificationService::broadcastUnreadCount($user);

        return response()->json([
            'message' => 'Notifications marquées comme lues.',
            'unreadnotificationcount' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Marquer des notifications de la colonne data qui ont pour contenu notification_type=chat et model_type=0197a78c-929d-72a1-a80d-080f7a8d92b2 de l'utilisateur comme lues.
     */
    public function markAsReadChat(Request $request, $chatId)
    {
        try {
            // Récupérer le message/chat

            $userId = Auth::user()->id;
            $user = Auth::user();

            // $chatId = "0197abbf-7907-71f6-a7d1-8bf62c466d49";

            // Récupérer les IDs des notifications avant de les marquer comme lues
            $notificationsToUpdate = DatabaseNotification::where('notifiable_id', $userId)
                ->whereNull('read_at')
                ->whereJsonContains('data->notification_type', 'chat')
                ->whereJsonContains('data->model_type', $chatId)
                ->pluck('id')
                ->toArray();

            // Marquer comme lues
            $updatedCount = DatabaseNotification::whereIn('id', $notificationsToUpdate)
                ->update(['read_at' => now()]);

            return $this->getDataNotification($user);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des notifications.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getDataNotification($user): array
    {
        try {
            // Comptage sécurisé des notifications non lues
            $currentUnreadCount = DatabaseNotification::where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->count();

            // Récupération des 3 dernières notifications non lues
            $recentUnread = DatabaseNotification::where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->latest()
                ->take(5)
                ->get();

            // Formatage sécurisé des notifications
            $formatted = [];
            if ($recentUnread->isNotEmpty()) {
                try {
                    $formatted = NotificationResource::collection($recentUnread)->toArray(request());
                } catch (\Exception $e) {
                    Log::warning('Erreur lors du formatage des notifications', [
                        'error' => $e->getMessage(),
                    ]);
                    $formatted = [];
                }
            }

            return [
                'unreadnotificationcount' => $currentUnreadCount,
                'recent_unread_list' => $formatted,
            ];
        } catch (\Exception $e) {
            return [
                'unreadnotificationcount' => 0,
                'recent_unread_list' => [],
                'error' => true,
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    public function listAllReadNotifications()
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $notifications = $user->readNotifications()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return (new NotificationCollection($notifications))
            ->additional(['readnotificationcount' => $user->readNotifications()->count()]);
    }

    /**
     * Marquer toutes les notifications venant d'autres utilisateurs comme lues.
     */
    /**
     * Marquer toutes les notifications venant d'autres utilisateurs comme lues. OTHER
     */
    public function markReceivedNotificationsAsRead(Request $request)
    {
        $user = Auth::user();

        // Marquer comme lues les notifications :
        // - Où l'utilisateur connecté EST LE DESTINATAIRE
        // - Et qui ont été ENVOYÉES PAR D'AUTRES UTILISATEURS (notifiable_id != user->id)
        $updated = DB::table('notifications')
            ->whereNull('read_at')
            ->where('notifiable_type', 'App\Models\User') // Adaptez au modèle cible
            ->where('notifiable_id', '!=', $user->id) // Exclut les notifications envoyées par l'utilisateur
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => "$updated notifications reçues marquées comme lues",
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }
}
