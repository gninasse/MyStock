{{-- Liste à choix multiples des magasins visibles ; aucun choix = tous --}}
<label class="form-label small mb-0" for="magasins">Magasin(s)</label>
<select class="form-select form-select-sm" id="magasins" name="magasins[]" multiple size="{{ min(4, max(2, $magasins->count())) }}">
    @foreach ($magasins as $magasin)
        <option value="{{ $magasin->id }}" @selected(in_array($magasin->id, $filtres['magasins'] ?? []))>{{ $magasin->libelle }}</option>
    @endforeach
</select>
<div class="form-text">Aucune sélection = tous. Ctrl+clic pour en choisir plusieurs.</div>
