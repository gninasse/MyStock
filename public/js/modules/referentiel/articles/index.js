/**
 * articles/index.js
 * Référentiel global des articles.
 */
import { TableSelection, FormulaireModal, confirmerPuisEnvoyer, notifierSucces } from '../common.js';

$(function () {
    const $table = $('#articles-table');
    const table = new TableSelection('#articles-table');

    // Modifier / désactiver restent grisés sans la permission
    $table.on('selection-changee', (e, ligne) => {
        ['#btn-edit', '#btn-toggle'].forEach((b) => $(b).prop('disabled', !ligne || $(b).data('autorise') !== 1));
    });

    const formulaire = new FormulaireModal({
        modal: '#articleModal',
        form: '#articleForm',
        titreAjout: 'Ajouter un article',
        titreModif: 'Modifier l\'article',
        urlStore: route('referentiel.articles.store'),
        urlUpdate: (id) => route('referentiel.articles.update', id),
        apresEnregistrement: () => table.refresh()
    });

    // Unité proposée par défaut selon la catégorie
    $('#categorie_id').on('change', function () {
        const unite = $(this).find(':selected').data('unite');
        if (unite && !$('#unite_mesure').val()) $('#unite_mesure').val(unite);
    });

    $('#filtre-categorie').on('change', () => table.refresh());

    $('#btn-add').on('click', () => formulaire.ouvrirAjout());

    $('#btn-edit').on('click', () => {
        const ligne = table.selection();
        if (ligne) formulaire.ouvrirModification(ligne);
    });

    $('#btn-toggle').on('click', () => {
        const ligne = table.selection();
        if (!ligne) return;
        const action = ligne.actif ? 'Désactiver' : 'Activer';
        confirmerPuisEnvoyer(`${action} l'article « ${ligne.designation} » ?`, route('referentiel.articles.toggle-status', ligne.id), 'POST')
            .done((response) => {
                notifierSucces(response.message);
                table.refresh();
            });
    });
});
