<form id="quick-article-form">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Code <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control" required>
        </div>
        <div class="col-md-8">
            <label class="form-label">Désignation <span class="text-danger">*</span></label>
            <input type="text" name="designation" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Catégorie <span class="text-danger">*</span></label>
            <select name="category_id" class="form-select" required>
                <option value="">Choisir une catégorie...</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Unité <span class="text-danger">*</span></label>
            <input type="text" name="unit" class="form-control" placeholder="Pièce" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Stock minimum <span class="text-danger">*</span></label>
            <input type="number" name="min_stock" class="form-control" value="0" min="0" required>
        </div>
        <div class="col-md-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
    </div>
    
    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </div>
</form>

<script>
$('#quick-article-form').on('submit', function(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    data._token = '{{ csrf_token() }}';

    $.ajax({
        url: '{{ route("inventory.articles.quick") }}',
        method: 'POST',
        data: data
    }).done(r => {
        if (typeof Lumiere !== 'undefined' && Lumiere.showToast) {
            Lumiere.showToast(r.message);
        }
        
        if (window.entryWizardInstance) {
            window.entryWizardInstance.addLine(r.data);
        }
        
        $(this).closest('.modal').modal('hide');
    }).fail(xhr => {
        const errors = xhr.responseJSON?.errors;
        if (errors) {
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            Object.entries(errors).forEach(([k,v]) =>
                $(`#quick-article-form [name="${k}"]`).addClass('is-invalid').after(`<div class="invalid-feedback">${v[0]}</div>`)
            );
        }
    });
});
</script>
