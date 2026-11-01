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
    'transfert_mode' => [
        'groupe' => 'Transferts entre magasins',
        'libelle' => 'Déroulement d\'un transfert',
        'aide' => 'En deux étapes, la marchandise reste « en transit » jusqu\'à sa réception par le magasin destinataire.',
        'type' => 'choix',
        'choix' => [
            'DEUX_ETAPES' => 'Deux étapes : expédition, puis réception par le magasin destinataire',
            'UNE_ETAPE_SI_ACCES' => 'Une étape si l\'expéditeur a aussi accès au magasin destinataire, sinon deux',
            'UNE_ETAPE' => 'Une étape : le magasin destinataire est crédité dès l\'expédition',
        ],
        'defaut' => 'DEUX_ETAPES',
    ],
    'transfert_ecart' => [
        'groupe' => 'Transferts entre magasins',
        'libelle' => 'Quantité expédiée mais non reçue',
        'aide' => 'Traitement de l\'écart quand le destinataire reçoit moins que la quantité expédiée (ou refuse le transfert).',
        'type' => 'choix',
        'choix' => [
            'RETOUR_SOURCE' => 'Réintégrée dans le stock du magasin source',
            'PERTE' => 'Comptée comme perte en transit (le stock source n\'est pas recrédité)',
        ],
        'defaut' => 'RETOUR_SOURCE',
    ],
    'lots_actifs' => [
        'groupe' => 'Lots et péremption',
        'libelle' => 'Suivre les lots et dates de péremption',
        'aide' => 'Permet de saisir le numéro de lot et la date de péremption à l\'entrée. Les sorties et transferts consomment alors les lots qui périment en premier (FEFO).',
        'type' => 'booleen',
        'defaut' => false,
    ],
    'peremption_alerte_jours' => [
        'groupe' => 'Lots et péremption',
        'libelle' => 'Alerte de péremption (jours avant la date)',
        'aide' => 'Un lot en stock qui périme dans ce délai apparaît dans les alertes.',
        'type' => 'entier',
        'min' => 1,
        'max' => 730,
        'defaut' => 90,
    ],
];
