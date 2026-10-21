<!DOCTYPE html>
<html lang="fr">

<head>
  <title>@yield('title', 'Accueil') | TexTileCycle</title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
  <meta name="theme-color" content="#3f7556">
  <meta name="format-detection" content="telephone=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="author" content="TexTileCycle">
  <meta name="keywords" content="vêtements,seconde vie,dépôt,réparation,upcycling,don,associations,recyclage textile">
  <meta name="description" content="TexTileCycle : la plateforme qui donne une seconde vie à vos vêtements grâce au dépôt, à la réparation, à l'upcycling et au don aux associations.">

  {{-- Tout est servi en local (plus de CDN ni de Google Fonts) : chargement plus rapide et site utilisable sans internet. --}}
  <link rel="preload" href="{{ asset_v('front/fonts/polices.css') }}" as="style">
  <link rel="stylesheet" href="{{ asset_v('front/vendor/bootstrap/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset_v('front/fonts/polices.css') }}">
  <link rel="stylesheet" href="{{ asset_v('front/style.css') }}">
  <link rel="stylesheet" href="{{ asset_v('front/css/custom.css') }}">
  <link rel="stylesheet" href="{{ asset_v('front/css/textilecycle.css') }}">
  <link rel="stylesheet" href="{{ asset_v('front/css/mouvement.css') }}">
  <link rel="stylesheet" href="{{ asset_v('front/css/theme.css') }}">
  <link rel="stylesheet" href="{{ asset_v('front/css/doux.css') }}">
  @stack('styles')
</head>

<body class="@yield('body_class')">
  @include('partials.front.icons')

  <div class="tc-progression" aria-hidden="true"></div>

  @include('partials.front.navbar')

  @yield('content')

  @include('partials.front.footer')

  <script src="{{ asset_v('front/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset_v('front/js/mouvement.js') }}"></script>
  @stack('scripts')
</body>

</html>
