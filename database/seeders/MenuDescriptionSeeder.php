<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuDescriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Updates existing menus with multilingual descriptions (JSON)
     */
    public function run(): void
    {
        $descriptions = [

            // Dashboard
            'home' => [
                'fr' => 'Page d’accueil principale affichant une vue d’ensemble des informations et statistiques clés.',
                'en' => 'Main homepage displaying an overview of key information and statistics.',
                'pt' => 'Página inicial principal que apresenta uma visão geral das principais informações e estatísticas.',
            ],

            'analytics' => [
                'fr' => 'Tableau de bord analytique permettant de visualiser les données et indicateurs clés.',
                'en' => 'Analytics dashboard to visualize key data and performance indicators.',
                'pt' => 'Painel analítico para visualizar dados e indicadores-chave.',
            ],

            'chat' => [
                'fr' => 'Messagerie interne permettant de communiquer en temps réel avec les autres utilisateurs.',
                'en' => 'Internal chat allowing real-time communication with other users.',
                'pt' => 'Chat interno que permite comunicação em tempo real com outros utilizadores.',
            ],

            'notifications' => [
                'fr' => 'Historique et gestion des notifications reçues sur la plateforme.',
                'en' => 'History and management of notifications received on the platform.',
                'pt' => 'Histórico e gestão das notificações recebidas na plataforma.',
            ],

            'documents' => [
                'fr' => 'Accès et gestion des documents liés à vos dossiers et activités.',
                'en' => 'Access and management of documents related to your cases and activities.',
                'pt' => 'Acesso e gestão de documentos relacionados aos seus processos e atividades.',
            ],

            // Administration
            'settings' => [
                'fr' => 'Configuration des paramètres du compte et des préférences de l’application.',
                'en' => 'Configuration of account settings and application preferences.',
                'pt' => 'Configuração das definições da conta e preferências da aplicação.',
            ],

            'logs' => [
                'fr' => 'Consultation des journaux d’activités et des actions effectuées sur la plateforme.',
                'en' => 'View activity logs and actions performed on the platform.',
                'pt' => 'Consulta dos registos de atividades e ações realizadas na plataforma.',
            ],

            'users' => [
                'fr' => 'Gestion des utilisateurs : création, modification et suppression des comptes.',
                'en' => 'User management: create, update, and delete user accounts.',
                'pt' => 'Gestão de utilizadores: criação, edição e eliminação de contas.',
            ],

            'managemenu' => [
                'fr' => 'Gestion et organisation des menus de navigation de l’application.',
                'en' => "Manage and organize the application's navigation menus.",
                'pt' => 'Gerir e organizar os menus de navegação da aplicação.',
            ],

            'categories' => [
                'fr' => 'Création et gestion des catégories utilisées dans l’application.',
                'en' => 'Create and manage categories used in the application.',
                'pt' => 'Criar e gerir categorias utilizadas na aplicação.',
            ],

            'module-managers' => [
                'fr' => 'Activation et gestion des modules et fonctionnalités avancées.',
                'en' => 'Enable and manage modules and advanced features.',
                'pt' => 'Ativar e gerir módulos e funcionalidades avançadas.',
            ],

            // Autorisation
            'roles' => [
                'fr' => 'Définition des rôles utilisateurs et attribution des permissions associées.',
                'en' => 'Define user roles and assign related permissions.',
                'pt' => 'Definir funções de utilizadores e atribuir permissões associadas.',
            ],

            'permissions' => [
                'fr' => 'Gestion fine des droits d’accès aux fonctionnalités de l’application.',
                'en' => 'Fine-grained management of access rights to application features.',
                'pt' => 'Gestão detalhada dos direitos de acesso às funcionalidades da aplicação.',
            ],
        ];

        foreach ($descriptions as $menuName => $description) {
            Menu::where('name', $menuName)->update([
                'description' => $description, // JSON auto si colonne json
            ]);
        }

        $this->command->info(
            'Menu descriptions updated successfully for '.count($descriptions).' menus.'
        );
    }
}
