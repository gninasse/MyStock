<?php

/*
 * Rôles métier de MyStock (SFD 2.1 et 2.2). Chaque module peut ajouter
 * ses propres permissions aux rôles dans son fichier config/roles.php.
 */
return [
    // Rôle technique : tous les droits (le Gate::before du Core le laisse aussi passer partout).
    // Seul rôle qui administre les rôles, la matrice des permissions et les modules.
    'super-admin' => [
        'description' => 'Super-administrateur technique : tous les droits, dont les rôles, les permissions et les modules',
        'permissions' => ['cores.*', 'referentiel.*'],
    ],
    'admin' => [
        'description' => 'Administrateur : tous les droits métier, utilisateurs et journal d\'audit',
        'permissions' => [
            'referentiel.*',
            'cores.dashboard.view',
            'cores.users.index',
            'cores.users.store',
            'cores.users.update',
            'cores.users.reset-password',
            'cores.users.toggle-status',
            'cores.users.roles',
            'cores.activities.index',
            'cores.activities.data',
            'cores.activities.show',
            'cores.activities.export',
        ],
    ],
    'gestionnaire' => [
        'description' => 'Gestionnaire : entrées, sorties, inventaires et articles fréquents de ses magasins',
        'permissions' => [
            'cores.dashboard.view',
            'referentiel.articles.index',
            'referentiel.articles.store',
            'referentiel.articles-frequents.manage',
        ],
    ],
    'operateur' => [
        'description' => 'Opérateur : réception (entrées) et articles fréquents de ses magasins',
        'permissions' => [
            'cores.dashboard.view',
            'referentiel.articles.index',
            'referentiel.articles.store',
            'referentiel.articles-frequents.manage',
        ],
    ],
    'consultant' => [
        'description' => 'Consultant : consultation des états de tous les magasins',
        'permissions' => [
            'cores.dashboard.view',
            'referentiel.magasins.tous',
        ],
    ],
];
