@extends('core::pdf.layout')

@use('Modules\Stock\Models\Document')
@php
    $type = $document->type;
    $titres = [
        Document::ENTREE => "Bon d'entrée", Document::SORTIE => 'Bon de sortie', Document::TRANSFERT => 'Bon de transfert',
        Document::RECEPTION => 'Bon de réception', Document::REINTEGRATION => 'Bon de réintégration', Document::AJUSTEMENT => "Bon d'ajustement d'inventaire",
    ];
    $statuts = Document::STATUTS;
    if ($type === Document::TRANSFERT && $document->statut === Document::VALIDE) {
        $statuts[Document::VALIDE] = ['Reçu'];
    }
    $estTransfert = $type === Document::TRANSFERT;
    $avecReception = $estTransfert && $document->lignes->whereNotNull('quantite_recue')->isNotEmpty();
    $avecLots = $document->lignes->contains(fn ($l) => $l->lots->isNotEmpty());
    $signe = $type === Document::AJUSTEMENT;
    $filigrane = match ($document->statut) { Document::BROUILLON => 'BROUILLON', Document::ANNULE => 'ANNULÉ', default => null };
    $signatures = match ($type) {
        Document::ENTREE => [['Le livreur', 'Nom, date et signature'], ['Le magasinier', 'Réception conforme — date et signature']],
        Document::SORTIE => [['Le magasinier', 'Date et signature'], ['Le bénéficiaire', 'Reçu le — nom et signature']],
        Document::TRANSFERT => [['Magasin expéditeur', 'Remis le — nom et signature'], ['Transporteur', 'Nom et signature'], ['Magasin destinataire', 'Reçu le — nom et signature']],
        Document::RECEPTION => [['Le réceptionnaire', 'Date et signature'], ['Responsable du magasin', 'Visa']],
        default => [['Établi par', 'Date et signature'], ['Responsable du magasin', 'Visa']],
    };
    $magasinBloc = fn ($m) => $m ? $m->libelle : '—';
@endphp

@section('titre', $titres[$type] ?? $document->libelle_type)
@section('sous-titre')
    N° {{ $document->reference ?? 'non attribué' }} &nbsp;<span class="pastille {{ $document->statut }}">{{ $statuts[$document->statut][0] ?? $document->statut }}</span>
@endsection
@section('pied', ($titres[$type] ?? '').' '.($document->reference ?? ''))

@section('contenu')
    <table class="infos">
        <tr>
            <td class="bloc" style="width: 34%">
                <div class="etiquette">{{ $estTransfert ? 'Magasin expéditeur' : 'Magasin' }}</div>
                <div class="valeur">{{ $magasinBloc($document->magasin) }}</div>
                <div class="detail">{{ $document->magasin?->code }}@if ($document->magasin?->localisation) · {{ $document->magasin->localisation }}@endif</div>
            </td>
            <td class="espace"></td>
            <td class="bloc" style="width: 38%">
                @switch($type)
                    @case(Document::TRANSFERT)
                        <div class="etiquette">Magasin destinataire</div>
                        <div class="valeur">{{ $magasinBloc($document->magasinDestination) }}</div>
                        <div class="detail">{{ $document->magasinDestination?->code }}@if ($document->magasinDestination?->localisation) · {{ $document->magasinDestination->localisation }}@endif</div>
                        @break
                    @case(Document::SORTIE)
                        <div class="etiquette">Bénéficiaire</div>
                        <div class="valeur">{{ $document->beneficiaire ?? '—' }}</div>
                        <div class="detail">{{ collect([$document->direction?->libelle, $document->service?->libelle])->filter()->implode(' / ') }}</div>
                        @break
                    @case(Document::RECEPTION)
                        <div class="etiquette">Provenance</div>
                        <div class="valeur">{{ $magasinBloc($document->documentOrigine?->magasin) }}</div>
                        <div class="detail">Transfert {{ $document->documentOrigine?->reference }}</div>
                        @break
                    @case(Document::REINTEGRATION)
                        <div class="etiquette">Transfert d'origine</div>
                        <div class="valeur">{{ $document->documentOrigine?->reference ?? '—' }}</div>
                        <div class="detail">Quantités non reçues ou transfert annulé</div>
                        @break
                    @case(Document::AJUSTEMENT)
                        <div class="etiquette">Inventaire</div>
                        <div class="valeur">{{ $document->inventaire ? 'Inventaire du '.$document->inventaire->date_inventaire->format('d/m/Y') : '—' }}</div>
                        <div class="detail">Régularisation des écarts constatés</div>
                        @break
                    @default
                        <div class="etiquette">Objet</div>
                        <div class="valeur">{{ $document->commentaire ?: 'Approvisionnement' }}</div>
                @endswitch
            </td>
            <td class="espace"></td>
            <td class="bloc" style="width: 28%">
                <div class="etiquette">Date du mouvement</div>
                <div class="valeur">{{ $document->date_mouvement->format('d/m/Y') }}</div>
                <div class="detail">
                    {{ $estTransfert ? 'Expédié' : 'Validé' }} le {{ $document->validated_at?->format('d/m/Y H:i') ?? '—' }}
                    @if ($document->recu_at)<br>Reçu le {{ $document->recu_at->format('d/m/Y H:i') }}@endif
                </div>
            </td>
        </tr>
    </table>

    <h2 class="section">Articles</h2>
    <table class="lignes">
        <thead>
            <tr>
                <th class="centre" style="width: 4%">N°</th>
                <th style="width: 11%">Code</th>
                <th>Désignation</th>
                <th style="width: 9%">Unité</th>
                @if ($avecLots)<th style="width: 24%">Lots</th>@endif
                <th class="num" style="width: 9%">{{ $estTransfert ? 'Expédié' : 'Quantité' }}</th>
                @if ($avecReception)
                    <th class="num" style="width: 7%">Reçu</th>
                    <th class="num" style="width: 7%">Écart</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($document->lignes as $ligne)
                <tr>
                    <td class="centre muet">{{ $loop->iteration }}</td>
                    <td>{{ $ligne->article->code }}</td>
                    <td>{{ $ligne->article->designation }}</td>
                    <td>{{ $unitesMesure[$ligne->article->unite_mesure] ?? $ligne->article->unite_mesure }}</td>
                    @if ($avecLots)
                        <td class="muet">
                            @foreach ($ligne->lots as $ventilation)
                                {{ $ventilation->lot->numero }}@if ($ventilation->lot->date_peremption) (exp. {{ $ventilation->lot->date_peremption->format('d/m/Y') }})@endif : {{ $ventilation->quantite }}<br>
                            @endforeach
                            @if ($ligne->lots->sum('quantite') < $ligne->quantite)
                                Sans lot : {{ $ligne->quantite - $ligne->lots->sum('quantite') }}
                            @endif
                        </td>
                    @endif
                    <td class="num">{{ $signe ? ($ligne->sens > 0 ? '+' : '−') : '' }}{{ number_format($ligne->quantite, 0, ',', ' ') }}</td>
                    @if ($avecReception)
                        <td class="num">{{ $ligne->quantite_recue }}</td>
                        @php $ecart = $ligne->quantite_recue === null ? null : $ligne->quantite_recue - $ligne->quantite; @endphp
                        <td class="num {{ $ecart < 0 ? 'negatif' : '' }}">{{ $ecart ?: '' }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        @unless ($signe)
            <tfoot>
                <tr>
                    <td colspan="{{ 4 + ($avecLots ? 1 : 0) }}">{{ $document->lignes->count() }} article(s)</td>
                    <td class="num">{{ number_format($document->lignes->sum('quantite'), 0, ',', ' ') }}</td>
                    @if ($avecReception)
                        <td class="num">{{ $document->lignes->sum('quantite_recue') }}</td>
                        @php $ecartTotal = $document->lignes->sum('quantite_recue') - $document->lignes->sum('quantite'); @endphp
                        <td class="num {{ $ecartTotal < 0 ? 'negatif' : '' }}">{{ $ecartTotal ?: '' }}</td>
                    @endif
                </tr>
            </tfoot>
        @endunless
    </table>

    <h2 class="section">Traçabilité</h2>
    <dl class="meta">
        <dt>Saisi par</dt><dd>{{ $document->utilisateur?->full_name }} le {{ $document->created_at->format('d/m/Y H:i') }}</dd>
        @if ($document->validated_at)
            <dt>{{ $estTransfert ? 'Expédié par' : 'Validé par' }}</dt><dd>{{ $document->validateur?->full_name }} le {{ $document->validated_at->format('d/m/Y H:i') }}</dd>
        @endif
        @if ($document->recu_at)
            <dt>Réceptionné par</dt><dd>{{ $document->recepteur?->full_name }} le {{ $document->recu_at->format('d/m/Y H:i') }}</dd>
        @endif
        @if ($document->commentaire && $type !== Document::ENTREE)
            <dt>Commentaire</dt><dd>{{ $document->commentaire }}</dd>
        @endif
        @if ($document->documentAnnule)
            <dt>Annule le document</dt><dd>{{ $document->documentAnnule->reference }}</dd>
        @endif
        @if ($document->annulation)
            <dt>Annulé par</dt><dd>{{ $document->annulation->reference }}</dd>
        @endif
    </dl>

    @if ($document->motif_ecart)
        <div class="note"><b>Motif de l'écart :</b> {{ $document->motif_ecart }}</div>
    @endif

    <table class="signatures">
        <tr>
            @foreach ($signatures as [$role, $ligne])
                @unless ($loop->first)<td class="espace"></td>@endunless
                <td class="case" style="width: {{ floor(100 / count($signatures)) - 1 }}%"><div class="role">{{ $role }}</div><div class="ligne">{{ $ligne }}</div></td>
            @endforeach
        </tr>
    </table>
@endsection
