@extends('layouts.front')

@section('title', 'Proposer un projet Upcycling')

@push('styles')
  <link rel="stylesheet" href="{{ asset_v('front/css/upcycling-create.css') }}">
@endpush

@section('content')
@include('upcycling.partials.styles')
@php
  $formErrors = $errors->getBag('projet');
  $formOld = fn ($field, $default = null) => $formErrors->any() ? old($field, $default) : $default;
@endphp
<section class="upcycling-create-page" aria-labelledby="upc-title">
  <div class="container upc-container">
    <header class="upc-hero">
      <div>
        <a href="{{ route('upcycling.mes-projets') }}" class="upc-back"><span aria-hidden="true">←</span> Mes projets</a>
        <p class="upc-eyebrow">Une idée, une seconde vie</p>
        <h1 id="upc-title">Proposer un projet <em>Upcycling</em></h1>
        <p class="upc-intro">Un vêtement oublié, un peu d’imagination et votre savoir-faire. Partagez une transformation qui donnera envie de créer autrement.</p>
      </div>
      <div class="upc-process" aria-label="Les étapes de publication">
        <p>Du premier fil au partage</p>
        <ol><li aria-current="step"><span>1</span> Décrire le projet</li><li><span>2</span> Ajouter les étapes</li><li><span>3</span> Soumettre à validation</li></ol>
      </div>
    </header>
    <div class="upc-messages">@include('upcycling.partials.flash')</div>
    <div class="upc-layout">
      <form method="POST" action="{{ route('upcycling.projets.store') }}" enctype="multipart/form-data" class="tcu-form upc-form" data-upcycling-form>
        @csrf
        <div class="upc-ai-panel">
          <p class="upc-eyebrow"><span aria-hidden="true">✳</span> Assistance IA <span class="upc-optional">Facultatif</span></p>
          @include('upcycling.partials.assistant-projet')
        </div>
        <p class="upc-required-note">Les champs portant le badge <span class="upc-required">Champ obligatoire</span> sont nécessaires pour enregistrer votre projet.</p>
<section id="upc-general" class="upc-section" aria-labelledby="upc-heading-general">
      <div class="upc-section-heading"><span class="upc-number" aria-hidden="true">01</span><div><h2 id="upc-heading-general">Informations générales</h2><p>Un titre qui donne envie de découvrir votre création.</p></div></div>
      <div class="row g-3">
  <div class="col-12">
    <label for="titre" class="form-label">Titre <span class="upc-required">Champ obligatoire</span></label>
    <input id="titre" aria-describedby="upc-help-titre" type="text" name="titre" value="{{ $formOld('titre', $projet->titre) }}" minlength="5" maxlength="150" required
      @class(['form-control', 'is-invalid' => $formErrors->has('titre')]) aria-invalid="{{ $formErrors->has('titre') ? 'true' : 'false' }}">
    @error('titre', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
    <p id="upc-help-titre" class="upc-help">Exemple : « Un cabas du quotidien à partir d’un vieux jean ».</p>
  </div>


      </div>
    </section>
<section id="upc-transformation" class="upc-section" aria-labelledby="upc-heading-transformation">
      <div class="upc-section-heading"><span class="upc-number" aria-hidden="true">02</span><div><h2 id="upc-heading-transformation">Transformation souhaitée</h2><p>Racontez le passage d’une pièce oubliée à un nouvel objet utile.</p></div></div>
      <div class="row g-3">
  <div class="col-md-6">
    <label for="vetement_origine" class="form-label">Vêtement d'origine <span class="upc-required">Champ obligatoire</span></label>
    <input id="vetement_origine" aria-describedby="upc-help-vetement_origine" type="text" name="vetement_origine" value="{{ $formOld('vetement_origine', $projet->vetement_origine) }}" minlength="3" maxlength="255" placeholder="ex. vieux jean" required
      @class(['form-control', 'is-invalid' => $formErrors->has('vetement_origine')]) aria-invalid="{{ $formErrors->has('vetement_origine') ? 'true' : 'false' }}">
    @error('vetement_origine', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
    <p id="upc-help-vetement_origine" class="upc-help">Quelle pièce allez-vous transformer ? Précisez le vêtement et sa matière.</p>
  </div>

  <div class="col-md-6">
    <label for="resultat" class="form-label">Résultat <span class="upc-required">Champ obligatoire</span></label>
    <input id="resultat" aria-describedby="upc-help-resultat" type="text" name="resultat" value="{{ $formOld('resultat', $projet->resultat) }}" minlength="3" maxlength="255" placeholder="ex. sac cabas" required
      @class(['form-control', 'is-invalid' => $formErrors->has('resultat')]) aria-invalid="{{ $formErrors->has('resultat') ? 'true' : 'false' }}">
    @error('resultat', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
    <p id="upc-help-resultat" class="upc-help">Exemple : un sac cabas, un coussin ou une pochette à emporter partout.</p>
  </div>


      </div>
    </section>
<section id="upc-difficulte" class="upc-section" aria-labelledby="upc-heading-difficulte">
      <div class="upc-section-heading"><span class="upc-number" aria-hidden="true">03</span><div><h2 id="upc-heading-difficulte">Difficulté et durée</h2><p>Aidez la communauté à se lancer avec les bons repères.</p></div></div>
      <div class="row g-3">
  <div class="col-md-6">
    <label for="difficulte" class="form-label">Difficulté <span class="upc-required">Champ obligatoire</span></label>
    <select id="difficulte" name="difficulte" required @class(['form-select', 'is-invalid' => $formErrors->has('difficulte')]) aria-invalid="{{ $formErrors->has('difficulte') ? 'true' : 'false' }}">
      <option value="">— Choisir —</option>
      @foreach ($difficultes as $valeur => $libelle)
        <option value="{{ $valeur }}" @selected($formOld('difficulte', $projet->difficulte?->value) === $valeur)>{{ $libelle }}</option>
      @endforeach
    </select>
    @error('difficulte', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="duree_minutes" class="form-label">Durée (minutes) <span class="upc-required">Champ obligatoire</span></label>
    <input id="duree_minutes" type="number" name="duree_minutes" value="{{ $formOld('duree_minutes', $projet->duree_minutes) }}" min="5" max="1440" step="1" required
      @class(['form-control', 'is-invalid' => $formErrors->has('duree_minutes')]) aria-invalid="{{ $formErrors->has('duree_minutes') ? 'true' : 'false' }}">
    <div class="form-text">Entre 5 et 1440 minutes (24 h).</div>
    @error('duree_minutes', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
  </div>


      </div>
    </section>
<section id="upc-description" class="upc-section" aria-labelledby="upc-heading-description">
      <div class="upc-section-heading"><span class="upc-number" aria-hidden="true">04</span><div><h2 id="upc-heading-description">Description et matériel</h2><p>Les bonnes idées se partagent avec des explications claires.</p></div></div>
      <div class="row g-3">
  <div class="col-12">
    <label for="description" class="form-label">Description <span class="upc-required">Champ obligatoire</span></label>
    <textarea id="description" aria-describedby="upc-help-description" name="description" rows="5" minlength="20" maxlength="5000" required
      @class(['form-control', 'is-invalid' => $formErrors->has('description')]) aria-invalid="{{ $formErrors->has('description') ? 'true' : 'false' }}">{{ $formOld('description', $projet->description) }}</textarea>
    @error('description', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
    <p id="upc-help-description" class="upc-help">Présentez votre idée, son utilité et les grandes lignes de la transformation.</p>
  </div>

  <div class="col-12">
    <label for="materiel_necessaire" class="form-label">Matériel nécessaire <span class="upc-required">Champ obligatoire</span></label>
    <textarea id="materiel_necessaire" aria-describedby="upc-help-materiel_necessaire" name="materiel_necessaire" rows="4" minlength="3" maxlength="2000" required
      @class(['form-control', 'is-invalid' => $formErrors->has('materiel_necessaire')]) aria-invalid="{{ $formErrors->has('materiel_necessaire') ? 'true' : 'false' }}">{{ $formOld('materiel_necessaire', $projet->materiel_necessaire) }}</textarea>
    <div class="form-text">Un élément par ligne.</div>
    @error('materiel_necessaire', 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
    <p id="upc-help-materiel_necessaire" class="upc-help">Exemple, un élément par ligne : ciseaux de couture, fil assorti, aiguille.</p>
  </div>


      </div>
    </section>
<section id="upc-photos" class="upc-section" aria-labelledby="upc-heading-photos">
      <div class="upc-section-heading"><span class="upc-number" aria-hidden="true">05</span><div><h2 id="upc-heading-photos">Photos avant/après</h2><p>Deux images pour raconter la seconde vie de votre vêtement.</p></div></div>
      <div class="row g-3">
  @foreach (['photo_avant' => ['Photo « avant »', $projet->photoAvantUrl()], 'photo_apres' => ['Photo « après »', $projet->photoApresUrl()]] as $champ => [$libelle, $url])
    <div class="col-md-6"><div class="upc-photo-zone">
      <label for="{{ $champ }}" class="form-label">{{ $libelle }} <span class="upc-required">Champ obligatoire</span></label>
      <div class="upc-photo-visual" aria-hidden="true"><span>{{ $loop->first ? '01' : '02' }}</span><p>{{ $loop->first ? 'Le vêtement de départ' : 'La création finale' }}</p><small>{{ $loop->first ? 'Montrez la pièce avant sa transformation.' : 'Mettez en valeur le résultat obtenu.' }}</small></div>
      @if ($url)
        <div class="mb-2"><img src="{{ $url }}" alt="{{ $libelle }} actuelle" class="tcu-thumb"></div>
      @endif
      <input id="{{ $champ }}" aria-describedby="upc-help-{{ $champ }}" type="file" name="{{ $champ }}" accept="image/jpeg,image/png,image/webp" @required(! \App\Http\Requests\Upcycling\UpcyclingRequest::photoExistante($projet, $champ))
        @class(['form-control', 'is-invalid' => $formErrors->has($champ)]) aria-invalid="{{ $formErrors->has($champ) ? 'true' : 'false' }}">
      <div id="upc-help-{{ $champ }}" class="form-text">JPG, PNG ou WEBP, 2 Mo maximum. Après une erreur, sélectionnez à nouveau les fichiers à envoyer.{{ \App\Http\Requests\Upcycling\UpcyclingRequest::photoExistante($projet, $champ) ? ' Sans remplacement, la photo actuelle est conservée.' : ' Sélectionnez une image.' }}</div>
      @error($champ, 'projet') <div data-server-error class="invalid-feedback">{{ $message }}</div> @enderror
    </div></div>
  @endforeach
      </div>
    </section>
        <div class="upc-save-panel">
          <div><h2>Votre idée est prête à prendre forme ?</h2><p>Enregistrez votre brouillon. Vous pourrez ensuite détailler les étapes et soumettre votre projet à validation.</p></div>
          <div class="upc-save-actions">
            <button type="submit" class="upc-button upc-button-primary">Enregistrer et ajouter les étapes <span aria-hidden="true">→</span></button>
            <a href="{{ route('upcycling.mes-projets') }}" class="upc-button upc-button-secondary">Annuler</a>
          </div>
        </div>
        @include('upcycling.partials.validation-browser')
      </form>
      <aside class="upc-aside" aria-label="Conseils pour proposer un projet">
        <div class="upc-aside-card"><p class="upc-eyebrow">Votre fil conducteur</p><h2>Une création bien racontée.</h2><nav aria-label="Sections du formulaire"><a href="#upc-general"><span>01</span> Informations générales</a><a href="#upc-transformation"><span>02</span> Transformation souhaitée</a><a href="#upc-difficulte"><span>03</span> Difficulté et durée</a><a href="#upc-description"><span>04</span> Description et matériel</a><a href="#upc-photos"><span>05</span> Photos avant/après</a></nav></div>
        <div class="upc-aside-card upc-aside-tip"><span class="upc-tip-icon" aria-hidden="true">✳</span><h2>Les détails font la différence.</h2><p>Choisissez un titre précis, estimez le temps nécessaire et indiquez le matériel utile. Un tutoriel clair aide chacun à oser se lancer.</p></div>
        <div class="upc-publication-note"><strong>Une publication accompagnée</strong><p>Votre projet est d’abord enregistré en brouillon. Seuls les projets soumis puis validés par l’équipe apparaissent dans la galerie.</p></div>
      </aside>
    </div>
  </div>
</section>
@endsection
