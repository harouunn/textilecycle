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

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  <link rel="stylesheet" type="text/css" href="{{ asset('front/css/vendor.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
  <link rel="stylesheet" type="text/css" href="{{ asset('front/style.css') }}">
  <link rel="stylesheet" type="text/css" href="{{ asset('front/css/custom.css') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&family=Marcellus&display=swap"
    rel="stylesheet">
  @stack('styles')
</head>

<body class="@yield('body_class')">
  @include('partials.front.icons')

  <div class="preloader text-white fs-6 text-uppercase overflow-hidden"></div>

  @include('partials.front.navbar')

  @yield('content')

  @include('partials.front.footer')

  <script src="{{ asset('front/js/jquery.min.js') }}"></script>
  <script src="{{ asset('front/js/plugins.js') }}"></script>
  <script src="{{ asset('front/js/SmoothScroll.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script src="{{ asset('front/js/script.min.js') }}"></script>
  @stack('scripts')
</body>

</html>
