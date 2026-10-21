{{-- Reçu PDF d'un don (dompdf : CSS simple, pas de flexbox). Paramètres : don, dateReception, url, qr, impact --}}
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Reçu {{ $don->reference }}</title>
  <style>
    @page { margin: 32px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
    h1 { font-size: 20px; margin: 0 0 4px; }
    .muted { color: #666; }
    .entete { border-bottom: 2px solid #222; padding-bottom: 12px; margin-bottom: 18px; }
    .entete td { vertical-align: top; }
    .marque { font-size: 18px; font-weight: bold; letter-spacing: 1px; }
    table { width: 100%; border-collapse: collapse; }
    .details th { text-align: left; width: 34%; padding: 6px 8px; background: #f2f2f2; border-bottom: 1px solid #ddd; }
    .details td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
    .bloc { margin-top: 18px; }
    .impact td { width: 33%; text-align: center; padding: 10px; background: #f7f7f7; border: 4px solid #fff; }
    .impact strong { display: block; font-size: 15px; }
    .pied { margin-top: 28px; font-size: 10px; }
  </style>
</head>
<body>
  <table class="entete">
    <tr>
      <td>
        <div class="marque">TEXTILECYCLE</div>
        <h1>Reçu de don</h1>
        <div class="muted">Référence <strong>{{ $don->reference }}</strong></div>
      </td>
      <td style="text-align: right; width: 170px">
        <img src="{{ $qr }}" width="140" height="140" alt="QR code de vérification">
        <div class="muted" style="font-size: 9px">Scannez pour vérifier ce reçu</div>
      </td>
    </tr>
  </table>

  <table class="details">
    <tr><th>Donateur</th><td>{{ $don->user->name }}</td></tr>
    <tr><th>Association bénéficiaire</th><td>{{ $don->association->nom }} — {{ $don->association->adresse }}, {{ $don->association->ville }}</td></tr>
    @if ($don->campagne)
      <tr><th>Campagne</th><td>{{ $don->campagne->titre }}</td></tr>
    @endif
    <tr><th>Articles</th><td>{{ $don->quantite }} × {{ $don->typeLabel() }} ({{ $don->etatLabel() }})</td></tr>
    <tr><th>Poids</th><td>{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : 'Non renseigné' }}</td></tr>
    <tr><th>Mode de remise</th><td>{{ $don->modeLabel() }}</td></tr>
    <tr><th>Date de remise</th><td>{{ $don->date_remise->format('d/m/Y') }}</td></tr>
    <tr><th>Reçu par l'association le</th><td>{{ $dateReception->format('d/m/Y') }}</td></tr>
  </table>

  @if ($don->poids_kg)
    <div class="bloc">
      <strong>Impact environnemental estimé</strong>
      <table class="impact">
        <tr>
          <td><strong>{{ number_format($impact['kg'], 1, ',', ' ') }} kg</strong> de textiles réutilisés</td>
          <td><strong>{{ number_format($impact['co2_kg'], 0, ',', ' ') }} kg</strong> de CO₂ évités</td>
          <td><strong>{{ number_format($impact['eau_litres'], 0, ',', ' ') }} L</strong> d'eau économisés</td>
        </tr>
      </table>
    </div>
  @endif

  <p class="pied muted">
    Ce reçu atteste la remise de textiles à l'association indiquée. Il ne constitue pas un reçu fiscal.<br>
    Vérification : {{ $url }}
  </p>
</body>
</html>
