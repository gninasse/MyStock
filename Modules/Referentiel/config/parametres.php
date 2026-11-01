<?php

/*
 * Paramètres de l'application modifiables par l'administrateur (écran Administration > Paramètres).
 * type : texte | entier | booleen | choix. La valeur par défaut s'applique tant que rien n'est enregistré.
 */
return [
    'organisation_nom' => [
        'groupe' => 'Général',
        'libelle' => 'Nom de l\'organisation',
        'aide' => 'Affiché dans l\'en-tête des états PDF.',
        'type' => 'texte',
        'max' => 100,
        'defaut' => 'CHU-YO',
    ],
    'mouvement_jours_retroactifs' => [
        'groupe' => 'Mouvements',
        'libelle' => 'Ancienneté maximale de la date d\'un mouvement (jours)',
        'aide' => 'Une entrée, une sortie ou un inventaire ne peut pas être daté de plus de N jours avant aujourd\'hui.',
        'type' => 'entier',
        'min' => 0,
        'max' => 365,
        'defaut' => 7,
    ],
];
