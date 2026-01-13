<?php

namespace App\Services;

use App\Events\UserNotificationCountUpdated;
use App\Models\User;
use App\Notifications\UserSpecificNotification;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    public static function sendNewNotification($message_content): void
    {
        $user = Auth::user();

        // Données simples, pas d'objets complexes
        $data = [
            'user' => $user,
            'message' => $message_content,
            'notification_type' => 'user',
            'chat_partner_name' => $user->first_name, // ou first_name
            'created_at' => now()->toISOString(),
        ];

        $user->notify(new UserSpecificNotification($user, $data));
    }

    public static function sendNotificationToUser(User $user, $message_content): void
    {
        $data = [
            'user' => $user,
            'message' => $message_content,
            'notification_type' => 'user',
            'chat_partner_name' => $user->first_name,
            'created_at' => now()->toISOString(),
        ];

        $user->notify(new UserSpecificNotification($user, $data));
    }

    /**
     * Diffuse uniquement la mise à jour du compteur de notifications non lues.
     * Utile après avoir marqué des notifications comme lues/non lues sans envoyer une nouvelle notification.
     *
     * @param  User  $user
     */
    public static function broadcastUnreadCount($user): void
    {

        $user = Auth::user();
        event(new UserNotificationCountUpdated($user, $user->unreadNotifications()->count()));
    }
}
