<!DOCTYPE html>
<html lang="fr">

<head>
  <title>@yield('title', 'Accueil') | TexTileCycle</title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="format-detection" content="telephone=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="author" content="TexTileCycle">
  <meta name="keywords" content="vêtements,seconde vie,dépôt,réparation,upcycling,don,associations,recyclage textile">
  <meta name="description" content="TexTileCycle : la plateforme qui donne une seconde vie à vos vêtements grâce au dépôt, à la réparation, à l'upcycling et au don aux associations.">

  {{-- Tout est servi en local (plus de CDN ni de Google Fonts) : chargement plus rapide et site utilisable sans internet. --}}
  <link rel="preload" href="{{ asset('front/fonts/polices.css') }}" as="style">
  <link rel="stylesheet" href="{{ asset('front/vendor/bootstrap/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('front/css/vendor.css') }}">
  <link rel="stylesheet" href="{{ asset('front/vendor/swiper/swiper-bundle.min.css') }}">
  <link rel="stylesheet" href="{{ asset('front/fonts/polices.css') }}">
  <link rel="stylesheet" href="{{ asset('front/style.css') }}">
  <link rel="stylesheet" href="{{ asset('front/css/custom.css') }}">
  <link rel="stylesheet" href="{{ asset('front/css/textilecycle.css') }}">
  @stack('styles')
</head>

<body class="@yield('body_class')">
  @include('partials.front.icons')

  @include('partials.front.navbar')

  @yield('content')

  @include('partials.front.footer')

  <script src="{{ asset('front/js/jquery.min.js') }}"></script>
  <script src="{{ asset('front/js/plugins.js') }}"></script>
  <script src="{{ asset('front/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('front/vendor/swiper/swiper-bundle.min.js') }}"></script>
  <script src="{{ asset('front/js/script.min.js') }}"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  @stack('scripts')
</body>

</html>
