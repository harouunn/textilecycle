@extends('layouts.front')

@section('title', 'Donnez une seconde vie à vos vêtements')
@section('body_class', 'homepage')

@section('content')
@php
  $ctaUrl = auth()->check() ? auth()->user()->accueil() : route('register');

  $slides = [
    ['image' => 'banner-image-6.jpg', 'title' => 'Déposez vos vêtements', 'text' => 'Confiez-nous les vêtements que vous ne portez plus, en point de collecte ou en ligne.'],
    ['image' => 'banner-image-1.jpg', 'title' => 'Réparation', 'text' => 'Un bouton, une couture, une fermeture : nos couturiers redonnent vie à vos pièces.'],
    ['image' => 'banner-image-2.jpg', 'title' => 'Upcycling', 'text' => 'Les textiles abîmés deviennent des créations uniques et durables.'],
    ['image' => 'banner-image-3.jpg', 'title' => 'Don aux associations', 'text' => 'Les vêtements en bon état sont redistribués à nos associations partenaires.'],
    ['image' => 'banner-image-4.jpg', 'title' => 'Mode responsable', 'text' => 'Chaque vêtement réutilisé, c\'est de l\'eau, de l\'énergie et des déchets économisés.'],
    ['image' => 'banner-image-5.jpg', 'title' => 'Une démarche solidaire', 'text' => 'Rejoignez une communauté engagée pour une mode plus juste et plus durable.'],
  ];

  $testimonials = [
    ['quote' => '« J\'ai déposé trois sacs de vêtements en dix minutes. Savoir qu\'ils vont servir à quelqu\'un, ça n\'a pas de prix. »', 'author' => 'Sarah, donatrice'],
    ['quote' => '« Ma veste préférée a été réparée comme neuve. Je ne pensais pas qu\'on pouvait la sauver ! »', 'author' => 'Karim, utilisateur'],
    ['quote' => '« Grâce à TexTileCycle, nous recevons chaque mois des vêtements triés et en bon état pour les familles. »', 'author' => 'Association partenaire'],
    ['quote' => '« L\'upcycling m\'a permis de transformer de vieux jeans en un sac unique. Bravo pour l\'initiative. »', 'author' => 'Léa, créatrice'],
  ];
@endphp

  {{-- Bannière --}}
  <section id="billboard" class="bg-light py-5">
    <div class="container">
      <div class="row justify-content-center">
        <h1 class="section-title text-center mt-4" data-aos="fade-up">Donnez une seconde vie à vos vêtements</h1>
        <div class="col-md-6 text-center" data-aos="fade-up" data-aos-delay="300">
          <p>TexTileCycle collecte vos vêtements, les répare, les transforme ou les offre aux associations. Une plateforme
            simple pour réduire le gaspillage textile et soutenir la solidarité près de chez vous.</p>
        </div>
      </div>
      <div class="row">
        <div class="swiper main-swiper py-4" data-aos="fade-up" data-aos-delay="600">
          <div class="swiper-wrapper d-flex border-animation-left">
            @foreach ($slides as $slide)
              <div class="swiper-slide">
                <div class="banner-item image-zoom-effect">
                  <div class="image-holder">
                    <a href="#concept">
                      <img loading="lazy" decoding="async" src="{{ asset('front/images/'.$slide['image']) }}" alt="{{ $slide['title'] }}" class="img-fluid">
                    </a>
                  </div>
                  <div class="banner-content py-4">
                    <h5 class="element-title text-uppercase">
                      <a href="#concept" class="item-anchor">{{ $slide['title'] }}</a>
                    </h5>
                    <p>{{ $slide['text'] }}</p>
                    <div class="btn-left">
                      <a href="#concept" class="btn-link fs-6 text-uppercase item-anchor text-decoration-none">En savoir plus</a>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          <div class="swiper-pagination"></div>
        </div>
        <div class="icon-arrow icon-arrow-left"><svg width="50" height="50" viewBox="0 0 24 24">
            <use xlink:href="#arrow-left"></use>
          </svg></div>
        <div class="icon-arrow icon-arrow-right"><svg width="50" height="50" viewBox="0 0 24 24">
            <use xlink:href="#arrow-right"></use>
          </svg></div>
      </div>
    </div>
  </section>

  {{-- Le cycle TexTileCycle : les quatre gestions, avec leurs vrais chiffres --}}
  @php
    $etapesCycle = [
        ['numero' => '01', 'titre' => 'Déposer', 'texte' => 'Photographiez votre vêtement : notre analyse remplit la fiche pour vous. Après validation, il rejoint le catalogue.', 'chiffre' => $chiffres['vetements'], 'unite' => 'vêtement(s) disponible(s)', 'lien' => route('depot.catalogue.index'), 'action' => 'Voir le catalogue', 'icone' => 'cintre'],
        ['numero' => '02', 'titre' => 'Réparer', 'texte' => 'Un trou, une fermeture, un ourlet ? Nos ateliers partenaires diagnostiquent et estiment la réparation.', 'chiffre' => $chiffres['ateliers'], 'unite' => 'atelier(s) partenaire(s)', 'lien' => route('ateliers.index'), 'action' => 'Trouver un atelier', 'icone' => 'cle'],
        ['numero' => '03', 'titre' => 'Transformer', 'texte' => 'Trop abîmé pour être porté ? Il devient un sac, un coussin ou une création unique grâce à l\'upcycling.', 'chiffre' => $chiffres['projets'], 'unite' => 'projet(s) d\'upcycling', 'lien' => route('upcycling.index'), 'action' => 'Voir les créations', 'icone' => 'etincelle'],
        ['numero' => '04', 'titre' => 'Donner', 'texte' => 'Les vêtements en bon état habillent celles et ceux qui en ont besoin, via nos associations partenaires.', 'chiffre' => $chiffres['associations'], 'unite' => 'association(s) partenaire(s)', 'lien' => route('dons.index'), 'action' => 'Faire un don', 'icone' => 'feuille'],
    ];
  @endphp

  <section id="concept" class="tc-page py-5">
    <div class="container py-md-4">
      <div class="text-center mb-5">
        <div class="tc-surtitre">Le cycle TexTileCycle</div>
        <h2 class="section-title mt-2">Quatre chemins pour une seconde vie</h2>
        <p class="mx-auto" style="max-width: 640px;">Chaque vêtement trouve sa meilleure destination. Les quatre gestions de la plateforme sont reliées : un vêtement déposé peut être réparé, transformé ou donné.</p>
      </div>
      <div class="row g-4">
        @foreach ($etapesCycle as $etape)
          <div class="col-md-6 col-lg-3">
            <a href="{{ $etape['lien'] }}" class="tc-cycle-carte">
              <div class="d-flex justify-content-between align-items-start mb-4">
                <span class="tc-cycle-carte__numero">{{ $etape['numero'] }}</span>
                <span class="tc-stat__icone"><x-ui.icone :nom="$etape['icone']" /></span>
              </div>
              <h3>{{ $etape['titre'] }}</h3>
              <p>{{ $etape['texte'] }}</p>
              <div class="tc-cycle-carte__chiffre"><strong>{{ $etape['chiffre'] }}</strong> {{ $etape['unite'] }}</div>
              <span class="tc-produit__cta mt-3 d-inline-block">{{ $etape['action'] }}</span>
            </a>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  @if ($chiffres['impact']['vetements'] > 0)
    <div class="tc-bandeau-impact">
      <div class="container d-flex flex-wrap justify-content-center gap-4 gap-md-5">
        <span><x-ui.icone nom="cintre" /> <strong>{{ $chiffres['impact']['vetements'] }}</strong> vêtement(s) ont trouvé une seconde vie</span>
        <span><x-ui.icone nom="feuille" /> <strong>{{ \App\Services\Depot\ImpactEcologique::formaterEau($chiffres['impact']['eau']) }}</strong> d'eau économisés</span>
        <span><x-ui.icone nom="etincelle" /> <strong>{{ number_format($chiffres['impact']['co2'], 0, ',', ' ') }} kg</strong> de CO₂ évités</span>
      </div>
    </div>
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
            <div class="col-6 col-md-4 col-lg-3">
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
            <div class="image-holder">
              <img loading="lazy" decoding="async" src="{{ asset('front/images/single-image-2.jpg') }}" alt="Vêtements prêts pour une seconde vie" class="product-image img-fluid">
            </div>
          </div>
          <div class="col-md-6 column-container bg-white">
            <div class="collection-content p-5 m-0 m-md-5">
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
  <section class="testimonials py-5 bg-light">
    <div class="section-header text-center mt-5">
      <h3 class="section-title">ILS NOUS FONT CONFIANCE</h3>
    </div>
    <div class="swiper testimonial-swiper overflow-hidden my-5">
      <div class="swiper-wrapper d-flex">
        @foreach ($testimonials as $testimonial)
          <div class="swiper-slide">
            <div class="testimonial-item text-center">
              <blockquote>
                <p>{{ $testimonial['quote'] }}</p>
                <div class="review-title text-uppercase">{{ $testimonial['author'] }}</div>
              </blockquote>
            </div>
          </div>
        @endforeach
      </div>
    </div>
    <div class="testimonial-swiper-pagination d-flex justify-content-center mb-5"></div>
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
            <a href="{{ route('dons.associations.show', $association) }}" class="tc-carte tc-association">
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

  {{-- Appel à l'action --}}
  <section class="newsletter bg-light tc-page" style="background: url({{ asset('front/images/pattern-bg.png') }}) no-repeat;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 py-5 my-5 text-center">
          <div class="tc-surtitre mb-2">Rejoignez le cycle</div>
          <h3 class="section-title text-uppercase mb-3">Un vêtement que vous ne portez plus ?</h3>
          <p class="mb-4">Déposez-le en deux minutes : notre analyse de photo remplit la fiche, l'équipe la valide, et il trouve preneur.</p>
          <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="{{ route('depot.mes-depots.create') }}" class="tc-btn tc-btn--vert"><x-ui.icone nom="plus" /> Déposer un vêtement</a>
            @guest
              <a href="{{ route('register') }}" class="tc-btn tc-btn--clair">Créer un compte</a>
            @endguest
          </div>
        </div>
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
