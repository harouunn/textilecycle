<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Administration') | TexTileCycle</title>
  <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

  {{-- Police servie en local (plus de Google Fonts) : plus rapide et utilisable sans internet. --}}
  <link rel="stylesheet" href="{{ asset_v('back/fonts/polices.css') }}">

  <link rel="stylesheet" href="{{ asset_v('back/css/sneat.css') }}">
  <link rel="stylesheet" href="{{ asset_v('back/css/theme.css') }}">
  <link rel="stylesheet" href="{{ asset_v('back/css/custom.css') }}">
  <link rel="stylesheet" href="{{ asset_v('back/css/doux.css') }}">
  @stack('styles')
</head>

<body>
  <div class="v-application v-theme--light v-layout v-layout--full-height v-locale--is-ltr">
    <div class="v-application__wrap">
      <div data-v-fba8a720 class="layout-wrapper layout-nav-type-vertical layout-navbar-static layout-footer-static layout-content-width-fluid">

        @include('partials.back.sidebar')

        <div class="layout-content-wrapper">
          @include('partials.back.navbar')

          <main class="layout-page-content">
            <div class="page-content-container">
              @yield('content')
            </div>
          </main>

          @include('partials.back.footer')
        </div>

        <div class="layout-overlay" data-nav-close></div>
      </div>
    </div>
  </div>

  <script src="{{ asset_v('back/js/main.js') }}"></script>
  @stack('scripts')
</body>

</html>
