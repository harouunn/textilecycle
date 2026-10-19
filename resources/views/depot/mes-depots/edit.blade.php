@extends('layouts.front')

@section('title', 'Modifier mon dépôt')

@section('content')

  <div class="tc-page">
    <x-ui.hero
      surtitre="Mes dépôts"
      :titre="'Modifier « '.$vetement->titre.' »'"
      sous-titre="Après modification, votre vêtement sera de nouveau vérifié par l'équipe avant sa publication."
      :fil="['Mes dépôts' => route('depot.mes-depots.index'), 'Modifier' => null]" />

    <section class="py-5">
      <div class="container" style="max-width: 960px;">
        @include('depot.partials.flash')

        <form method="POST" action="{{ route('depot.mes-depots.update', $vetement) }}" enctype="multipart/form-data" class="tc-carte tc-carte__corps p-md-5">
          @csrf
          @method('PUT')
          @include('depot.mes-depots._form', ['submitLabel' => 'Enregistrer'])
        </form>
      </div>
    </section>
  </div>
@endsection
