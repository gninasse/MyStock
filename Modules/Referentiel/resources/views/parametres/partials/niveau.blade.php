{{-- Une ligne de la hiérarchie administrative (écran En-tête des documents). --}}
<div class="input-group mb-2 niveau-ligne">
    <span class="input-group-text text-body-secondary numero" style="min-width: 2.6rem"></span>
    <input type="text" class="form-control" value="{{ $niveau }}" maxlength="120" aria-label="Niveau de hiérarchie">
    <button type="button" class="btn btn-outline-secondary monter" title="Monter" aria-label="Monter"><i class="bi bi-arrow-up"></i></button>
    <button type="button" class="btn btn-outline-secondary descendre" title="Descendre" aria-label="Descendre"><i class="bi bi-arrow-down"></i></button>
    <button type="button" class="btn btn-outline-danger retirer" title="Retirer" aria-label="Retirer"><i class="bi bi-x-lg"></i></button>
</div>
