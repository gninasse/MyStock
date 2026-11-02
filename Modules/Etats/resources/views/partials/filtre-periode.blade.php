{{-- Période du / au, avec raccourcis. $defautDu : date de début par défaut (Y-m-d) --}}
<div class="col-md-2">
    <label class="form-label small mb-0" for="date_du">Du</label>
    <input type="date" class="form-control form-control-sm" id="date_du" name="date_du" value="{{ $filtres['date_du'] ?? $defautDu }}">
</div>
<div class="col-md-2">
    <label class="form-label small mb-0" for="date_au">Au</label>
    <input type="date" class="form-control form-control-sm" id="date_au" name="date_au" value="{{ $filtres['date_au'] ?? now()->toDateString() }}">
</div>
