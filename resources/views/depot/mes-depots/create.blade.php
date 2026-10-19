@extends('layouts.front')

@section('title', 'Déposer un vêtement')

@section('content')

  <div class="tc-page">
    <x-ui.hero
      surtitre="Dépôt solidaire"
      titre="Déposer un vêtement"
      sous-titre="Décrivez votre vêtement, ou laissez notre analyse de photo pré-remplir la fiche. Il sera publié après validation par l'équipe."
      :fil="['Catalogue' => route('depot.catalogue.index'), 'Déposer un vêtement' => null]" />

    <section class="py-5">
      <div class="container">
        <div class="row g-5">
          <div class="col-lg-8">
            @include('depot.partials.flash')
            @include('depot.partials.analyse')

            <form method="POST" action="{{ route('depot.mes-depots.store') }}" enctype="multipart/form-data" class="tc-carte tc-carte__corps p-md-5">
              @csrf
              @include('depot.mes-depots._form', ['submitLabel' => 'Déposer mon vêtement', 'analyseRoute' => 'depot.mes-depots.analyser'])
            </form>
          </div>

          <aside class="col-lg-4">
            <div class="tc-carte tc-carte__corps">
              <h2 class="fs-6 text-uppercase mb-3" style="letter-spacing: .08em;">Après votre dépôt</h2>
              <ol class="tc-numeros">
                <li><strong>Validation</strong> L'équipe vérifie la photo et la description.</li>
                <li><strong>Publication</strong> Votre vêtement apparaît dans le catalogue.</li>
                <li><strong>Seconde vie</strong> Un membre le réserve et vient le retirer.</li>
              </ol>
              <hr>
              <p class="small mb-0">Astuce : une photo nette, à la lumière du jour et sur un fond clair, donne le meilleur classement automatique.</p>
            </div>
          </aside>
        </div>
      </div>
    </section>
  </div>
@endsection
