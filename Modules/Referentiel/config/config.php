<?php

return [
    'name' => 'Referentiel',

    // Unités de mesure proposées pour les articles (SFD 5.2.2)
    'unites_mesure' => [
        'PIECE' => 'Pièce',
        'KG' => 'Kg',
        'LITRE' => 'Litre',
        'METRE' => 'Mètre',
        'RAME' => 'Rame',
        'CARTON' => 'Carton',
        'BOITE' => 'Boîte',
        'FLACON' => 'Flacon',
        'ROULEAU' => 'Rouleau',
        'PAIRE' => 'Paire',
    ],

    // Préfixe des codes article générés automatiquement (SFD 5.2.3) : ART-001, ART-002…
    'article_code_prefixe' => 'ART-',

    // Nombre maximum d'articles fréquents par magasin (SFD 6.3)
    'articles_frequents_max' => 50,
];
