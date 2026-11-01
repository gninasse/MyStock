/**
 * document.js
 * Actions de la fiche d'un document de stock.
 */
import { confirmerPuisEnvoyer, notifierSucces } from '../referentiel/common.js';

$(function () {
    $('#btn-annuler-transfert').on('click', function () {
        const $btn = $(this);
        confirmerPuisEnvoyer(
            `Annuler le transfert ${$btn.data('reference')} ? Toutes les quantités seront réintégrées dans le magasin source.`,
            $btn.data('url'), 'POST', {},
            { confirmer: 'Oui, annuler le transfert', renoncer: 'Non' }
        ).done((reponse) => {
            notifierSucces(reponse.message);
            setTimeout(() => window.location.reload(), 1500);
        });
    });
});
