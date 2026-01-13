<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('user-unreadcount.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal pour les messages d'une conversation spécifique
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    // Vérifier si l'utilisateur fait partie de cette conversation
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});
// routes/channels.php

// ... (vos canaux existants, comme chat.{conversationId})

// Canal de présence global pour les utilisateurs en ligne
Broadcast::channel('presence-online-users', function ($user) {
    if ($user) {
        return ['id' => $user->id, 'first_name' => $user->first_name]; // Ou first_name etc.
    }

    return false;
});

Broadcast::channel('job-status.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
