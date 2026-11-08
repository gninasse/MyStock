<?php

return [
    'super-admin' => ['permissions' => ['stock.*']],
    'admin' => ['permissions' => ['stock.*']],
    'gestionnaire' => ['permissions' => ['stock.documents.index', 'stock.entrees.saisir', 'stock.sorties.saisir', 'stock.inventaires.saisir', 'stock.transferts.saisir', 'stock.transferts.receptionner']],
    'operateur' => ['permissions' => ['stock.documents.index', 'stock.entrees.saisir', 'stock.transferts.receptionner']],
];
