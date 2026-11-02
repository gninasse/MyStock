/**
 * apercu-pdf.js
 * Ouvre un PDF dans la modale d'aperçu : <a data-apercu-pdf="URL" data-titre="…">.
 * Le serveur sert le PDF « inline » ; ?telecharger=1 force le téléchargement.
 */
(function () {
    function avecParametre(url, cle, valeur) {
        const u = new URL(url, window.location.origin);
        u.searchParams.set(cle, valeur);
        return u.toString();
    }

    $(document).on('click', '[data-apercu-pdf]', function (e) {
        e.preventDefault();
        const url = $(this).attr('data-apercu-pdf') || $(this).attr('href');
        const titre = $(this).data('titre') || $(this).attr('title') || 'Aperçu du document';
        const $modal = $('#modalApercuPdf');
        const $cadre = $('#apercu-pdf-cadre');

        $modal.find('.titre').text(titre);
        $modal.find('.apercu-pdf-chargement').removeClass('d-none');
        $cadre.removeClass('charge').attr('src', url);
        $('#apercu-pdf-telecharger').attr('href', avecParametre(url, 'telecharger', 1));
        $('#apercu-pdf-onglet').attr('href', url);
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });

    $(function () {
        const cadre = document.getElementById('apercu-pdf-cadre');
        if (!cadre) return;
        cadre.addEventListener('load', function () {
            if (!cadre.getAttribute('src')) return;
            cadre.classList.add('charge');
            $('#modalApercuPdf .apercu-pdf-chargement').addClass('d-none');
        });

        $('#apercu-pdf-imprimer').on('click', function () {
            try {
                cadre.contentWindow.focus();
                cadre.contentWindow.print();
            } catch (err) {
                window.open(cadre.src, '_blank', 'noopener');
            }
        });

        // Libère le PDF à la fermeture
        $('#modalApercuPdf').on('hidden.bs.modal', function () {
            cadre.classList.remove('charge');
            cadre.removeAttribute('src');
        });
    });
})();
