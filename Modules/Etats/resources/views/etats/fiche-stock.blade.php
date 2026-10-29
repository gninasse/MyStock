@extends('core::layouts.master')

@section('header', 'Fiche de stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">États</a></li>
    <li class="breadcrumb-item active" aria-current="page">Fiche de stock</li>
@endsection

@section('content')
<form method="GET" class="card mb-3 no-print">
    <div class="card-body row g-2">
        <div class="col-md-4">
            <label class="form-label small mb-0" for="article_id">Article <span class="text-danger">*</span></label>
            <input class="form-control form-control-sm" list="liste-articles" id="article_recherche" placeholder="Rechercher un article…"
                   value="{{ optional($articles->firstWhere('id', $filtres['article_id'] ?? null), fn ($a) => $a->designation.' ('.$a->code.')') }}">
            <datalist id="liste-articles">
                @foreach ($articles as $article)
                    <option data-id="{{ $article->id }}" value="{{ $article->designation }} ({{ $article->code }})"></option>
                @endforeach
            </datalist>
            <input type="hidden" name="article_id" id="article_id" value="{{ $filtres['article_id'] ?? '' }}">
            @error('article_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-0" for="magasin_id">Magasin <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="magasin_id" name="magasin_id" required>
                <option value="">— Choisir —</option>
                @foreach ($magasins as $magasin)
                    <option value="{{ $magasin->id }}" @selected(($filtres['magasin_id'] ?? null) == $magasin->id)>{{ $magasin->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0" for="date_du">Du</label>
            <input type="date" class="form-control form-control-sm" id="date_du" name="date_du" value="{{ $filtres['date_du'] ?? now()->startOfYear()->toDateString() }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-0" for="date_au">Au</label>
            <input type="date" class="form-control form-control-sm @error('date_au') is-invalid @enderror" id="date_au" name="date_au" value="{{ $filtres['date_au'] ?? now()->toDateString() }}">
            @error('date_au')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-1 text-end">
            <button class="btn btn-primary btn-sm mt-3"><i class="fas fa-search"></i></button>
        </div>
    </div>
</form>

@if ($etat)
    @include('etats::partials.resultat')
@else
    <div class="alert alert-info">Choisissez un article et un magasin pour afficher la fiche de stock.</div>
@endif
@stop

@push('js')
<script>
    $(function () {
        $('#article_recherche').on('input change', function () {
            const option = $(`#liste-articles option[value="${CSS.escape(this.value)}"]`);
            $('#article_id').val(option.data('id') || '');
        });
    });
</script>
@endpush
