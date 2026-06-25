/**
 * Lumière Gestion - Core JavaScript
 * Système global pour gestion AJAX, modales, toasts et interactions
 */

class LumiereApp {
    constructor() {
        this.init();
    }

    init() {
        this.initAjaxSetup();
        this.initToasts();
        this.initModals();
        this.initOffcanvas();
        console.log('✨ Lumière App initialized');
    }

    /**
     * Configuration AJAX globale
     */
    initAjaxSetup() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            error: (xhr) => {
                if (xhr.status === 419) {
                    this.showToast('Session expirée. Veuillez recharger la page.', 'danger');
                } else if (xhr.status === 403) {
                    this.showToast('Accès non autorisé', 'danger');
                } else if (xhr.status === 500) {
                    this.showToast('Erreur serveur', 'danger');
                }
            }
        });
    }

    /**
     * Système de toasts Bootstrap 5
     */
    initToasts() {
        if (!$('#toast-container').length) {
            $('body').append('<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
        }
    }

    /**
     * Afficher un toast
     * @param {string} message 
     * @param {string} type - success|danger|warning|info
     * @param {number} duration - durée en ms (défaut 4000)
     */
    showToast(message, type = 'success', duration = 4000) {
        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const colors = {
            success: 'var(--success)',
            danger: 'var(--danger)',
            warning: 'var(--warning)',
            info: 'var(--info)'
        };

        const toastId = `toast-${Date.now()}`;
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white border-0" role="alert" style="background: ${colors[type]};">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas ${icons[type]} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        $('#toast-container').append(toastHtml);
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: duration });
        toast.show();

        // Supprimer après disparition
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    /**
     * Initialisation des modales multi-niveaux
     */
    initModals() {
        let modalStack = [];

        // Gestion du z-index pour modales empilées
        $(document).on('show.bs.modal', '.modal', function () {
            const zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);
            setTimeout(() => {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);
            modalStack.push($(this).attr('id'));
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            modalStack.pop();
            if (modalStack.length > 0) {
                $('body').addClass('modal-open');
            }
        });
    }

    /**
     * Ouvrir une modale avec contenu AJAX
     * @param {string} url - URL pour charger le contenu
     * @param {object} options - { size: 'lg'|'xl', title: '' }
     */
    openModal(url, options = {}) {
        const modalId = `modal-${Date.now()}`;
        const size = options.size || 'lg';

        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog modal-${size} modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                ${options.icon ? `<i class="fas fa-${options.icon}"></i>` : ''}
                                ${options.title || 'Chargement...'}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();

        // Charger le contenu via AJAX
        $.get(url, (data) => {
            $(`#${modalId} .modal-body`).html(data);
        }).fail(() => {
            $(`#${modalId} .modal-body`).html('<p class="text-danger">Erreur de chargement</p>');
        });

        // Supprimer la modale du DOM après fermeture
        document.getElementById(modalId).addEventListener('hidden.bs.modal', function () {
            this.remove();
        });

        return modal;
    }

    /**
     * Fermer toutes les modales
     */
    closeAllModals() {
        $('.modal').modal('hide');
    }

    /**
     * Initialisation des offcanvas
     */
    initOffcanvas() {
        // Aucune configuration spécifique pour l'instant
    }

    /**
     * Chargement dynamique de contenu dans un container
     * @param {string} selector - Sélecteur du container
     * @param {string} url - URL pour charger le contenu
     * @param {object} data - Données POST optionnelles
     */
    loadContent(selector, url, data = {}) {
        const $container = $(selector);
        
        // Spinner de chargement
        $container.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        `);

        const method = Object.keys(data).length > 0 ? 'POST' : 'GET';

        $.ajax({
            url: url,
            method: method,
            data: data,
            success: (response) => {
                $container.html(response);
            },
            error: () => {
                $container.html('<p class="text-danger">Erreur de chargement</p>');
            }
        });
    }

    /**
     * Confirmer une action avec modale Bootstrap
     * @param {object} options - { title, message, onConfirm, confirmText, cancelText }
     */
    confirm(options = {}) {
        const defaults = {
            title: 'Confirmation',
            message: 'Êtes-vous sûr de vouloir continuer ?',
            confirmText: 'Confirmer',
            cancelText: 'Annuler',
            type: 'danger',
            onConfirm: () => {}
        };

        const settings = { ...defaults, ...options };
        const modalId = `confirm-modal-${Date.now()}`;

        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle text-${settings.type}"></i>
                                ${settings.title}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>${settings.message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i>
                                ${settings.cancelText}
                            </button>
                            <button type="button" class="btn btn-${settings.type}" id="${modalId}-confirm">
                                <i class="fas fa-check"></i>
                                ${settings.confirmText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();

        // Action de confirmation
        $(`#${modalId}-confirm`).on('click', function() {
            settings.onConfirm();
            modal.hide();
        });

        // Supprimer du DOM après fermeture
        document.getElementById(modalId).addEventListener('hidden.bs.modal', function () {
            this.remove();
        });
    }

    /**
     * Afficher/masquer un spinner de chargement global
     */
    showLoading() {
        if (!$('#global-loading').length) {
            $('body').append(`
                <div id="global-loading" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; display: flex; align-items: center; justify-content: center;">
                    <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            `);
        } else {
            $('#global-loading').show();
        }
    }

    hideLoading() {
        $('#global-loading').fadeOut(200);
    }
}

// Instance globale
window.Lumiere = new LumiereApp();

// Export pour usage ES6 modules si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LumiereApp;
}
