{{-- Pages d'erreur : autonomes (styles intégrés, aucune requête en base) pour s'afficher même si le site est en panne. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>@yield('titre') — TexTileCycle</title>
  <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
  <style>
    :root { --vert: #3f7556; --foret: #2f5a45; --encre: #24332b; --texte: #4a564f; --fond: #fbf9f4; --sauge: #eef4ea; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: linear-gradient(160deg, #eaf3e6, var(--fond) 55%, #f6ece0);
      font: 18px/1.7 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: var(--texte); }
    main { max-width: 560px; text-align: center; background: #fff; border-radius: 24px; padding: 48px 36px; box-shadow: 0 10px 40px -16px rgba(36, 51, 43, .25); }
    .code { display: inline-block; padding: 6px 16px; border-radius: 999px; background: var(--sauge); color: var(--vert); font-weight: 600; font-size: 16px; }
    h1 { margin: 18px 0 10px; color: var(--encre); font-size: 30px; line-height: 1.25; font-weight: 600; }
    p { margin: 0 0 28px; }
    .actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
    a { display: inline-flex; align-items: center; min-height: 48px; padding: 0 22px; border-radius: 999px; font-weight: 600; text-decoration: none; }
    .principal { background: var(--foret); color: #fff; }
    .secondaire { border: 1.5px solid #cddbc9; color: var(--foret); }
    a:focus-visible { outline: 3px solid #7fb38f; outline-offset: 3px; }
    .marque { margin-top: 32px; font-size: 15px; color: #6f7a73; }
    .marque strong { color: var(--encre); font-weight: 600; } .marque span { color: var(--vert); }
  </style>
</head>
<body>
  <main>
    <span class="code">Erreur @yield('code')</span>
    <h1>@yield('titre')</h1>
    <p>@yield('message')</p>
    <div class="actions">
      <a class="principal" href="{{ url('/') }}">Revenir à l'accueil</a>
      @hasSection('action')
        @yield('action')
      @else
        <a class="secondaire" href="{{ url('/depot/catalogue') }}">Voir le catalogue</a>
      @endif
    </div>
    <div class="marque"><strong>TexTile<span>Cycle</span></strong> · donnons une seconde vie à nos vêtements</div>
  </main>
</body>
</html>
