<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessageSent;
use App\Events\UserNotificationCountUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageResource;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\UserSpecificNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Pour des requêtes plus complexes si besoin
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Importer Str

class ChatController extends Controller
{
    /**
     * Récupère la liste des conversations pour l'utilisateur authentifié.
     * Prend en charge un paramètre de recherche 'q' pour filtrer par nom de participant.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        // Récupérer le terme de recherche de la requête.
        $searchTerm = $request->query('q');

        $conversationsQuery = $user->conversations()
            ->with([
                'participants' => function ($query) use ($user) {
                    $query->where('users.id', '!=', $user->id);
                },
                'latestMessage',
            ])
            // Utiliser une clause 'when' pour appliquer le filtre de recherche de manière conditionnelle.
            ->when($searchTerm, function ($query, $term) use ($user) {
                // Recherche les conversations où le nom de l'AUTRE participant correspond au terme.
                return $query->whereHas('participants', function ($subQuery) use ($term, $user) {
                    $subQuery->where('users.id', '!=', $user->id) // S'assurer que nous ne recherchons pas le nom de l'utilisateur actuel
                        ->where(function ($nameQuery) use ($term) {
                            // Recherche dans le prénom OU le nom de famille
                            $nameQuery->where('first_name', 'LIKE', "%{$term}%")
                                ->orWhere('last_name', 'LIKE', "%{$term}%");
                        });
                });
            })
            ->select('conversations.*')
            ->leftJoin('messages as latest_msg', function ($join) {
                $join->on('conversations.id', '=', 'latest_msg.conversation_id')
                    ->whereRaw('latest_msg.id = (select id from messages where conversation_id = conversations.id order by created_at desc limit 1)');
            })
            ->orderByRaw('COALESCE(latest_msg.created_at, conversations.updated_at) DESC');

        // Exécuter la requête
        $conversations = $conversationsQuery->get();

        // Le reste de la logique de mappage reste inchangé.
        $chatList = $conversations->map(function ($conversation) use ($user) {
            $otherParticipant = $conversation->participants->first();

            if (! $otherParticipant) {
                return null;
            }

            $lastMessage = $conversation->latestMessage;

            $unreadCount = $conversation->messages()
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();

            return [
                'id' => $conversation->id,
                'imageUrl' => $otherParticipant->photo ?? asset('images/default_avatar.png'),
                'first_name' => $otherParticipant->first_name,
                'lastMessage' => $lastMessage ? Str::limit($lastMessage->message, 30) : 'No messages yet.',
                'unreadCount' => $unreadCount,
                'partnerId' => $otherParticipant->id,
                'timestamp' => $lastMessage ? $lastMessage->created_at?->format(__('dateformatTime.datekey')) : $conversation->updated_at->toIso8601String(),
                'is_blocked' => $otherParticipant->is_blocked,
            ];
        })->filter();

        return response()->json($chatList);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $conversationId)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->firstOrFail();

        // Charger TOUS les messages de la conversation
        $messagesQuery = $conversation->messages()
            ->with('sender:id,first_name,photo') // Seulement les champs nécessaires pour l'expéditeur
            // Décidez de l'ordre :
            // ->orderBy('created_at', 'asc') // Plus ancien en premier (recommandé si vous les affichez dans cet ordre)
            ->orderBy('created_at', 'desc') // Plus récent en premier (si vous voulez inverser côté client)
            ->get();

        // Marquer les messages comme lus par l'utilisateur actuel (ceux envoyés par d'autres)
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'data' => $messagesQuery->map(function ($message) use ($user) { // Utiliser $messagesQuery ici
                return [
                    'id' => $message->id,
                    'from' => $message->sender_id === $user->id ? 'me' : 'partner',
                    'message' => $message->message,
                    'timestamp' => $message->created_at?->format(__('dateformatTime.datekey')),
                    'id' => $message->id,
                    'from' => $message->sender_id === $user->id ? 'me' : 'partner',
                    'message' => $message->message, // Texte du message/légende
                    // AJOUTER LES CHAMPS POUR LES FICHIERS/IMAGES
                    'message_type' => $message->message_type,
                    'file_url' => $message->file_url, // L'accesseur s'en charge
                    'file_name' => $message->file_name,
                    'file_mime_type' => $message->file_mime_type,
                    'file_size' => $message->file_size,
                ];
            }),
            // Plus de 'links' ni de 'meta' car il n'y a pas de pagination
        ]);
    }

    /**
     * Envoie un nouveau message dans une conversation.
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $user = Auth::user();
        $sender = Auth::user();
        $conversation = Conversation::findOrFail($conversationId);

        $recipient = $conversation->participants()->where('users.id', '!=', $user->id)->first();

        if ($recipient && $recipient->is_blocked) {
            return response()->json(['message' => 'This user is blocked.'], 403);
        }

        // Validation
        $validated = $request->validate([
            'message' => 'nullable|string|max:5000|required_without:attachment', // Message requis si pas de fichier
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip|max:10240', // Max 10MB, ajustez les types et la taille
        ]);

        $messageText = $validated['message'] ?? null;
        $attachmentFile = $request->file('attachment'); // Récupérer le fichier

        $messageData = [
            'sender_id' => $user->id,
            'message' => $messageText,
            'message_type' => 'text', // Type par défaut
        ];

        if ($attachmentFile) {
            // Déterminer le type de message basé sur le MIME type
            $mimeType = $attachmentFile->getMimeType();
            if (Str::startsWith($mimeType, 'image/')) {
                $messageData['message_type'] = 'image';
            } else {
                $messageData['message_type'] = 'file';
            }

            // Stocker le fichier
            // Le chemin sera 'chat_attachments/user_{id}/année/mois/nom_fichier_unique.ext'
            $filePath = $attachmentFile->store(
                'chats/'.'user_'.$user->id.'/'.now()->format('Y/m'), // Crée des sous-dossiers par utilisateur/année/mois
                'chat_attachments' // Nom du disque configuré dans filesystems.php
            );

            if (! $filePath) {
                return response()->json(['message' => 'Failed to upload attachment.'], 500);
            }

            $messageData['file_path'] = $filePath;
            $messageData['file_name'] = $attachmentFile->getClientOriginalName();
            $messageData['file_mime_type'] = $mimeType;
            $messageData['file_size'] = $attachmentFile->getSize();

            if (! $messageText) { // Si aucun message texte n'est fourni avec le fichier
                $messageData['message'] = $messageData['file_name']; // Utiliser le nom du fichier comme "message" par défaut
            }
        } elseif (! $messageText) {
            // Si ni message texte ni fichier ne sont fournis (la validation devrait l'attraper avec required_without)
            return response()->json(['message' => 'A message or an attachment is required.'], 422);
        }

        $message = $conversation->messages()->create($messageData);

        $recipient = null;
        foreach ($conversation->participants as $participant) {
            if ($participant->id != $sender->id) {
                $recipient = $participant;
                break;
            }
        }

        // 2. Si un destinataire est trouvé, lui envoyer la notification
        if ($recipient) {
            $senderName = $sender->first_name;

            // ceci permet de récupérer la langue préférée de l'utilisateur
            $this->switchlanguageInServer($recipient);

            // Préparer le contenu de la notification avec la langue préférée du destinataire
            $notificationMessageContent = __('message.show').' '.$senderName;
            $preview = $messageData['message_type'] === 'text' ? $messageData['message'] : $messageData['file_name'];
            if ($preview) {
                $notificationMessageContent .= ': "'.Str::limit($preview, 50).'"';
            }

            // Données à passer à la notification pour le contexte
            $notificationData = [
                'message' => $notificationMessageContent,
                'conversation_id' => $conversationId,
                'message_id' => $message->id,
                'notification_type' => 'chat',
                'message_type' => $messageData['message_type'],
                // Pour le frontend, il est utile de savoir qui est l'autre personne dans le chat (l'expéditeur de ce message)
                // afin de pouvoir afficher "Message de X" ou d'ouvrir le chat avec X.
                'chat_partner_id' => $sender->id,
                'chat_partner_name' => $senderName, // ou first_name

            ];
            $recipient->notify(new UserSpecificNotification(
                $recipient,
                $notificationData
            ));
            $unreadChatMessagesCount = $recipient->unreadChatMessagesCount();
            event(new UserNotificationCountUpdated($recipient, $unreadChatMessagesCount));
            $this->switchlanguageInServer($sender);
        } else {
            // Logguer une erreur si aucun destinataire n'est trouvé (ne devrait pas arriver dans un chat 1-1)
            Log::warning(" sendMessage: No recipient found for conversation ID {$conversation->id} to notify.");
        }

        // Charger les relations nécessaires pour la réponse et l'événement
        $message->load('sender:id,first_name,last_name,photo'); // Adaptez les champs User

        broadcast(new NewMessageSent($message->fresh()))->toOthers(); // fresh() pour avoir file_url

        $conversation->touch();

        // Renvoyer le message complet, y compris les infos du fichier et file_url
        // Le modèle Message inclut 'file_url' dans ses appends.
        return response()->json($message->fresh(), 201);
    }

    public function switchlanguageInServer($user): void
    {
        $userlanguage = new LanguageResource($user->languages()->first());
        app()->setLocale($userlanguage->code); // Mettre à jour la locale de l'application
    }

    /**
     * Marque les messages d'une conversation comme lus.
     * Le endpoint `getMessages` le fait déjà, mais cela pourrait être un endpoint séparé si nécessaire.
     */
    public function markAsRead(Request $request, $conversationId)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $conversationId)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->firstOrFail();

        $updatedCount = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // TODO: Diffuser un événement "messages-read" si l'autre utilisateur doit être notifié
        // (ex: pour afficher les doubles coches bleues)

        return response()->json(['message' => "$updatedCount messages marked as read."]);
    }

    /**
     * Trouve ou crée une conversation entre l'utilisateur authentifié et un autre utilisateur.
     * Utile pour le bouton "New Chat -> Add Contact".
     */
    public function findOrCreateConversation(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
        ]);

        $currentUser = Auth::user();
        $recipientId = $request->input('recipient_id');

        if ($currentUser->id == $recipientId) {
            return response()->json(['error' => 'Cannot create a chat with yourself.'], 400);
        }

        // Chercher une conversation existante 1-1 (directe) entre ces deux utilisateurs
        $conversation = Conversation::whereHas('participants', function ($query) use ($currentUser) {
            $query->where('users.id', $currentUser->id);
        })
            ->whereHas('participants', function ($query) use ($recipientId) {
                $query->where('users.id', $recipientId);
            })

            ->whereDoesntHave('participants', function ($query) use ($currentUser, $recipientId) {
                $query->whereNotIn('users.id', [$currentUser->id, $recipientId]);
            })
            ->first();

        if (! $conversation) {
            DB::beginTransaction();
            try {
                $conversation = Conversation::create(); // L'ID UUID sera auto-généré
                $conversation->participants()->attach([$currentUser->id, $recipientId]);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json(['error' => 'Could not create conversation.'], 500);
            }
        }

        // Mettre à jour updated_at pour faire remonter la conversation en haut de la liste
        $conversation->touch();

        // Renvoyer les informations de la conversation au format attendu par le frontend pour la navigation
        $otherParticipant = User::find($recipientId);

        return response()->json([
            'id' => $conversation->id,
            'imageUrl' => $otherParticipant->photo ?? asset('images/default_avatar.png'),
            'first_name' => $otherParticipant->first_name,
            'lastMessage' => 'Chat started.', // Ou null
            'unreadCount' => 0,
            'partnerId' => $otherParticipant->id, // AJOUTÉ : crucial pour WebSocket et statut en ligne
            'timestamp' => $conversation->created_at?->format(__('dateformatTime.datekey')),
            'is_blocked' => $otherParticipant->is_blocked,
        ], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    public function test()
    {
        $user = auth()->user(); // ou auth()->user() si c'est protégé par Sanctum

        return response()->json([
            'resultat' => $user->unreadChatMessagesCount(),
        ]);
    }
}
