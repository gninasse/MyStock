<?php

// Matrice SFD 2.2 et 13.3 : l'Opérateur n'a ni le journal ni la fiche de stock,
// le Consultant n'a pas le journal ni les alertes.
return [
    'admin' => ['permissions' => ['etats.*']],
    'gestionnaire' => ['permissions' => ['etats.*']],
    'operateur' => ['permissions' => ['etats.stock-magasin', 'etats.stock-categorie', 'etats.export', 'etats.alertes']],
    'consultant' => ['permissions' => ['etats.stock-magasin', 'etats.stock-categorie', 'etats.fiche-stock', 'etats.export']],
];
