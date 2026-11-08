<?php

namespace Modules\Referentiel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Core\Services\PdfService;
use Modules\Referentiel\Services\EnteteDocuments;
use Modules\Referentiel\Services\Parametres;

/**
 * Administration > En-tête des documents : en-tête officiel commun à tous les PDF.
 */
class EnteteController extends Controller
{
    public function __construct(protected Parametres $parametres, protected EnteteDocuments $entete) {}

    public function edit()
    {
        $definitions = $this->definitions();

        return view('referentiel::parametres.entete', [
            'definitions' => $definitions,
            'valeurs' => collect($definitions)->mapWithKeys(fn ($d, $cle) => [$cle => $this->parametres->valeur($cle)])->all(),
            'images' => collect(EnteteDocuments::IMAGES)->mapWithKeys(fn ($cle) => [$cle => $this->entete->imageEnBase64($this->parametres->valeur($cle))])->all(),
            'documents' => config('referentiel.documents', []),
        ]);
    }

    public function update(Request $request)
    {
        $definitions = $this->definitions();
        // Règles de cet écran, y compris celles des éléments de liste (« entete_hierarchie.* »)
        $regles = collect($this->parametres->regles())
            ->filter(fn ($r, $cle) => isset($definitions[Str::before($cle, '.')]))->all();
        $regles['entete_libelles'][] = function ($attribut, $valeur, $echec) {
            if (array_diff_key((array) $valeur, config('referentiel.documents', []))) {
                $echec('Document inconnu.');
            }
        };

        $donnees = $request->validate($regles, [], collect($definitions)->map(fn ($d) => mb_strtolower($d['libelle']))->all());
        // L'en-tête officiel commence toujours par au moins un niveau administratif
        if (array_key_exists('entete_hierarchie', $donnees) && ! array_filter((array) $donnees['entete_hierarchie'])) {
            return response()->json(['message' => 'Indiquez au moins un niveau de hiérarchie.', 'errors' => ['entete_hierarchie' => ['Indiquez au moins un niveau de hiérarchie.']]], 422);
        }

        $modifies = $this->parametres->enregistrer($donnees, $request->user(), 'entete_documents_modifie');

        return response()->json([
            'success' => true,
            'message' => $modifies ? 'En-tête des documents enregistré.' : 'Aucune modification.',
        ]);
    }

    public function image(Request $request, string $cle)
    {
        abort_unless(in_array($cle, EnteteDocuments::IMAGES, true), 404);
        // PNG ou JPEG uniquement : DomPDF rend mal le SVG ; 1 Mo suffit largement pour un en-tête
        $request->validate(['fichier' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:1024', 'dimensions:min_width=40,min_height=40']], [], ['fichier' => 'image']);

        $this->entete->enregistrerImage($cle, $request->file('fichier'), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Image enregistrée.',
            'image' => $this->entete->imageEnBase64($this->parametres->valeur($cle)),
        ]);
    }

    public function supprimerImage(Request $request, string $cle)
    {
        abort_unless(in_array($cle, EnteteDocuments::IMAGES, true), 404);
        $this->entete->supprimerImage($cle, $request->user());

        return response()->json(['success' => true, 'message' => 'Image retirée.']);
    }

    /**
     * Document d'exemple pour juger de l'en-tête enregistré.
     */
    public function apercu()
    {
        return app(PdfService::class)->rendre('referentiel::pdf.apercu-entete', [], 'apercu-en-tete.pdf');
    }

    protected function definitions(): array
    {
        return collect($this->parametres->definitions())->where('ecran', 'entete')->all();
    }
}
