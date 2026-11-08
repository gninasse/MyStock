<?php

namespace Modules\Referentiel\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\User;

/**
 * En-tête officiel des documents PDF : hiérarchie administrative, nom de l'État et devise,
 * armoiries et logo, libellé centré du document, « Fait à … le … » et pied de page.
 * Les images sont stockées sur le disque local et intégrées en base64 : DomPDF ne fait
 * aucun appel réseau.
 */
class EnteteDocuments
{
    public const IMAGES = ['entete_armoiries', 'entete_logo'];

    protected const DOSSIER = 'entete';

    public function __construct(protected Parametres $parametres) {}

    /**
     * Données prêtes à afficher par le gabarit PDF.
     */
    public function donnees(): array
    {
        $p = fn (string $cle) => $this->parametres->valeur($cle);

        return [
            'etat' => $p('entete_etat'),
            'devise' => $p('entete_devise'),
            'hierarchie' => $p('entete_hierarchie'),
            'armoiries' => $this->imageEnBase64($p('entete_armoiries')),
            'armoiries_position' => $p('entete_armoiries_position'),
            'logo' => $this->imageEnBase64($p('entete_logo')),
            'logo_position' => $p('entete_logo_position'),
            'lieu' => $p('entete_lieu'),
            'pied' => $p('entete_pied'),
        ];
    }

    /**
     * Libellé d'un document : celui renommé par l'administrateur, sinon le libellé par défaut.
     */
    public function libelle(string $code, ?string $defaut = null): string
    {
        $renomme = $this->parametres->valeur('entete_libelles')[$code] ?? null;

        return $renomme ?: ($defaut ?? config("referentiel.documents.{$code}", $code));
    }

    /**
     * Numéro d'un bon selon le format paramétré (« N° {numero}/CHU-YO/DG » par exemple).
     */
    public function reference(?string $numero): string
    {
        if (! $numero) {
            return 'N° non attribué (brouillon)';
        }
        $format = $this->parametres->valeur('entete_reference') ?: 'N° {numero}';
        if (! str_contains($format, '{numero}')) {
            $format .= ' {numero}';
        }

        return strtr($format, ['{numero}' => $numero, '{annee}' => now()->format('Y')]);
    }

    /**
     * « Fait à Ouagadougou, le 8 novembre 2026 » ; date en pointillés quand elle est à remplir à la main.
     */
    public function faitA(?\DateTimeInterface $date = null, bool $aRemplir = false): string
    {
        $lieu = $this->parametres->valeur('entete_lieu');
        $jour = $aRemplir
            ? '........ / ........ / ............'
            : Carbon::instance($date ?? now())->locale('fr')->translatedFormat('j F Y');

        return ($lieu ? "Fait à {$lieu}, le " : 'Fait le ').$jour;
    }

    public function enregistrerImage(string $cle, UploadedFile $fichier, User $user): void
    {
        $ancien = $this->parametres->valeur($cle);
        $chemin = $fichier->storeAs(self::DOSSIER, $cle.'-'.now()->format('YmdHis').'.'.$fichier->extension(), 'local');

        $this->parametres->enregistrer([$cle => $chemin], $user, 'entete_logo_modifie');
        $this->supprimerFichier($ancien);
    }

    public function supprimerImage(string $cle, User $user): void
    {
        $ancien = $this->parametres->valeur($cle);
        $this->parametres->enregistrer([$cle => null], $user, 'entete_logo_supprime');
        $this->supprimerFichier($ancien);
    }

    /**
     * URI data: de l'image, ou null si elle n'est pas définie ou a disparu du disque.
     */
    public function imageEnBase64(?string $chemin): ?string
    {
        if (! $chemin || ! Storage::disk('local')->exists($chemin)) {
            return null;
        }
        $type = Storage::disk('local')->mimeType($chemin) ?: 'image/png';

        return 'data:'.$type.';base64,'.base64_encode(Storage::disk('local')->get($chemin));
    }

    protected function supprimerFichier(?string $chemin): void
    {
        if ($chemin && str_starts_with($chemin, self::DOSSIER.'/')) {
            Storage::disk('local')->delete($chemin);
        }
    }
}
