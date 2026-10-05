@extends('layouts.front')

@section('title', 'Donnez une seconde vie à vos vêtements')
@section('body_class', 'homepage')

@section('content')
@php
  $ctaUrl = auth()->check() ? route('admin.dashboard') : route('register');

  $slides = [
    ['image' => 'banner-image-6.jpg', 'title' => 'Déposez vos vêtements', 'text' => 'Confiez-nous les vêtements que vous ne portez plus, en point de collecte ou en ligne.'],
    ['image' => 'banner-image-1.jpg', 'title' => 'Réparation', 'text' => 'Un bouton, une couture, une fermeture : nos couturiers redonnent vie à vos pièces.'],
    ['image' => 'banner-image-2.jpg', 'title' => 'Upcycling', 'text' => 'Les textiles abîmés deviennent des créations uniques et durables.'],
    ['image' => 'banner-image-3.jpg', 'title' => 'Don aux associations', 'text' => 'Les vêtements en bon état sont redistribués à nos associations partenaires.'],
    ['image' => 'banner-image-4.jpg', 'title' => 'Mode responsable', 'text' => 'Chaque vêtement réutilisé, c\'est de l\'eau, de l\'énergie et des déchets économisés.'],
    ['image' => 'banner-image-5.jpg', 'title' => 'Une démarche solidaire', 'text' => 'Rejoignez une communauté engagée pour une mode plus juste et plus durable.'],
  ];

  $features = [
    ['icon' => 'shopping-bag', 'title' => 'Dépôt simplifié', 'text' => 'Déposez vos vêtements en quelques clics ou dans l\'un de nos points de collecte.'],
    ['icon' => 'arrow-cycle', 'title' => 'Réparation', 'text' => 'Nos couturiers partenaires réparent les pièces abîmées pour prolonger leur vie.'],
    ['icon' => 'gift', 'title' => 'Upcycling', 'text' => 'Les textiles non réparables sont transformés en nouvelles créations originales.'],
    ['icon' => 'heart', 'title' => 'Dons solidaires', 'text' => 'Les vêtements en bon état sont offerts aux associations de votre région.'],
  ];

  $services = [
    ['image' => 'cat-item1.jpg', 'label' => 'Déposer mes vêtements'],
    ['image' => 'cat-item2.jpg', 'label' => 'Faire réparer'],
    ['image' => 'cat-item3.jpg', 'label' => 'Donner à une association'],
  ];

  $creations = [
    ['image' => 'product-item-1.jpg', 'title' => 'Robe fleurie revisitée'],
    ['image' => 'product-item-2.jpg', 'title' => 'Chemise patchwork'],
    ['image' => 'product-item-3.jpg', 'title' => 'Chemise en coton réparée'],
    ['image' => 'product-item-4.jpg', 'title' => 'Pull court retravaillé'],
    ['image' => 'product-item-10.jpg', 'title' => 'Pull recousu main'],
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
                    <a href="#services">
                      <img src="{{ asset('front/images/'.$slide['image']) }}" alt="{{ $slide['title'] }}" class="img-fluid">
                    </a>
                  </div>
                  <div class="banner-content py-4">
                    <h5 class="element-title text-uppercase">
                      <a href="#services" class="item-anchor">{{ $slide['title'] }}</a>
                    </h5>
                    <p>{{ $slide['text'] }}</p>
                    <div class="btn-left">
                      <a href="#services" class="btn-link fs-6 text-uppercase item-anchor text-decoration-none">En savoir plus</a>
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

  {{-- Le concept --}}
  <section id="concept" class="features py-5">
    <div class="container">
      <div class="row">
        @foreach ($features as $feature)
          <div class="col-md-3 text-center" data-aos="fade-in" data-aos-delay="{{ $loop->index * 300 }}">
            <div class="py-5">
              <svg width="38" height="38" viewBox="0 0 24 24">
                <use xlink:href="#{{ $feature['icon'] }}"></use>
              </svg>
              <h4 class="element-title text-capitalize my-3">{{ $feature['title'] }}</h4>
              <p>{{ $feature['text'] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Services --}}
  <section id="services" class="categories overflow-hidden">
    <div class="container">
      <div class="open-up" data-aos="zoom-out">
        <div class="row">
          @foreach ($services as $service)
            <div class="col-md-4">
              <div class="cat-item image-zoom-effect">
                <div class="image-holder">
                  <a href="{{ $ctaUrl }}">
                    <img src="{{ asset('front/images/'.$service['image']) }}" alt="{{ $service['label'] }}" class="product-image img-fluid">
                  </a>
                </div>
                <div class="category-content">
                  <div class="product-button">
                    <a href="{{ $ctaUrl }}" class="btn btn-common text-uppercase">{{ $service['label'] }}</a>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </section>

  {{-- Créations upcyclées --}}
  <section id="creations" class="new-arrival product-carousel py-5 position-relative overflow-hidden">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center mt-5 mb-3">
        <h4 class="text-uppercase">Nos dernières créations upcyclées</h4>
        <a href="#creations" class="btn-link">Bientôt en ligne</a>
      </div>
      <div class="swiper product-swiper open-up" data-aos="zoom-out">
        <div class="swiper-wrapper d-flex">
          @foreach ($creations as $creation)
            <div class="swiper-slide">
              <div class="product-item image-zoom-effect link-effect">
                <div class="image-holder position-relative">
                  <a href="#creations">
                    <img src="{{ asset('front/images/'.$creation['image']) }}" alt="{{ $creation['title'] }}" class="product-image img-fluid">
                  </a>
                  <a href="#creations" class="btn-icon btn-wishlist" aria-label="J'aime">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                      <use xlink:href="#heart"></use>
                    </svg>
                  </a>
                  <div class="product-content">
                    <h5 class="element-title text-uppercase fs-5 mt-3">
                      <a href="#creations">{{ $creation['title'] }}</a>
                    </h5>
                    <a href="#creations" class="text-decoration-none" data-after="Découvrir"><span>Pièce unique</span></a>
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
  </section>

  {{-- Engagement --}}
  <section class="collection bg-light position-relative py-5">
    <div class="container">
      <div class="row">
        <div class="title-xlarge text-uppercase txt-fx domino">Seconde vie</div>
        <div class="collection-item d-flex flex-wrap my-5">
          <div class="col-md-6 column-container">
            <div class="image-holder">
              <img src="{{ asset('front/images/single-image-2.jpg') }}" alt="Vêtements prêts pour une seconde vie" class="product-image img-fluid">
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
      <div class="row">
        <div class="logo-content d-flex flex-wrap justify-content-between">
          @foreach (range(1, 5) as $logo)
            <img src="{{ asset('front/images/logo'.$logo.'.png') }}" alt="Association partenaire" class="logo-image img-fluid">
          @endforeach
        </div>
      </div>
    </div>
  </section>

  {{-- Newsletter --}}
  <section class="newsletter bg-light" style="background: url({{ asset('front/images/pattern-bg.png') }}) no-repeat;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 py-5 my-5">
          <div class="subscribe-header text-center pb-3">
            <h3 class="section-title text-uppercase">Restez informé de nos actions</h3>
          </div>
          <form id="form" class="d-flex flex-wrap gap-2" onsubmit="return false;">
            <input type="email" name="email" placeholder="Votre adresse e-mail" class="form-control form-control-lg">
            <button class="btn btn-dark btn-lg text-uppercase w-100">Je m'inscris</button>
          </form>
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
              <img src="{{ asset('front/images/insta-item'.$photo.'.jpg') }}" alt="Instagram TexTileCycle" class="insta-image img-fluid">
            </a>
          </div>
        </div>
      @endforeach
    </div>
  </section>
@endsection
