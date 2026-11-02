{{-- Liste déroulante de filtre : $nom, $libelle, $options (collection id/libelle), $tous (libellé de l'option vide), $col --}}
<div class="{{ $col ?? 'col-md-3' }}">
    <label class="form-label small mb-0" for="{{ $nom }}">{{ $libelle }}</label>
    <select class="form-select form-select-sm" id="{{ $nom }}" name="{{ $nom }}">
        <option value="">{{ $tous ?? 'Tous' }}</option>
        @foreach ($options as $option)
            <option value="{{ $option->id }}" @selected(($filtres[$nom] ?? null) == $option->id)>{{ $option->libelle }}</option>
        @endforeach
    </select>
</div>
