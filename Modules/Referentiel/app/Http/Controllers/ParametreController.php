<?php

namespace Modules\Referentiel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Referentiel\Services\Parametres;

class ParametreController extends Controller
{
    public function __construct(protected Parametres $parametres) {}

    public function index()
    {
        return view('referentiel::parametres.index', [
            // Les paramètres d'un écran dédié (en-tête des documents) n'apparaissent pas ici
            'groupes' => collect($this->parametres->definitions())->whereNull('ecran')->groupBy('groupe', true),
            'valeurs' => $this->parametres->toutes(),
        ]);
    }

    public function update(Request $request)
    {
        $definitions = $this->parametres->definitions();
        $generales = collect($definitions)->whereNull('ecran');
        $donnees = $request->validate(
            array_intersect_key($this->parametres->regles(), $generales->all()),
            [],
            collect($definitions)->map(fn ($d) => mb_strtolower($d['libelle']))->all()
        );

        $modifies = $this->parametres->enregistrer($donnees, $request->user());

        return response()->json([
            'success' => true,
            'message' => $modifies ? 'Paramètres enregistrés.' : 'Aucune modification.',
        ]);
    }
}
