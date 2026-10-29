<?php

return [
    'admin' => ['permissions' => ['stock.*']],
    'gestionnaire' => ['permissions' => ['stock.documents.index', 'stock.entrees.saisir', 'stock.sorties.saisir']],
    'operateur' => ['permissions' => ['stock.documents.index', 'stock.entrees.saisir']],
];
