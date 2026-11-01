@extends('core::layouts.master')

@section('header', 'Transferts à réceptionner')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Mouvements</a></li>
    <li class="breadcrumb-item active" aria-current="page">À réceptionner</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Marchandise en transit vers vos magasins ({{ $transferts->count() }})</h3></div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>N° transfert</th>
                    <th>Expédié le</th>
                    <th>Depuis</th>
                    <th>Vers</th>
                    <th>Lignes</th>
                    <th>Expédié par</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transferts as $transfert)
                    <tr>
                        <td><a href="{{ route('stock.documents.show', $transfert->id) }}">{{ $transfert->reference }}</a></td>
                        <td>{{ $transfert->validated_at->format('d/m/Y H:i') }} <small class="text-muted">({{ $transfert->validated_at->diffForHumans() }})</small></td>
                        <td>{{ $transfert->magasin->libelle }}</td>
                        <td>{{ $transfert->magasinDestination->libelle }}</td>
                        <td>{{ $transfert->lignes_count }}</td>
                        <td>{{ $transfert->validateur?->full_name }}</td>
                        <td class="text-end">
                            <a href="{{ route('stock.transferts.reception', $transfert->id) }}" class="btn btn-success btn-sm"><i class="fas fa-dolly"></i> Réceptionner</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted p-4">Aucun transfert en attente de réception.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@stop
