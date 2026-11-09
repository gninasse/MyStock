{{-- Gabarit des pages d'erreur : autonome (aucun accès base de données) pour s'afficher même en cas de panne. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') · {{ config('app.name') }}</title>
    @include('core::layouts.partials.head-meta')
    <style>
        :root { --fond: #f4f6f9; --carte: #fff; --texte: #1f2d3d; --doux: #6c757d; --bord: #dee2e6; --accent: #0d6efd; }
        @media (prefers-color-scheme: dark) { :root { --fond: #1a1d21; --carte: #212529; --texte: #dee2e6; --doux: #adb5bd; --bord: #343a40; --accent: #6ea8fe; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: var(--fond); color: var(--texte);
            font-family: "Source Sans 3", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .erreur { width: 100%; max-width: 30rem; text-align: center; }
        .erreur-marque { display: inline-flex; align-items: center; gap: .55rem; font-weight: 700; letter-spacing: .05em; color: var(--texte); text-decoration: none; margin-bottom: 2rem; }
        .erreur-marque img { width: 34px; height: 34px; border-radius: 9px; }
        .erreur-code { font-size: 5.5rem; font-weight: 800; line-height: 1; color: var(--accent); letter-spacing: -.03em; }
        h1 { font-size: 1.5rem; margin: .8rem 0 .5rem; }
        p { color: var(--doux); line-height: 1.55; margin: 0 0 1.6rem; }
        .actions { display: flex; gap: .6rem; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-block; padding: .6rem 1.15rem; border-radius: .5rem; font-weight: 600; text-decoration: none; border: 1px solid var(--bord); color: var(--texte); background: var(--carte); cursor: pointer; font: inherit; }
        .btn-principal { background: var(--accent); border-color: var(--accent); color: #fff; }
        .pied { margin-top: 2.5rem; font-size: .8rem; color: var(--doux); }
    </style>
</head>
<body>
    <main class="erreur">
        <a class="erreur-marque" href="{{ url('/') }}"><img src="{{ asset('brand/logo.svg') }}" alt=""> MyStock</a>
        <div class="erreur-code">@yield('code')</div>
        <h1>@yield('titre')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            @section('actions')
                <button type="button" class="btn" onclick="history.back()">Page précédente</button>
                <a class="btn btn-principal" href="{{ url('/') }}">Tableau de bord</a>
            @show
        </div>
        <div class="pied">{{ config('app.organisation') }} · {{ config('app.name') }} v{{ config('app.version') }}</div>
    </main>
</body>
</html>
