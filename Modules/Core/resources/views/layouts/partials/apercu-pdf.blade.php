{{-- Aperçu PDF en modale : tout lien ou bouton portant data-apercu-pdf="URL" (titre : data-titre) --}}
<div class="modal fade" id="modalApercuPdf" tabindex="-1" aria-labelledby="modalApercuPdfTitre" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-lg-down apercu-pdf-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title d-flex align-items-center gap-2 text-truncate" id="modalApercuPdfTitre">
                    <i class="fas fa-file-pdf text-danger"></i> <span class="titre">Aperçu</span>
                </h5>
                <div class="ms-auto d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="apercu-pdf-imprimer" title="Imprimer">
                        <i class="fas fa-print"></i><span class="d-none d-md-inline ms-1">Imprimer</span>
                    </button>
                    <a class="btn btn-sm btn-outline-secondary" id="apercu-pdf-telecharger" href="#" title="Télécharger">
                        <i class="fas fa-download"></i><span class="d-none d-md-inline ms-1">Télécharger</span>
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" id="apercu-pdf-onglet" href="#" target="_blank" rel="noopener" title="Ouvrir dans un nouvel onglet">
                        <i class="fas fa-up-right-from-square"></i>
                    </a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
            </div>
            <div class="modal-body p-0 position-relative bg-body-secondary">
                <div class="apercu-pdf-chargement position-absolute top-50 start-50 translate-middle text-center text-muted">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="small">Génération du document…</div>
                </div>
                <iframe id="apercu-pdf-cadre" title="Aperçu du document PDF" class="w-100 h-100 border-0 d-block"></iframe>
            </div>
        </div>
    </div>
</div>
<style>
    .apercu-pdf-dialog .modal-body { height: calc(100vh - 140px); }
    @media (max-width: 991.98px) { .apercu-pdf-dialog .modal-body { height: auto; flex: 1; } }
    #apercu-pdf-cadre { opacity: 0; transition: opacity .2s; }
    #apercu-pdf-cadre.charge { opacity: 1; }
</style>
