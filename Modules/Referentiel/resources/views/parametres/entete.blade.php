@extends('core::layouts.master')

@section('header', 'En-tête des documents')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('referentiel.parametres.index') }}">Paramètres</a></li>
    <li class="breadcrumb-item active" aria-current="page">En-tête des documents</li>
@endsection

@push('css')
<style>
    .entete-apercu { background: #fff; color: #000; border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: 18px 16px 14px; font-family: "DejaVu Sans", Arial, sans-serif; }
    .entete-apercu table { width: 100%; }
    .entete-apercu td { vertical-align: top; text-align: center; padding: 0 2px; }
    .entete-apercu .col-cote { width: 42%; }
    .entete-apercu .col-centre { width: 16%; vertical-align: middle; }
    .entete-apercu .niveau { font-size: .62rem; font-weight: 700; text-transform: uppercase; line-height: 1.3; }
    .entete-apercu .tiret { width: 28px; margin: 3px auto; border-top: 1px solid #000; }
    .entete-apercu .nom-etat { font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .entete-apercu .devise { font-size: .64rem; font-style: italic; }
    .entete-apercu img { max-height: 46px; margin: 0 2px 4px; }
    .entete-apercu .libelle { display: inline-block; margin-top: 14px; padding: 3px 16px; border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: 700; font-size: .85rem; letter-spacing: .06em; text-transform: uppercase; }
    .entete-apercu .reference { font-size: .72rem; font-weight: 700; margin-top: 4px; }
    .entete-apercu .fait-a { text-align: right; font-size: .68rem; margin-top: 12px; }
    .entete-apercu .pied { border-top: 1px solid #ccd; margin-top: 10px; padding-top: 4px; font-size: .58rem; color: #555; text-align: center; }
    .image-entete { height: 72px; border: 1px dashed var(--bs-border-color); border-radius: .5rem; display: flex; align-items: center; justify-content: center; background: var(--bs-tertiary-bg); }
    .image-entete img { max-height: 62px; max-width: 100%; }
    @media (min-width: 1200px) { .apercu-collant { position: sticky; top: 1rem; } }
</style>
@endpush

@section('content')
<div class="row g-3">
    <div class="col-xl-7">
        <form id="form-entete" data-url="{{ route('referentiel.parametres.entete.update') }}" novalidate>
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title fw-bold">État</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label" for="entete_etat">Nom de l'État</label>
                            <input type="text" class="form-control" id="entete_etat" name="entete_etat" value="{{ $valeurs['entete_etat'] }}" maxlength="80">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="entete_devise">Devise nationale</label>
                            <input type="text" class="form-control" id="entete_devise" name="entete_devise" value="{{ $valeurs['entete_devise'] }}" maxlength="120">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="form-text mt-2">Affichés à droite de l'en-tête, séparés par un trait.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title fw-bold mb-0">Hiérarchie administrative</h3>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="btn-ajouter-niveau"><i class="bi bi-plus-lg"></i> Ajouter un niveau</button>
                </div>
                <div class="card-body">
                    <p class="form-text mt-0">Du niveau le plus élevé au plus bas, comme sur le papier à en-tête officiel. Affichée à gauche, en majuscules.</p>
                    <div id="liste-niveaux">
                        @foreach ($valeurs['entete_hierarchie'] as $niveau)
                            @include('referentiel::parametres.partials.niveau', ['niveau' => $niveau])
                        @endforeach
                    </div>
                    <div class="invalid-feedback d-block" data-erreur="entete_hierarchie"></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title fw-bold">Armoiries et logo</h3></div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach (['entete_armoiries' => ['Armoiries ou emblème national', 'entete_armoiries_position'], 'entete_logo' => ['Logo de la structure', 'entete_logo_position']] as $cle => [$titre, $position])
                            <div class="col-md-6" data-image="{{ $cle }}"
                                 data-url="{{ route('referentiel.parametres.entete.image', $cle) }}"
                                 data-url-supprimer="{{ route('referentiel.parametres.entete.image.supprimer', $cle) }}">
                                <label class="form-label fw-semibold">{{ $titre }}</label>
                                <div class="image-entete mb-2">
                                    <img src="{{ $images[$cle] }}" alt="" class="{{ $images[$cle] ? '' : 'd-none' }}">
                                    <span class="text-body-secondary small {{ $images[$cle] ? 'd-none' : '' }}">Aucune image</span>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <label class="btn btn-sm btn-outline-primary mb-0">
                                        <i class="bi bi-upload"></i> Choisir…
                                        <input type="file" accept="image/png,image/jpeg" class="d-none">
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-danger retirer-image {{ $images[$cle] ? '' : 'd-none' }}"><i class="bi bi-trash"></i> Retirer</button>
                                </div>
                                <select class="form-select form-select-sm" name="{{ $position }}" aria-label="Position : {{ mb_strtolower($titre) }}">
                                    @foreach ($definitions[$position]['choix'] as $valeur => $libelle)
                                        <option value="{{ $valeur }}" @selected($valeurs[$position] === $valeur)>{{ $libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text mt-3">PNG ou JPEG, 1 Mo au plus, de préférence sur fond transparent ou blanc. L'image est enregistrée dès qu'elle est choisie.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title fw-bold">Numérotation, signature et pied de page</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="entete_reference">Format du numéro des bons</label>
                            <input type="text" class="form-control" id="entete_reference" name="entete_reference" value="{{ $valeurs['entete_reference'] }}" maxlength="80">
                            <div class="invalid-feedback"></div>
                            <div class="form-text"><code>{numero}</code> : numéro du bon · <code>{annee}</code> : année. Exemple : <code>N° {numero}/CHU-YO/DG</code></div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="entete_lieu">Lieu de signature</label>
                            <input type="text" class="form-control" id="entete_lieu" name="entete_lieu" value="{{ $valeurs['entete_lieu'] }}" maxlength="60">
                            <div class="invalid-feedback"></div>
                            <div class="form-text">« Fait à …, le … » au-dessus des signatures.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="entete_pied">Mentions du pied de page</label>
                            <input type="text" class="form-control" id="entete_pied" name="entete_pied" value="{{ $valeurs['entete_pied'] }}" maxlength="200">
                            <div class="invalid-feedback"></div>
                            <div class="form-text">Adresse, boîte postale, téléphone, e-mail…</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title fw-bold">Libellés des documents</h3></div>
                <div class="card-body">
                    <p class="form-text mt-0">Titre centré sous l'en-tête. Laissez vide pour garder le libellé par défaut.</p>
                    <div class="row g-2">
                        @foreach ($documents as $code => $defaut)
                            <div class="col-md-6">
                                <label class="form-label small text-body-secondary mb-1" for="libelle-{{ $code }}">{{ $defaut }}</label>
                                <input type="text" class="form-control form-control-sm libelle-document" id="libelle-{{ $code }}" data-code="{{ $code }}"
                                       value="{{ $valeurs['entete_libelles'][$code] ?? '' }}" placeholder="{{ $defaut }}" maxlength="80">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('referentiel.parametres.index') }}" class="btn btn-outline-secondary">Retour aux paramètres</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>

    <div class="col-xl-5">
        <div class="card apercu-collant">
            <div class="card-header d-flex align-items-center">
                <h3 class="card-title fw-bold mb-0">Aperçu</h3>
                <a href="{{ route('referentiel.parametres.entete.apercu') }}" class="btn btn-sm btn-outline-primary ms-auto"
                   data-apercu-pdf="{{ route('referentiel.parametres.entete.apercu') }}" data-titre="Aperçu de l'en-tête">
                    <i class="bi bi-file-earmark-pdf"></i> Aperçu PDF
                </a>
            </div>
            <div class="card-body">
                <div class="entete-apercu" id="entete-apercu" data-libelle-defaut="{{ $documents['TRANSFERT'] }}">
                    <table>
                        <tr>
                            <td class="col-cote"><div data-zone="logo-gauche"></div><div data-zone="hierarchie"></div></td>
                            <td class="col-centre" data-zone="centre"></td>
                            <td class="col-cote">
                                <div data-zone="armoiries-droite"></div>
                                <div class="nom-etat" data-zone="etat"></div>
                                <div data-zone="devise"></div>
                            </td>
                        </tr>
                    </table>
                    <div class="text-center">
                        <div class="libelle" data-zone="libelle"></div>
                        <div class="reference" data-zone="reference"></div>
                    </div>
                    <div class="fait-a" data-zone="lieu"></div>
                    <div class="pied" data-zone="pied"></div>
                </div>
                <p class="form-text mb-0 mt-2">L'aperçu PDF montre l'en-tête <b>enregistré</b>.</p>
            </div>
        </div>
    </div>
</div>

<template id="modele-niveau">
    @include('referentiel::parametres.partials.niveau', ['niveau' => ''])
</template>
@stop

@push('js')
<script type="module" src="{{ asset('js/modules/referentiel/parametres/entete.js') }}"></script>
@endpush
