/**
 * parametres/index.js
 * Enregistrement des paramètres de l'application.
 */
import { envoyer, notifierSucces } from '../common.js';

$(function () {
    const $form = $('#form-parametres');

    $form.on('submit', function (e) {
        e.preventDefault();
        $form.find('.is-invalid').removeClass('is-invalid');

        envoyer($form.data('url'), 'PUT', $form.serialize())
            .done((reponse) => notifierSucces(reponse.message))
            .fail((xhr) => {
                $.each(xhr.responseJSON?.errors ?? {}, (champ, messages) => {
                    $form.find(`[name="${champ}"]`).not('[type=hidden]').addClass('is-invalid')
                        .closest('.col-md-6').find('.invalid-feedback').text(messages[0]);
                });
            });
    });
});
