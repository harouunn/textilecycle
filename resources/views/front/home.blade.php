@extends('layouts.front')

@section('title', 'Donnez une seconde vie à vos vêtements')
@section('body_class', 'homepage')

@section('content')
@php
  $ctaUrl = auth()->check() ? auth()->user()->accueil() : route('register');


  $testimonials = [
    ['quote' => '« J\'ai déposé trois sacs de vêtements en dix minutes. Savoir qu\'ils vont servir à quelqu\'un, ça n\'a pas de prix. »', 'author' => 'Sarah, donatrice'],
    ['quote' => '« Ma veste préférée a été réparée comme neuve. Je ne pensais pas qu\'on pouvait la sauver ! »', 'author' => 'Karim, utilisateur'],
    ['quote' => '« Grâce à TexTileCycle, nous recevons chaque mois des vêtements triés et en bon état pour les familles. »', 'author' => 'Association partenaire'],
    ['quote' => '« L\'upcycling m\'a permis de transformer de vieux jeans en un sac unique. Bravo pour l\'initiative. »', 'author' => 'Léa, créatrice'],
  ];
@endphp

  {{-- Bannière en mouvement : titre révélé mot par mot, mosaïque de photos qui défilent --}}
  @php
    $motsTitre = ['Donnez', 'une', '<em>seconde</em>', '<em>vie</em>', 'à', 'vos', 'vêtements'];
  @endphp
  <section id="billboard" class="tc-hero-mouvement tc-page">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <div class="tc-surtitre">Économie circulaire du textile</div>
          {{-- Chaque mot glisse vers le haut, l'un après l'autre (texte fixe, sans saisie utilisateur). --}}
          <h1 class="tc-hero-titre">{!! collect($motsTitre)->map(fn ($mot, $i) => '<span class="mot"><span style="--i: '.$i.'">'.$mot.'</span></span>')->implode(' ') !!}</h1>
          <p class="tc-hero-texte">Déposez, réparez, transformez ou donnez : TexTileCycle trouve la meilleure destination pour chaque vêtement, près de chez vous.</p>
          <div class="tc-hero-actions d-flex flex-wrap gap-3 mt-4">
            <a href="{{ route('depot.mes-depots.create') }}" class="tc-btn tc-btn--vert tc-btn--magnetique"><x-ui.icone nom="plus" /> Déposer un vêtement</a>
            <a href="{{ route('depot.catalogue.index') }}" class="tc-btn tc-btn--clair tc-btn--magnetique">Explorer le catalogue <x-ui.icone nom="fleche" /></a>
          </div>
          <div class="tc-hero-pastilles">
            <div><strong data-compteur="{{ $chiffres['vetements'] }}">{{ $chiffres['vetements'] }}</strong><span>vêtements disponibles</span></div>
            <div><strong data-compteur="{{ $chiffres['ateliers'] }}">{{ $chiffres['ateliers'] }}</strong><span>ateliers partenaires</span></div>
            <div><strong data-compteur="{{ $chiffres['associations'] }}">{{ $chiffres['associations'] }}</strong><span>associations</span></div>
          </div>
        </div>
        <div class="col-lg-6 position-relative" aria-hidden="true">
          <div class="tc-hero-flottant tc-hero-flottant--1"><span class="tc-stat__icone"><x-ui.icone nom="feuille" /></span><div><strong>{{ number_format($chiffres['impact']['eau'], 0, ',', ' ') }} L</strong>d'eau économisés</div></div>
          <div class="tc-hero-flottant tc-hero-flottant--2"><span class="tc-stat__icone"><x-ui.icone nom="valide" /></span><div><strong>Vérifié</strong>par notre équipe</div></div>
          <div class="tc-mosaique">
            @foreach ($mosaique as $colonne)
              <div class="tc-mosaique__colonne">
                <div class="tc-mosaique__piste">
                  {{-- La colonne est répétée deux fois : le défilement boucle sans couture. --}}
                  @foreach ([...$colonne, ...$colonne] as $photo)
                    <a href="{{ $photo['lien'] }}" tabindex="-1"><img src="{{ $photo['src'] }}" alt="" width="300" height="400" @if ($loop->index >= 3) loading="lazy" @endif decoding="async"></a>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Bandeau défilant --}}
  <div class="tc-defilant" aria-hidden="true">
    <div class="tc-defilant__piste">
      @foreach (range(1, 2) as $repetition)
        @foreach (['Déposer', 'Réparer', 'Transformer', 'Donner', 'Seconde vie', 'Zéro gaspillage'] as $mot)
          <span>{{ $mot }} <i>✦</i></span>
        @endforeach
      @endforeach
    </div>
  </div>

  {{-- Le cycle TexTileCycle : grille « bento » des quatre gestions, avec leurs vrais chiffres --}}
  @php
    $etapesCycle = [
        ['numero' => '01', 'titre' => 'Déposer', 'texte' => 'Photographiez votre vêtement : notre analyse remplit la fiche pour vous. Après validation, il rejoint le catalogue.', 'chiffre' => $chiffres['vetements'], 'unite' => 'vêtement(s) disponible(s)', 'lien' => route('depot.catalogue.index'), 'image' => 'banner-image-6.jpg', 'taille' => 'grand'],
        ['numero' => '02', 'titre' => 'Réparer', 'texte' => 'Nos ateliers diagnostiquent et estiment la réparation.', 'chiffre' => $chiffres['ateliers'], 'unite' => 'atelier(s) partenaire(s)', 'lien' => route('ateliers.index'), 'image' => 'post-image7.jpg', 'taille' => 'large'],
        ['numero' => '03', 'titre' => 'Transformer', 'texte' => 'Il devient un sac, un coussin ou une création unique.', 'chiffre' => $chiffres['projets'], 'unite' => 'projet(s) d\'upcycling', 'lien' => route('upcycling.index'), 'image' => 'cat-item3.jpg', 'taille' => 'normal'],
        ['numero' => '04', 'titre' => 'Donner', 'texte' => 'Il habille celles et ceux qui en ont besoin.', 'chiffre' => $chiffres['associations'], 'unite' => 'association(s) partenaire(s)', 'lien' => route('dons.index'), 'image' => 'insta-item5.jpg', 'taille' => 'normal'],
    ];
  @endphp

  <section id="concept" class="tc-page py-5 my-md-4">
    <div class="container py-md-4">
      <div class="d-flex flex-wrap justify-content-between align-items-end gap-4 mb-5" data-revele>
        <div>
          <div class="tc-surtitre">Le cycle TexTileCycle</div>
          <h2 class="section-title mt-2 mb-0" style="font-size: clamp(2.2rem, 4vw, 3.4rem);">Quatre chemins pour une seconde vie</h2>
        </div>
        <p class="mb-0" style="max-width: 420px;">Chaque vêtement trouve sa meilleure destination. Les quatre gestions de la plateforme sont reliées : un vêtement déposé peut être réparé, transformé ou donné.</p>
      </div>

      <div class="tc-bento">
        @foreach ($etapesCycle as $etape)
          <a href="{{ $etape['lien'] }}" class="tc-bento__case tc-bento__case--{{ $etape['taille'] }}" data-revele>
            <img src="{{ asset('front/images/'.$etape['image']) }}" alt="" loading="lazy" decoding="async">
            <div class="tc-bento__haut">
              <span class="tc-bento__numero">{{ $etape['numero'] }}</span>
              <span class="tc-bento__fleche"><x-ui.icone nom="fleche" /></span>
            </div>
            <h3>{{ $etape['titre'] }}</h3>
            <p>{{ $etape['texte'] }}</p>
            <div class="tc-bento__chiffre"><strong data-compteur="{{ $etape['chiffre'] }}">{{ $etape['chiffre'] }}</strong> {{ $etape['unite'] }}</div>
          </a>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Impact écologique en grands chiffres --}}
  @if ($chiffres['impact']['vetements'] > 0)
    <section class="tc-page pb-5">
      <div class="container">
        <div class="tc-impact" data-revele>
          <div class="row g-4 align-items-end mb-5 position-relative">
            <div class="col-lg-7">
              <div class="tc-surtitre">Notre impact, en temps réel</div>
              <h2 class="mt-2 mb-0">Chaque vêtement sauvé compte vraiment.</h2>
            </div>
            <div class="col-lg-5"><p class="mb-0">Estimations calculées à partir des vêtements donnés ou recyclés sur la plateforme, selon leur type.</p></div>
          </div>
          <div class="row g-4 position-relative">
            <div class="col-md-4">
              <div class="tc-impact__valeur" data-compteur="{{ $chiffres['impact']['vetements'] }}">{{ $chiffres['impact']['vetements'] }}</div>
              <div class="tc-impact__libelle">vêtement(s) ont trouvé une seconde vie</div>
            </div>
            <div class="col-md-4">
              <div class="tc-impact__valeur"><span data-compteur="{{ $chiffres['impact']['eau'] }}">{{ number_format($chiffres['impact']['eau'], 0, ',', ' ') }}</span> L</div>
              <div class="tc-impact__libelle">d'eau économisés</div>
            </div>
            <div class="col-md-4">
              <div class="tc-impact__valeur"><span data-compteur="{{ $chiffres['impact']['co2'] }}">{{ number_format($chiffres['impact']['co2'], 0, ',', ' ') }}</span> kg</div>
              <div class="tc-impact__libelle">de CO₂ évités</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  @endif

  {{-- Derniers vêtements du catalogue (données réelles) --}}
  @if ($derniersVetements->isNotEmpty())
    <section id="creations" class="tc-page py-5">
      <div class="container py-md-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
          <div>
            <div class="tc-surtitre">Catalogue solidaire</div>
            <h2 class="section-title mt-2 mb-0">Nouveautés à réserver</h2>
          </div>
          <a href="{{ route('depot.catalogue.index') }}" class="tc-btn tc-btn--clair">Tout le catalogue <x-ui.icone nom="fleche" /></a>
        </div>
        <div class="row g-4">
          @foreach ($derniersVetements as $vetement)
            <div class="col-6 col-md-4 col-lg-3" data-revele>
              <x-depot.carte-vetement :vetement="$vetement" />
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- Engagement --}}
  <section class="collection bg-light position-relative py-5">
    <div class="container">
      <div class="row">
        <div class="title-xlarge text-uppercase txt-fx domino">Seconde vie</div>
        <div class="collection-item d-flex flex-wrap my-5">
          <div class="col-md-6 column-container">
            <div class="image-holder" data-revele="image">
              <img loading="lazy" decoding="async" src="{{ asset('front/images/single-image-2.jpg') }}" alt="Vêtements prêts pour une seconde vie" class="product-image img-fluid">
            </div>
          </div>
          <div class="col-md-6 column-container bg-white">
            <div class="collection-content p-5 m-0 m-md-5" data-revele>
              <h3 class="element-title text-uppercase">Un geste simple pour la planète</h3>
              <p>Chaque année, des tonnes de vêtements finissent à la décharge alors qu'ils pourraient encore être portés.
                Avec TexTileCycle, chaque pièce trouve sa meilleure destination : elle est réparée si elle peut l'être,
                transformée en une création unique si elle est trop abîmée, ou offerte à une association pour habiller
                ceux qui en ont besoin. Moins de déchets, moins de ressources gaspillées, plus de solidarité.</p>
              <a href="{{ $ctaUrl }}" class="btn btn-dark text-uppercase mt-3">Je donne une seconde vie</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Témoignages --}}
  <section class="tc-page py-5 my-md-4">
    <div class="container">
      <div class="text-center mb-5" data-revele>
        <div class="tc-surtitre">Ils nous font confiance</div>
        <h2 class="section-title mt-2">Ce qu'en dit la communauté</h2>
      </div>
      <div class="row g-4">
        @foreach ($testimonials as $testimonial)
          <div class="col-md-6 col-lg-3" data-revele>
            <figure class="tc-temoignage">
              <span class="tc-temoignage__guillemet" aria-hidden="true">“</span>
              <blockquote>{{ trim($testimonial['quote'], '«» ') }}</blockquote>
              <figcaption>
                <span class="tc-temoignage__avatar" aria-hidden="true">{{ mb_substr($testimonial['author'], 0, 1) }}</span>
                {{ $testimonial['author'] }}
              </figcaption>
            </figure>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Associations partenaires --}}
  <section id="associations" class="logo-bar py-5 my-5">
    <div class="container">
      <div class="text-center mb-5">
        <h3 class="section-title text-uppercase">Nos associations partenaires</h3>
        <p>Les vêtements en bon état sont redistribués à des associations locales engagées.</p>
      </div>
      @if ($associations->isNotEmpty())
        <div class="tc-page d-flex flex-wrap justify-content-center gap-3">
          @foreach ($associations as $association)
            <a href="{{ route('dons.associations.show', $association) }}" class="tc-carte tc-association" data-revele>
              <strong>{{ $association->nom }}</strong>
              <span>{{ $association->ville }}</span>
            </a>
          @endforeach
        </div>
      @else
        <div class="row">
          <div class="logo-content d-flex flex-wrap justify-content-between">
            @foreach (range(1, 5) as $logo)
              <img loading="lazy" decoding="async" src="{{ asset('front/images/logo'.$logo.'.png') }}" alt="Association partenaire" class="logo-image img-fluid">
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </section>

  {{-- Appel à l'action final --}}
  <section class="tc-page py-5">
    <div class="container">
      <div class="tc-cta" data-revele>
        <div class="tc-surtitre" style="color: var(--tc-foret);">Rejoignez le cycle</div>
        <h2 class="mt-2 mb-3">Un vêtement que vous ne portez plus ? Offrez-lui une seconde vie.</h2>
        <p class="mb-4" style="max-width: 520px;">Déposez-le en deux minutes : notre analyse de photo remplit la fiche, l'équipe la valide, et il trouve preneur.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="{{ route('depot.mes-depots.create') }}" class="tc-btn tc-btn--magnetique"><x-ui.icone nom="plus" /> Déposer un vêtement</a>
          @guest
            <a href="{{ route('register') }}" class="tc-btn tc-btn--clair tc-btn--magnetique">Créer un compte</a>
          @endguest
        </div>
        <img src="{{ asset('front/images/product-item-6.jpg') }}" alt="" loading="lazy" decoding="async">
      </div>
    </div>
  </section>

  {{-- Instagram --}}
  <section class="instagram position-relative">
    <div class="d-flex justify-content-center w-100 position-absolute bottom-0 z-1">
      <a href="#" class="btn btn-dark px-5">Suivez-nous sur Instagram</a>
    </div>
    <div class="row g-0">
      @foreach (range(1, 6) as $photo)
        <div class="col-6 col-sm-4 col-md-2">
          <div class="insta-item">
            <a href="#">
              <img loading="lazy" decoding="async" src="{{ asset('front/images/insta-item'.$photo.'.jpg') }}" alt="Instagram TexTileCycle" class="insta-image img-fluid">
            </a>
          </div>
        </div>
      @endforeach
    </div>
  </section>
@endsection
