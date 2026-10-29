/**
 * magasins/responsables.js
 * Responsables (principal / adjoints) et utilisateurs rattachés d'un magasin.
 */
import { echapper, envoyer, confirmerPuisEnvoyer, notifierSucces } from '../common.js';

$(function () {
    const magasinId = $('#page-responsables').data('magasin-id');
    const badge = (role) => role === 'PRINCIPAL'
        ? '<span class="badge text-bg-primary">Principal</span>'
        : '<span class="badge text-bg-info">Adjoint</span>';

    function charger() {
        $.get(route('referentiel.magasins.responsables.data', magasinId)).done((data) => {
            $('#responsables-actuels').html(data.actuels.length
                ? data.actuels.map((r) => `
                    <tr>
                        <td>${echapper(r.utilisateur)}</td>
                        <td>${badge(r.role_responsable)}</td>
                        <td>${r.date_debut}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger btn-terminer" data-id="${r.id}" data-nom="${echapper(r.utilisateur)}">Terminer</button>
                        </td>
                    </tr>`).join('')
                : '<tr><td colspan="4" class="text-center text-muted">Aucun responsable actif</td></tr>');

            $('#responsables-historique').html(data.historique.length
                ? data.historique.map((r) => `
                    <tr>
                        <td>${echapper(r.utilisateur)}</td>
                        <td>${badge(r.role_responsable)}</td>
                        <td>${r.date_debut} → ${r.date_fin ?? '…'}</td>
                        <td>${r.actif ? '<span class="badge text-bg-success">Actif</span>' : '<span class="badge text-bg-secondary">Terminé</span>'}</td>
                    </tr>`).join('')
                : '<tr><td colspan="4" class="text-center text-muted">Aucun historique</td></tr>');

            $('#utilisateurs-rattaches').html(data.rattaches.length
                ? data.rattaches.map((u) => `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${echapper(u.utilisateur)} <small class="text-muted d-block">${echapper(u.email)}</small></span>
                        <button class="btn btn-sm btn-outline-danger btn-detacher" data-id="${u.id}" title="Détacher"><i class="fas fa-unlink"></i></button>
                    </li>`).join('')
                : '<li class="list-group-item text-muted">Aucun utilisateur rattaché</li>');
        });
    }

    $('#responsableForm').on('submit', function (e) {
        e.preventDefault();
        envoyer(route('referentiel.magasins.responsables.store', magasinId), 'POST', $(this).serialize())
            .done((response) => {
                notifierSucces(response.message);
                charger();
            })
            .fail((xhr) => {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: Object.values(xhr.responseJSON.errors)[0][0] });
                }
            });
    });

    $('#responsables-actuels').on('click', '.btn-terminer', function () {
        const id = $(this).data('id');
        confirmerPuisEnvoyer(`Terminer le mandat de ${$(this).data('nom')} à la date du jour ?`,
            route('referentiel.magasins.responsables.terminer', [magasinId, id]), 'POST')
            .done((response) => {
                notifierSucces(response.message);
                charger();
            });
    });

    $('#rattachementForm').on('submit', function (e) {
        e.preventDefault();
        envoyer(route('referentiel.magasins.utilisateurs.store', magasinId), 'POST', $(this).serialize())
            .done((response) => {
                notifierSucces(response.message);
                this.reset();
                charger();
            });
    });

    $('#utilisateurs-rattaches').on('click', '.btn-detacher', function () {
        confirmerPuisEnvoyer('Détacher cet utilisateur du magasin ?',
            route('referentiel.magasins.utilisateurs.destroy', [magasinId, $(this).data('id')]), 'DELETE')
            .done((response) => {
                notifierSucces(response.message);
                charger();
            });
    });

    charger();
});
