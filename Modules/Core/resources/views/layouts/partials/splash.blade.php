{{--
    Écran de démarrage affiché pendant le chargement de chaque page.
    CSS et JS en ligne : il apparaît avant le chargement des feuilles de style
    et disparaît en fondu dès que la page est prête (8 s au plus).
--}}
@php
    try {
        $splashOrganisation = \Modules\Referentiel\Services\Parametres::get('organisation_nom');
    } catch (\Throwable) {
        $splashOrganisation = null;
    }
@endphp
<style>
    #splash { position: fixed; inset: 0; z-index: 2000; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.1rem;
        background: #f4f6f9; color: #1f2d3d; font-family: "Source Sans 3", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        transition: opacity .35s ease, visibility .35s ease; }
    #splash.splash-cache { opacity: 0; visibility: hidden; pointer-events: none; }
    #splash .splash-logo { width: 76px; height: 76px; border-radius: 18px; background: #0d6efd;
        box-shadow: 0 10px 30px rgba(13, 110, 253, .28); animation: splash-pulse 1.6s ease-in-out infinite; }
    #splash .splash-nom { font-size: 1.6rem; font-weight: 700; letter-spacing: .08em; line-height: 1; }
    #splash .splash-nom span { color: #0d6efd; }
    #splash .splash-org { font-size: .85rem; letter-spacing: .14em; text-transform: uppercase; opacity: .65; margin-top: -.5rem; }
    #splash .splash-barre { width: 160px; height: 4px; border-radius: 4px; background: rgba(13, 110, 253, .15); overflow: hidden; }
    #splash .splash-barre::after { content: ""; display: block; width: 40%; height: 100%; border-radius: 4px; background: #0d6efd; animation: splash-barre 1.1s ease-in-out infinite; }
    @keyframes splash-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.06); } }
    @keyframes splash-barre { 0% { transform: translateX(-100%); } 100% { transform: translateX(250%); } }
    @media (prefers-color-scheme: dark) {
        #splash { background: #1a1d21; color: #dee2e6; }
        #splash .splash-nom span { color: #6ea8fe; }
    }
    @media (prefers-reduced-motion: reduce) {
        #splash, #splash .splash-logo, #splash .splash-barre::after { animation: none; transition: none; }
    }
</style>
<div id="splash" role="status" aria-live="polite" aria-label="Chargement de MyStock">
    <img class="splash-logo" src="{{ asset('brand/logo.svg') }}" alt="" width="76" height="76">
    <div class="splash-nom">MY<span>STOCK</span></div>
    @if ($splashOrganisation)
        <div class="splash-org">{{ $splashOrganisation }}</div>
    @endif
    <div class="splash-barre" aria-hidden="true"></div>
</div>
<script>
    (function () {
        var splash = document.getElementById('splash');
        var masquer = function () { splash.classList.add('splash-cache'); };
        window.addEventListener('load', masquer);
        // Retour arrière servi depuis le cache du navigateur : la page est déjà prête.
        window.addEventListener('pageshow', function (e) { if (e.persisted) { masquer(); } });
        // Filet de sécurité si une ressource tarde à répondre.
        setTimeout(masquer, 8000);
    })();
</script>
