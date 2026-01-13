<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Vider la table pour éviter les doublons
        DB::table('menus')->truncate();

        // Récupérer les IDs des catégories
        $dashboardCategory = Category::where('name', 'Dashboard')->first();
        $administrationCategory = Category::where('name', 'Administration')->first();
        $autorisationCategory = Category::where('name', 'Autorisation')->first();

        $menus = [

            // =========================
            // Dashboard
            // =========================
            [
                'name' => 'home',
                'description' => [
                    'fr' => 'Page d’accueil principale affichant une vue d’ensemble des informations clés.',
                    'en' => 'Main homepage displaying an overview of key information.',
                    'pt' => 'Página inicial principal com uma visão geral das informações principais.',
                ],
                'icon' => 'home',
                'color' => '#2563eb',
                'route' => '/index',
                'roles' => ['user', 'admin', 'manager'],
                'slug' => Str::slug('home'),
                'category_id' => $dashboardCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'analytics',
                'description' => [
                    'fr' => 'Tableau de bord analytique pour visualiser les statistiques et indicateurs.',
                    'en' => 'Analytics dashboard to visualize statistics and indicators.',
                    'pt' => 'Painel analítico para visualizar estatísticas e indicadores.',
                ],
                'icon' => 'dashboard',
                'color' => '#ea580c',
                'route' => '/index/dashboard',
                'roles' => ['admin'],
                'slug' => Str::slug('analytics'),
                'category_id' => $dashboardCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'chat',
                'description' => [
                    'fr' => 'Messagerie interne pour communiquer en temps réel.',
                    'en' => 'Internal messaging for real-time communication.',
                    'pt' => 'Mensagens internas para comunicação em tempo real.',
                ],
                'icon' => 'chat',
                'color' => '#16a34a',
                'route' => '/index/chat',
                'roles' => ['user', 'admin', 'manager'],
                'slug' => Str::slug('chat'),
                'category_id' => $dashboardCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'notifications',
                'description' => [
                    'fr' => 'Consulter l’historique des notifications reçues.',
                    'en' => 'View the history of received notifications.',
                    'pt' => 'Consultar o histórico de notificações recebidas.',
                ],
                'icon' => 'notifications',
                'color' => '#dc2626',
                'route' => '/index/notifications/all',
                'roles' => ['user', 'admin', 'manager'],
                'slug' => Str::slug('notifications'),
                'category_id' => $dashboardCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'documents',
                'description' => [
                    'fr' => 'Gestion et consultation des documents.',
                    'en' => 'Manage and consult documents.',
                    'pt' => 'Gerir e consultar documentos.',
                ],
                'icon' => 'description',
                'color' => '#4b5563',
                'route' => '/index/document',
                'roles' => ['user', 'admin', 'manager'],
                'slug' => Str::slug('documents'),
                'category_id' => $dashboardCategory->id,
                'disable' => true,
            ],

            // =========================
            // Administration
            // =========================
            [
                'name' => 'settings',
                'description' => [
                    'fr' => 'Paramètres du compte et préférences de l’application.',
                    'en' => 'Account settings and application preferences.',
                    'pt' => 'Definições da conta e preferências da aplicação.',
                ],
                'icon' => 'settings',
                'color' => '#4b5563',
                'route' => '/index/settings',
                'roles' => ['user', 'admin', 'manager'],
                'slug' => Str::slug('settings'),
                'category_id' => $dashboardCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'logs',
                'description' => [
                    'fr' => 'Historique des activités et actions du système.',
                    'en' => 'System activity and action logs.',
                    'pt' => 'Histórico de atividades e ações do sistema.',
                ],
                'icon' => 'bar_chart',
                'color' => '#9333ea',
                'route' => '/index/log',
                'roles' => ['admin'],
                'slug' => Str::slug('logs'),
                'category_id' => $administrationCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'users',
                'description' => [
                    'fr' => 'Gestion des utilisateurs de la plateforme.',
                    'en' => 'Platform user management.',
                    'pt' => 'Gestão de utilizadores da plataforma.',
                ],
                'icon' => 'person',
                'color' => '#0d9488',
                'route' => '/index/user',
                'roles' => ['admin'],
                'slug' => Str::slug('users'),
                'category_id' => $administrationCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'managemenu',
                'description' => [
                    'fr' => 'Organisation et gestion des menus de navigation.',
                    'en' => 'Navigation menu management.',
                    'pt' => 'Gestão dos menus de navegação.',
                ],
                'icon' => 'menu',
                'color' => '#0891b2',
                'route' => '/index/managemenu',
                'roles' => ['admin'],
                'slug' => Str::slug('managemenu'),
                'category_id' => $administrationCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'categories',
                'description' => [
                    'fr' => 'Gestion des catégories de contenu.',
                    'en' => 'Content category management.',
                    'pt' => 'Gestão de categorias de conteúdo.',
                ],
                'icon' => 'category',
                'color' => '#f59e0b',
                'route' => '/index/category',
                'roles' => ['admin'],
                'slug' => Str::slug('categories'),
                'category_id' => $administrationCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'module-managers',
                'description' => [
                    'fr' => 'Gestion et activation des modules.',
                    'en' => 'Module management and activation.',
                    'pt' => 'Gestão e ativação de módulos.',
                ],
                'icon' => 'extension',
                'color' => '#10b981',
                'route' => '/index/module-managers',
                'roles' => ['admin'],
                'slug' => Str::slug('module-managers'),
                'category_id' => $administrationCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'github',
                'description' => [
                    'fr' => 'Gestion des repositories GitHub et de leurs branches.',
                    'en' => 'GitHub repositories and branches management.',
                    'pt' => 'Gestão de repositórios GitHub e suas branches.',
                ],
                'icon' => 'code',
                'color' => '#6366f1',
                'route' => '/index/github',
                'roles' => ['admin', 'manager'],
                'slug' => Str::slug('github'),
                'category_id' => $administrationCategory->id,
                'disable' => true,
            ],

            // =========================
            // Autorisation
            // =========================
            [
                'name' => 'roles',
                'description' => [
                    'fr' => 'Gestion des rôles et des niveaux d’accès.',
                    'en' => 'Role and access level management.',
                    'pt' => 'Gestão de funções e níveis de acesso.',
                ],
                'icon' => 'lock',
                'color' => '#4f46e5',
                'route' => '/index/role',
                'roles' => ['admin'],
                'slug' => Str::slug('roles'),
                'category_id' => $autorisationCategory->id,
                'disable' => true,
            ],
            [
                'name' => 'permissions',
                'description' => [
                    'fr' => 'Gestion des permissions et droits d’accès.',
                    'en' => 'Permission and access rights management.',
                    'pt' => 'Gestão de permissões e direitos de acesso.',
                ],
                'icon' => 'security',
                'color' => '#db2777',
                'route' => '/index/permission',
                'roles' => ['admin'],
                'slug' => Str::slug('permissions'),
                'category_id' => $autorisationCategory->id,
                'disable' => true,
            ],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
