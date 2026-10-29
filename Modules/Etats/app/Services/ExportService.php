<?php

namespace Modules\Etats\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Etats\Support\Etat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports Excel et PDF d'un état, journalisés (entité EXPORT, SFD 9.5).
 */
class ExportService
{
    protected const COULEURS = ['alerte-egal' => 'FFE5B4', 'alerte-sous' => 'F8D7DA'];

    public function excel(Etat $etat): StreamedResponse
    {
        $classeur = new Spreadsheet;
        $feuille = $classeur->getActiveSheet();
        $feuille->setTitle(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $etat->titre), 0, 31));
        $cles = array_keys($etat->colonnes);
        $derniere = Coordinate::stringFromColumnIndex(count($cles));

        $feuille->setCellValue('A1', $etat->titre)->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $feuille->setCellValue('A2', 'Édité le '.now()->format('d/m/Y à H:i').' — '.$this->filtresTexte($etat));
        $ligne = 4;

        foreach ($etat->entete as $libelle => $valeur) {
            $feuille->setCellValue("A{$ligne}", $libelle)->setCellValue("B{$ligne}", $valeur);
            $feuille->getStyle("A{$ligne}")->getFont()->setBold(true);
            $ligne++;
        }
        if ($etat->entete) {
            $ligne++;
        }

        $debutTableau = $ligne;
        $this->ecrireLigne($feuille, $ligne, array_values($etat->colonnes));
        $feuille->getStyle("A{$ligne}:{$derniere}{$ligne}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDE3EA']],
        ]);
        $ligne++;

        foreach ($etat->groupes as $groupe) {
            if ($groupe['titre'] ?? null) {
                $feuille->setCellValue("A{$ligne}", $groupe['titre']);
                $feuille->getStyle("A{$ligne}")->getFont()->setBold(true);
                $ligne++;
            }
            foreach ($groupe['lignes'] as $donnees) {
                $this->ecrireLigne($feuille, $ligne, array_map(fn ($c) => $donnees[$c] ?? null, $cles));
                if ($couleur = self::COULEURS[$donnees['_classe'] ?? ''] ?? null) {
                    $feuille->getStyle("A{$ligne}:{$derniere}{$ligne}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($couleur);
                }
                $ligne++;
            }
            if ($groupe['total'] ?? null) {
                $this->ecrireLigne($feuille, $ligne, array_map(fn ($c) => $groupe['total'][$c] ?? null, $cles));
                $feuille->getStyle("A{$ligne}:{$derniere}{$ligne}")->getFont()->setBold(true);
                $ligne++;
            }
        }

        // Filtres automatiques sur le tableau (SFD 9.5)
        $feuille->setAutoFilter("A{$debutTableau}:{$derniere}".max($debutTableau, $ligne - 1));

        foreach ($etat->pied as $libelle => $valeur) {
            $ligne++;
            $feuille->setCellValue("A{$ligne}", $libelle)->setCellValue("B{$ligne}", $valeur);
            $feuille->getStyle("A{$ligne}:B{$ligne}")->getFont()->setBold(true);
        }

        foreach (range(1, count($cles)) as $index) {
            $feuille->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }

        $this->journaliser($etat, 'Excel');

        return response()->streamDownload(function () use ($classeur) {
            (new Xlsx($classeur))->save('php://output');
        }, $etat->nomFichier('xlsx'), ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function pdf(Etat $etat)
    {
        $this->journaliser($etat, 'PDF');

        $pdf = Pdf::loadView('etats::pdf', ['etat' => $etat])
            // N'embarquer que les caractères utilisés de la police : ~30 Ko au lieu de ~900 Ko
            ->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4', count($etat->colonnes) > 6 ? 'landscape' : 'portrait');

        // Le total de pages n'est connu qu'après le rendu : numérotation posée sur le canevas
        $pdf->render();
        $canevas = $pdf->getDomPDF()->getCanvas();
        $canevas->page_text($canevas->get_width() - 90, $canevas->get_height() - 25, 'Page {PAGE_NUM} / {PAGE_COUNT}', null, 8, [0.33, 0.33, 0.33]);

        return $pdf->download($etat->nomFichier('pdf'));
    }

    public function journaliser(Etat $etat, string $format): void
    {
        activity('etats')
            ->causedBy(auth()->user())
            ->withProperties(['etat' => $etat->titre, 'format' => $format, 'filtres' => $etat->filtres])
            ->tap(function ($activity) {
                $activity->module = 'etats';
                $activity->ip_address = request()->ip();
                $activity->user_agent = request()->userAgent();
            })
            ->log('export');
    }

    public function filtresTexte(Etat $etat): string
    {
        return collect($etat->filtres)->map(fn ($v, $k) => "{$k} : {$v}")->implode(' | ');
    }

    protected function ecrireLigne($feuille, int $ligne, array $valeurs): void
    {
        foreach (array_values($valeurs) as $index => $valeur) {
            $feuille->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$ligne, $valeur);
        }
    }
}
