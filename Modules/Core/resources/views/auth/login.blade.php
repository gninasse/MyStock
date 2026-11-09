<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · {{ config('app.name') }}</title>
    @include('core::layouts.partials.head-meta')

    <link rel="stylesheet" href="{{ asset('plugins/source-sans-3/index.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.css') }}">
    <style>
        body { min-height: 100vh; margin: 0; display: flex; background: #eef2f7; font-family: "Source Sans 3", system-ui, sans-serif; }
        .connexion { flex: 1; display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); min-height: 100vh; }
        .connexion-identite { position: relative; overflow: hidden; color: #fff; padding: 3rem 3.5rem; display: flex; flex-direction: column;
            background: linear-gradient(145deg, #3d8bfd 0%, #0a58ca 55%, #084298 100%); }
        .connexion-identite::after { content: ""; position: absolute; width: 520px; height: 520px; right: -180px; bottom: -200px; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,.16), rgba(255,255,255,0) 70%); }
        .connexion-marque { display: flex; align-items: center; gap: .8rem; font-size: 1.5rem; font-weight: 700; letter-spacing: .04em; }
        .connexion-marque img { width: 48px; height: 48px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,.18); }
        .connexion-accroche { margin: auto 0; max-width: 30rem; position: relative; z-index: 1; }
        .connexion-accroche h1 { font-size: 2.1rem; font-weight: 700; line-height: 1.2; margin-bottom: 1rem; }
        .connexion-accroche p { opacity: .85; font-size: 1.05rem; }
        .connexion-points { list-style: none; padding: 0; margin: 1.6rem 0 0; display: grid; gap: .7rem; }
        .connexion-points li { display: flex; gap: .7rem; align-items: center; opacity: .95; }
        .connexion-points i { width: 2rem; height: 2rem; border-radius: .6rem; display: grid; place-items: center; background: rgba(255,255,255,.15); font-size: .9rem; }
        .connexion-etablissement { font-size: .85rem; opacity: .8; position: relative; z-index: 1; }
        .connexion-formulaire { display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 2.5rem 1.5rem; background: #fff; }
        .connexion-boite { width: 100%; max-width: 380px; }
        .connexion-boite h2 { font-size: 1.6rem; font-weight: 700; margin-bottom: .3rem; }
        .connexion-boite .form-control { padding: .7rem .9rem; }
        .connexion-boite .btn-primary { padding: .7rem; font-weight: 600; }
        .connexion-pied { margin-top: 2.5rem; font-size: .8rem; color: #6c757d; text-align: center; }
        .connexion-mobile { display: none; }
        @media (max-width: 900px) {
            .connexion { grid-template-columns: 1fr; }
            .connexion-identite { display: none; }
            .connexion-mobile { display: flex; justify-content: center; margin-bottom: 1.8rem; color: #212529; }
        }
        @media (prefers-color-scheme: dark) {
            body, .connexion-formulaire { background: #1a1d21; color: #dee2e6; }
            .connexion-mobile { color: #dee2e6; }
        }
    </style>
</head>
<body>
@include('core::layouts.partials.splash')

<main class="connexion">
    <section class="connexion-identite" aria-hidden="true">
        <div class="connexion-marque"><img src="{{ asset('brand/logo.svg') }}" alt=""> MyStock</div>
        <div class="connexion-accroche">
            <h1>La gestion des stocks des magasins, au même endroit.</h1>
            <p>Entrées, sorties, transferts, lots et inventaires, avec des imprimés officiels prêts à signer.</p>
            <ul class="connexion-points">
                <li><i class="fas fa-warehouse"></i> Stocks par magasin en temps réel</li>
                <li><i class="fas fa-truck-ramp-box"></i> Transferts tracés jusqu'à la réception</li>
                <li><i class="fas fa-file-signature"></i> Bons et procès-verbaux au format administratif</li>
            </ul>
        </div>
        <div class="connexion-etablissement">{{ config('app.organisation') }} · Ouagadougou, Burkina Faso</div>
    </section>

    <section class="connexion-formulaire">
        <div class="connexion-boite">
            <div class="connexion-marque connexion-mobile"><img src="{{ asset('brand/logo.svg') }}" alt=""> MyStock</div>

            <h2>Connexion</h2>
            <p class="text-body-secondary mb-4">Connectez-vous pour ouvrir votre session.</p>

            @error('login_error')
                <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
                    <i class="fas fa-circle-exclamation"></i> Identifiant ou mot de passe incorrect.
                </div>
            @enderror

            <form action="{{ route('login.post') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label for="login" class="form-label fw-semibold">E-mail ou nom d'utilisateur</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="login" id="login" class="form-control" value="{{ old('login') }}" autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" autocomplete="current-password" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Afficher le mot de passe">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-right-to-bracket me-1"></i> Se connecter</button>
                </div>
            </form>

            <p class="small text-body-secondary mt-3 mb-0">Mot de passe oublié ? Contactez l'administrateur de l'application.</p>

            <div class="connexion-pied">
                &copy; {{ now()->year }} {{ \Modules\Referentiel\Services\Parametres::get('organisation_nom') }} · {{ config('app.name') }} v{{ config('app.version') }}
            </div>
        </div>
    </section>
</main>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const champ = document.getElementById('password');
        const visible = champ.type === 'password';
        champ.type = visible ? 'text' : 'password';
        this.querySelector('i').className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
        this.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
</script>
</body>
</html>
