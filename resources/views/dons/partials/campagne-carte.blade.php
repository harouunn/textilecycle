{{-- Carte d'une campagne avec sa jauge. Paramètres : campagne (avecProgression), afficherAssociation (bool) --}}
@php
  $pourcentage = $campagne->progression();
  $kg = $campagne->kgCollectes();
@endphp
<div class="card h-100 border-0 shadow-sm">
  @if ($campagne->image_url)
    <img src="{{ $campagne->image_url }}" alt="" class="card-img-top object-fit-cover" style="height: 160px">
  @endif
  <div class="card-body d-flex flex-column">
    @if ($afficherAssociation ?? true)
      <a href="{{ route('dons.associations.show', $campagne->association) }}" class="small text-uppercase text-muted">{{ $campagne->association->nom }} · {{ $campagne->association->ville }}</a>
    @endif
    <h5 class="card-title mt-1">{{ $campagne->titre }}</h5>
    <p class="card-text small">{{ \Illuminate\Support\Str::limit($campagne->description, 150) }}</p>

    @if ($campagne->typesCibles())
      <p class="small mb-3">
        @foreach ($campagne->typesCibles() as $type)
          <span class="badge rounded-pill text-bg-light border">{{ $type->label() }}</span>
        @endforeach
      </p>
    @endif

    <div class="mt-auto">
      <div class="d-flex justify-content-between small mb-1">
        <span><strong>{{ number_format($kg, 1, ',', ' ') }} kg</strong> reçus</span>
        <span>objectif {{ number_format($campagne->objectif_kg, 0, ',', ' ') }} kg</span>
      </div>
      <div class="progress mb-2" role="progressbar" aria-label="Progression de la campagne {{ $campagne->titre }}"
        aria-valuenow="{{ min(100, $pourcentage) }}" aria-valuemin="0" aria-valuemax="100" style="height: 10px">
        <div class="progress-bar {{ $pourcentage >= 100 ? 'bg-success' : 'bg-dark' }}" style="width: {{ min(100, $pourcentage) }}%"></div>
      </div>
      <div class="d-flex justify-content-between align-items-center small text-muted mb-3">
        <span>{{ number_format($pourcentage, 0, ',', ' ') }} %</span>
        <span>jusqu'au {{ $campagne->date_fin->format('d/m/Y') }}</span>
      </div>
      <a href="{{ route('dons.create', [$campagne->association, 'campagne' => $campagne->id]) }}" class="btn btn-dark btn-sm rounded-pill">Donner pour cette campagne</a>
    </div>
  </div>
</div>
