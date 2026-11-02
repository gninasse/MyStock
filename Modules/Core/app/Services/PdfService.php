<?php

namespace Modules\Core\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Rendu PDF commun aux imprimés et aux états : polices réduites aux caractères utilisés,
 * pagination « Page x / y », affichage en ligne (aperçu en modale) ou téléchargement
 * quand la requête porte ?telecharger=1.
 */
class PdfService
{
    public function rendre(string $vue, array $donnees, string $nomFichier, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($vue, $donnees)
            // N'embarquer que les caractères utilisés de la police : ~30 Ko au lieu de ~900 Ko
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4', $orientation);

        // Le total de pages n'est connu qu'après le rendu : numérotation posée sur le canevas
        $pdf->render();
        $canevas = $pdf->getDomPDF()->getCanvas();
        $canevas->page_text($canevas->get_width() - 78, $canevas->get_height() - 24, 'Page {PAGE_NUM} / {PAGE_COUNT}', null, 7, [0.45, 0.48, 0.52]);

        return request()->boolean('telecharger') ? $pdf->download($nomFichier) : $pdf->stream($nomFichier);
    }
}
