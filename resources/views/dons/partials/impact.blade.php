{{-- Chiffres d'impact environnemental (ImpactCalculator). Paramètres : impact, titre (facultatif), estime (bool) --}}
<div class="row g-3 text-center">
  @foreach ([
    ['valeur' => number_format($impact['kg'], 1, ',', ' '), 'unite' => 'kg', 'libelle' => 'de textiles '.(($estime ?? false) ? 'proposés' : 'remis aux associations')],
    ['valeur' => number_format($impact['co2_kg'], 0, ',', ' '), 'unite' => 'kg CO₂', 'libelle' => ($estime ?? false) ? 'évitables (estimation)' : 'évités (estimation)'],
    ['valeur' => number_format($impact['eau_litres'], 0, ',', ' '), 'unite' => 'litres d\'eau', 'libelle' => ($estime ?? false) ? 'économisables (estimation)' : 'économisés (estimation)'],
  ] as $chiffre)
    <div class="col-md-4">
      <div class="p-3 bg-light rounded h-100">
        <div class="fs-3 fw-semibold">{{ $chiffre['valeur'] }} <span class="fs-6 fw-normal">{{ $chiffre['unite'] }}</span></div>
        <div class="small text-muted">{{ $chiffre['libelle'] }}</div>
      </div>
    </div>
  @endforeach
</div>
<p class="small text-muted mt-2 mb-0">Estimation à partir de coefficients moyens par kilo de textile réutilisé.</p>
