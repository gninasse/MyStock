/**
 * reception.js
 * Réception d'un transfert : quantités reçues, motif obligatoire en cas d'écart, refus.
 */
import { echapper, envoyerJson } from '../referentiel/common.js';

$(function () {
    const $page = $('#page-reception');

    function majEcarts() {
        let ecart = false;
        $page.find('tr[data-article]').each(function () {
            const $tr = $(this);
            const diff = (parseInt($tr.find('.qte-recue').val(), 10) || 0) - $tr.data('expedie');
            ecart ||= diff !== 0;
            $tr.find('.ecart').text(diff ? diff : '').toggleClass('text-danger fw-bold', diff !== 0);
        });
        $('#zone-motif').toggleClass('d-none', !ecart);
    }

    function envoyer(quantites) {
        const $boutons = $('#btn-receptionner, #btn-refuser').prop('disabled', true);
        $('#erreurs').addClass('d-none');

        envoyerJson($page.data('url'), 'POST', { quantites, motif: $('#motif').val() })
            .done((reponse) => {
                Swal.fire({ icon: 'success', title: 'Succès', text: reponse.message }).then(() => {
                    window.location = $page.data('retour');
                });
            })
            .fail((xhr) => {
                if (xhr.status !== 422) return;
                const messages = [...new Set(Object.values(xhr.responseJSON.errors).flat())];
                $('#erreurs').html(`<ul class="mb-0">${messages.map((m) => `<li>${echapper(m)}</li>`).join('')}</ul>`).removeClass('d-none');
                $('html, body').animate({ scrollTop: 0 }, 200);
            })
            .always(() => $boutons.prop('disabled', false));
    }

    const quantitesSaisies = () => Object.fromEntries(
        $page.find('tr[data-article]').map((i, tr) => [[$(tr).data('article'), parseInt($(tr).find('.qte-recue').val(), 10) || 0]]).get()
    );

    $page.on('input change', '.qte-recue', majEcarts);
    $('#btn-receptionner').on('click', () => envoyer(quantitesSaisies()));

    $('#btn-refuser').on('click', () => {
        Swal.fire({
            icon: 'warning',
            title: 'Refuser le transfert',
            text: 'Aucune quantité ne sera ajoutée à votre magasin. Indiquez le motif du refus :',
            input: 'text',
            inputAttributes: { maxlength: 500 },
            showCancelButton: true,
            confirmButtonText: 'Refuser le transfert',
            cancelButtonText: 'Annuler',
            inputValidator: (valeur) => (!valeur?.trim() ? 'Le motif est obligatoire.' : undefined)
        }).then((resultat) => {
            if (!resultat.isConfirmed) return;
            $('#motif').val(resultat.value);
            const quantites = quantitesSaisies();
            Object.keys(quantites).forEach((k) => (quantites[k] = 0));
            envoyer(quantites);
        });
    });

    majEcarts();
});
