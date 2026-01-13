<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    private array $createdPairs = [];

    public function run()
    {
        $faker = FakerFactory::create();

        // Récupération des deux comptes clés
        $tekie = User::where('email', 'tekie@tekie.com')->first();
        $admin = User::where('email', 'admin@admin.com')->first();

        if (! $tekie || ! $admin) {
            $this->command->error("Users de base introuvables (tekie/admin). Lance d'abord UserSeeder.");

            return;
        }

        // 1) Une conversation entre Tekie et Admin (unique)
        $this->createUniqueConversationWithMessages($tekie, $admin, $faker);

        // 2) Conversations pour Tekie avec d'autres users aléatoires
        $othersForTekie = User::whereNotIn('id', [$tekie->id, $admin->id])
            ->inRandomOrder()
            ->limit(50)
            ->get();

        foreach ($othersForTekie as $u) {
            $this->createUniqueConversationWithMessages($tekie, $u, $faker);
        }

        // 3) Conversations pour Admin avec d'autres users aléatoires (éviter les doublons)
        $othersForAdmin = User::whereNotIn('id', [$tekie->id, $admin->id])
            ->inRandomOrder()
            ->limit(60)
            ->get();

        foreach ($othersForAdmin as $u) {
            $this->createUniqueConversationWithMessages($admin, $u, $faker);
        }

        $totalConversations = count($this->createdPairs);
        $this->command->info("Seed chats OK : {$totalConversations} conversations uniques créées.");
    }

    /**
     * Crée une conversation unique entre deux users avec des messages variés.
     */
    private function createUniqueConversationWithMessages(User $u1, User $u2, \Faker\Generator $faker): void
    {
        // Créer une clé unique pour cette paire d'utilisateurs
        $pairKey = $this->createPairKey($u1->id, $u2->id);

        // Vérifier si cette conversation existe déjà
        if (in_array($pairKey, $this->createdPairs)) {
            return; // Ignorer si déjà créée
        }

        // Vérifier en base de données également
        if ($this->conversationExists($u1->id, $u2->id)) {
            $this->createdPairs[] = $pairKey;

            return;
        }

        $this->createConversationWithMessages($u1, $u2, $faker);
        $this->createdPairs[] = $pairKey;
    }

    /**
     * Crée une conversation 1-1 entre deux users avec des messages variés.
     */
    private function createConversationWithMessages(User $u1, User $u2, \Faker\Generator $faker): void
    {
        // Crée la conversation et attache les participants
        $conversation = Conversation::create([
            'type' => 'direct',
        ]);
        $conversation->participants()->attach([$u1->id, $u2->id]);

        // Messages variés selon le type de conversation
        $messagesCount = $this->getMessageCountBasedOnUsers($u1, $u2);
        $messages = $this->generateMessagesBasedOnUsers($u1, $u2, $faker);

        // Temps de départ aléatoire dans les 30 derniers jours
        $time = Carbon::now()->subDays(random_int(1, 30))->subMinutes(random_int(0, 1440));

        for ($i = 0; $i < $messagesCount; $i++) {
            // Alterner les expéditeurs
            $sender = ($i % 2 === 0) ? $u1 : $u2;

            // Choisir un message approprié
            $message = $messages[array_rand($messages)];

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message' => $message,
                'created_at' => $time,
                'updated_at' => $time,
                'read_at' => ($i < $messagesCount - 2) ? $time->copy()->addMinutes(random_int(1, 10)) : null,
            ]);

            // Temps variable entre messages (1 min à 2 heures)
            $time->addMinutes(random_int(1, 120));
        }

        // Met à jour la date de la conversation (dernier message)
        $conversation->touch();
    }

    private function getMessageCountBasedOnUsers(User $u1, User $u2): int
    {
        // Plus de messages entre tekie et admin
        if ($this->isSpecialUserPair($u1, $u2)) {
            return random_int(8, 25);
        }

        return random_int(3, 12);
    }

    private function generateMessagesBasedOnUsers(User $u1, User $u2, \Faker\Generator $faker): array
    {
        if ($this->isSpecialUserPair($u1, $u2)) {
            return $this->getTekieAdminMessages($faker);
        }

        return $this->getGeneralMessages($faker);
    }

    private function isSpecialUserPair(User $u1, User $u2): bool
    {
        $emails = [$u1->email, $u2->email];

        return in_array('tekie@tekie.com', $emails) && in_array('admin@admin.com', $emails);
    }

    private function getTekieAdminMessages(\Faker\Generator $faker): array
    {
        return [
            "Salut ! Comment ça va aujourd'hui ?",
            'Peux-tu vérifier les logs du serveur ?',
            "Le déploiement s'est bien passé",
            'Il y a un bug sur la page de connexion',
            'Les utilisateurs remontent un problème de performance',
            "J'ai mis à jour la documentation",
            'Peux-tu valider cette nouvelle fonctionnalité ?',
            'Le backup automatique fonctionne parfaitement',
            "Il faut qu'on planifie la prochaine release",
            'Les tests passent tous en vert ✅',
            'Problème résolu, merci pour ton aide !',
            'Nouvelle demande de feature des clients',
            "J'ai optimisé les requêtes de la base de données",
            'Le monitoring montre tout au vert',
            'Peux-tu regarder cette pull request ?',
            "Réunion d'équipe prévue demain 14h",
            'Migration de données terminée avec succès',
            "Les métriques d'usage sont excellentes ce mois-ci",
            'Security update appliquée sur tous les serveurs',
            'Interface utilisateur mise à jour selon les retours',
            $faker->sentence(random_int(6, 15)),
            $faker->sentence(random_int(4, 10)),
            $faker->sentence(random_int(8, 20)),
        ];
    }

    private function getGeneralMessages(\Faker\Generator $faker): array
    {
        return [
            'Bonjour ! Comment allez-vous ?',
            'Merci pour votre aide',
            'Avez-vous des nouvelles ?',
            'Parfait, merci !',
            'Je vous remercie pour votre réponse rapide',
            "Pouvez-vous m'aider avec cela ?",
            "C'est exactement ce que je cherchais",
            'Excellente idée !',
            'Je vais regarder ça de plus près',
            'Rendez-vous demain alors',
            'Très bien, à bientôt',
            "D'accord, merci pour l'information",
            $faker->sentence(random_int(5, 12)),
            $faker->sentence(random_int(4, 8)),
            $faker->sentence(random_int(6, 14)),
            $faker->sentence(random_int(3, 10)),
        ];
    }

    /**
     * Crée une clé unique pour une paire d'utilisateurs
     */
    private function createPairKey(string $userId1, string $userId2): string
    {
        // Ordonner les IDs pour s'assurer que user1-user2 = user2-user1
        $ids = [$userId1, $userId2];
        sort($ids);

        return implode('-', $ids);
    }

    /**
     * Vérifie si une conversation existe déjà entre deux utilisateurs
     */
    private function conversationExists(string $userId1, string $userId2): bool
    {
        return Conversation::whereHas('participants', function ($query) use ($userId1) {
            $query->where('user_id', $userId1);
        })->whereHas('participants', function ($query) use ($userId2) {
            $query->where('user_id', $userId2);
        })->where(function ($query) {
            // S'assurer que c'est une conversation 1-1 (exactement 2 participants)
            $query->whereHas('participants', function ($q) {}, '=', 2);
        })->exists();
    }
}
