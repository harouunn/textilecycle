@extends('layouts.front')

@section('title', 'Rapport mensuel — '.$association->nom)

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Espace association" titre="Rapport d'impact mensuel" :sous-titre="$association->nom.' — '.ucfirst($mois->locale('fr')->translatedFormat('F Y'))"
    :fil="['Dons' => route('dons.index'), 'Espace association' => route('dons.espace.index'), 'Rapport' => null]">
    @can('modifierPresentation', $association)
      <a href="{{ route('dons.espace.presentation.edit', $association) }}" class="tc-btn">Présentation</a>
    @endcan
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container" style="max-width: 900px">
    <form method="GET" action="{{ route('dons.espace.rapport', $association) }}" class="d-flex align-items-end gap-2 mb-4">
      <div>
        <label for="mois" class="form-label small text-uppercase mb-1">Mois</label>
        <input type="month" id="mois" name="mois" value="{{ $mois->format('Y-m') }}" max="{{ now()->format('Y-m') }}" class="form-control">
      </div>
      <button type="submit" class="btn btn-outline-dark">Afficher</button>
    </form>

    @if ($stats['dons'] === 0)
      <div class="alert alert-light border" role="status">Aucun don reçu ce mois-ci. Le rapport se remplira dès qu'un don sera marqué « Reçu ».</div>
    @endif

    <div class="row g-3 mb-4 text-center">
      @foreach ([
        ['Dons reçus', number_format($stats['dons'], 0, ',', ' ')],
        ['Textiles reçus', number_format($stats['kg'], 1, ',', ' ').' kg'],
        ['CO₂ évité (estim.)', number_format($stats['co2_kg'], 0, ',', ' ').' kg'],
        ['Eau économisée (estim.)', number_format($stats['eau_litres'], 0, ',', ' ').' L'],
      ] as [$libelle, $valeur])
        <div class="col-6 col-md-3">
          <div class="p-3 bg-light rounded h-100">
            <div class="fs-4 fw-semibold">{{ $valeur }}</div>
            <div class="small text-muted">{{ $libelle }}</div>
          </div>
        </div>
      @endforeach
    </div>
    @if ($stats['types'])
      <p class="small text-muted">Répartition : {{ collect($stats['types'])->map(fn ($n, $type) => "{$type} : {$n}")->join(' · ') }}</p>
    @endif

    <div class="d-flex justify-content-between align-items-end gap-2 mb-1">
      <label for="rapport" class="form-label mb-0">Texte du rapport (à relire, puis copier dans votre newsletter ou vos réseaux)</label>
      <button type="button" class="btn btn-sm btn-outline-dark" data-ia-rediger data-type="rapport" data-cible="rapport"
        data-fixes="{{ json_encode(['association_id' => $association->id, 'mois' => $mois->format('Y-m')]) }}">✨ Générer avec l'IA</button>
    </div>
    <textarea id="rapport" rows="8" class="form-control"></textarea>
    <div data-ia-message="rapport"></div>
    <button type="button" class="btn btn-dark btn-sm mt-2" onclick="navigator.clipboard.writeText(document.getElementById('rapport').value)">Copier le texte</button>
  </div>
</section>

@include('dons.partials.ia-rediger')
@endsection
