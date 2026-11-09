{{-- Icônes, manifeste et métadonnées communs à toutes les pages HTML. --}}
<meta name="application-name" content="{{ config('app.name') }}">
<meta name="description" content="{{ config('app.name') }} : gestion des stocks des magasins du {{ config('app.organisation') }}.">
<meta name="author" content="{{ config('app.organisation') }}">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0a58ca">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" href="{{ asset('brand/icon-32.png') }}" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
