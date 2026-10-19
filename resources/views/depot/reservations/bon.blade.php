<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Bon de réservation {{ $reservation->code }}</title>
  <style>
    @page { margin: 22mm 16mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1c1f1d; }
    .marque { font-size: 20pt; letter-spacing: .5pt; }
    .marque span { color: #3d7a4f; }
    .surtitre { font-size: 7.5pt; letter-spacing: 1.5pt; text-transform: uppercase; color: #3d7a4f; margin-top: 14pt; }
    h1 { font-size: 16pt; font-weight: normal; margin: 3pt 0 12pt; }
    table { width: 100%; border-collapse: collapse; }
    .details td { padding: 5pt 0; border-bottom: 0.5pt solid #e6e4de; vertical-align: top; }
    .details td.libelle { width: 38%; color: #8f948f; font-size: 8.5pt; text-transform: uppercase; letter-spacing: .5pt; }
    .code { font-size: 15pt; letter-spacing: 2pt; font-weight: bold; }
    .qr { text-align: center; padding: 10pt; border: 0.5pt solid #e6e4de; }
    .qr img { width: 130pt; height: 130pt; }
    .note { margin-top: 14pt; padding: 8pt 10pt; background: #f6f4ef; font-size: 8.5pt; color: #5d625f; }
    .pied { position: fixed; bottom: -10mm; left: 0; right: 0; text-align: center; font-size: 7.5pt; color: #8f948f; }
  </style>
</head>
<body>
  <div class="marque">TexTile<span>Cycle</span></div>
  <div class="surtitre">Bon de réservation</div>
  <h1>{{ $reservation->vetement->titre }}</h1>

  <table>
    <tr>
      <td style="width: 58%; vertical-align: top; padding-right: 14pt;">
        <table class="details">
          <tr><td class="libelle">Code</td><td class="code">{{ $reservation->code }}</td></tr>
          <tr><td class="libelle">Retrait</td><td>{{ $reservation->date_retrait->locale('fr')->translatedFormat('l j F Y') }}<br>{{ $reservation->creneau->label() }}</td></tr>
          <tr><td class="libelle">Client</td><td>{{ $reservation->user->name }}<br>{{ $reservation->telephone }}</td></tr>
          <tr><td class="libelle">Vêtement</td><td>{{ $reservation->vetement->categorie->nom }} · Taille {{ $reservation->vetement->taille->label() }} · {{ $reservation->vetement->etat->label() }}</td></tr>
          <tr><td class="libelle">Statut</td><td>{{ $reservation->statut->label() }}</td></tr>
        </table>
      </td>
      <td style="width: 42%; vertical-align: top;">
        <div class="qr">
          <img src="{{ $qrCode }}" alt="QR code">
          <div style="font-size: 7.5pt; color: #8f948f; margin-top: 4pt;">À scanner par l'équipe au retrait</div>
        </div>
      </td>
    </tr>
  </table>

  <div class="note">
    Présentez ce bon (imprimé ou sur votre téléphone) au créneau indiqué. Une réservation non retirée à la date prévue
    est annulée automatiquement et le vêtement retourne au catalogue.
  </div>

  <div class="pied">TexTileCycle · Bon généré le {{ now()->format('d/m/Y à H:i') }}</div>
</body>
</html>
