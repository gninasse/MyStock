<?php

namespace Modules\Etats\Support;

/**
 * Résultat d'un état, indépendant du rendu : le même objet sert à l'écran,
 * à l'impression, au PDF et à l'Excel (SFD 9.5).
 *
 * $colonnes : [clé => libellé] ; $numeriques : clés alignées à droite et totalisables.
 * $groupes  : [['titre' => ?string, 'lignes' => [[clé => valeur, '_classe' => ?string]], 'total' => ?array]]
 * $pied     : [libellé => valeur] affiché sous le tableau (ex. « Stock fin »).
 */
class Etat
{
    public function __construct(
        public string $titre,
        public array $filtres,
        public array $colonnes,
        public array $numeriques = [],
        public array $groupes = [],
        public array $entete = [],
        public array $pied = [],
    ) {}

    public function nombreLignes(): int
    {
        return array_sum(array_map(fn ($g) => count($g['lignes']), $this->groupes));
    }

    public function estVide(): bool
    {
        return $this->nombreLignes() === 0;
    }

    public function nomFichier(string $extension): string
    {
        return str($this->titre)->slug().'-'.now()->format('Ymd-His').'.'.$extension;
    }
}
