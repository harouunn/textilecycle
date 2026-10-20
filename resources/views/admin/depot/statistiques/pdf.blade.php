<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Statistiques du dépôt</title>
  @php
    $ind = $rapport['indicateurs'];
    $taux = fn (?int $valeur) => $valeur === null ? '—' : $valeur.' %';
    $nombre = fn (int $valeur) => number_format($valeur, 0, ',', ' ');
  @endphp
  <style>
    @page { margin: 18mm 15mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1c1f1d; }
    .marque { font-size: 18pt; }
    .marque span { color: #3d7a4f; }
    .surtitre { font-size: 7.5pt; letter-spacing: 1.5pt; text-transform: uppercase; color: #3d7a4f; margin-top: 10pt; }
    h1 { font-size: 15pt; font-weight: normal; margin: 2pt 0 4pt; }
    h2 { font-size: 10.5pt; margin: 16pt 0 6pt; padding-bottom: 3pt; border-bottom: 0.5pt solid #e6e4de; }
    .date { color: #8f948f; font-size: 8pt; }
    table { width: 100%; border-collapse: collapse; }
    .kpis td { width: 25%; padding: 8pt; background: #f6f4ef; border: 3pt solid #fff; vertical-align: top; }
    .kpis strong { display: block; font-size: 15pt; color: #3d7a4f; }
    .kpis span { font-size: 7.5pt; color: #5d625f; text-transform: uppercase; letter-spacing: .4pt; }
    .tableau th { text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .4pt; color: #8f948f; padding: 4pt 4pt; border-bottom: 0.5pt solid #e6e4de; }
    .tableau td { padding: 4pt; border-bottom: 0.5pt solid #f0eee9; }
    .droite { text-align: right; }
    .barre { height: 7pt; background: #3d7a4f; }
    .colonnes td.col { width: 50%; vertical-align: top; padding-right: 10pt; }
    .pied { position: fixed; bottom: -8mm; left: 0; right: 0; text-align: center; font-size: 7pt; color: #8f948f; }
  </style>
</head>
<body>
  <div class="marque">TexTile<span>Cycle</span></div>
  <div class="surtitre">Rapport du module Dépôt &amp; catalogue</div>
  <h1>Statistiques du dépôt</h1>
  <div class="date">Généré le {{ $rapport['genere_le']->format('d/m/Y à H:i') }}</div>

  <h2>Indicateurs clés</h2>
  <table class="kpis">
    <tr>
      <td><strong>{{ $nombre($ind['total']) }}</strong><span>Vêtements déposés</span></td>
      <td><strong>{{ $nombre($ind['ce_mois']) }}</strong><span>Déposés ce mois-ci</span></td>
      <td><strong>{{ $nombre($ind['a_valider']) }}</strong><span>À valider</span></td>
      <td><strong>{{ $nombre($ind['en_ligne']) }}</strong><span>En ligne au catalogue</span></td>
    </tr>
    <tr>
      <td><strong>{{ $taux($ind['taux_acceptation']) }}</strong><span>Taux d'acceptation</span></td>
      <td><strong>{{ $taux($ind['taux_reussite']) }}</strong><span>Réservations réussies</span></td>
      <td><strong>{{ $taux($ind['taux_expiration']) }}</strong><span>Annulations par expiration</span></td>
      <td><strong>{{ $nombre($rapport['reservations']['total']) }}</strong><span>Réservations</span></td>
    </tr>
  </table>

  <h2>Activité des 6 derniers mois</h2>
  <table class="tableau">
    <tr><th>Mois</th><th class="droite">Dépôts</th><th class="droite">Réservations</th></tr>
    @foreach ($rapport['par_mois'] as $mois)
      <tr><td>{{ $mois['mois'] }}</td><td class="droite">{{ $mois['depots'] }}</td><td class="droite">{{ $mois['reservations'] }}</td></tr>
    @endforeach
  </table>

  <h2>Catégories</h2>
  <table class="tableau">
    <tr><th>Catégorie</th><th class="droite">Déposés</th><th class="droite">Disponibles</th><th class="droite">Réservés</th><th class="droite">Donnés</th></tr>
    @foreach ($rapport['par_categorie'] as $categorie)
      <tr>
        <td>{{ $categorie->nom }}</td>
        <td class="droite"><strong>{{ $categorie->vetements_count }}</strong></td>
        <td class="droite">{{ $categorie->disponibles_count }}</td>
        <td class="droite">{{ $categorie->reserves_count }}</td>
        <td class="droite">{{ $categorie->donnes_count }}</td>
      </tr>
    @endforeach
  </table>

  <table class="colonnes"><tr>
    @foreach ([['Par état', $rapport['par_etat']], ['Réservations par statut', [
        'En attente' => $rapport['reservations']['en_attente'], 'Confirmées' => $rapport['reservations']['confirmee'],
        'Retirées' => $rapport['reservations']['retiree'], 'Annulées' => $rapport['reservations']['annulee'],
    ]]] as [$titre, $lignes])
      @php $max = max(1, max($lignes)); @endphp
      <td class="col">
        <h2>{{ $titre }}</h2>
        <table class="tableau">
          @foreach ($lignes as $libelle => $valeur)
            <tr>
              <td style="width: 40%">{{ $libelle }}</td>
              <td><div class="barre" style="width: {{ max(1, 100 * $valeur / $max) }}%"></div></td>
              <td class="droite" style="width: 12%">{{ $valeur }}</td>
            </tr>
          @endforeach
        </table>
      </td>
    @endforeach
  </tr></table>

  <h2>Seconde vie et impact écologique</h2>
  <table class="tableau">
    @foreach ($rapport['orientation'] as $libelle => $valeur)
      <tr><td>{{ $libelle }}</td><td class="droite">{{ $valeur }}</td></tr>
    @endforeach
    <tr><td><strong>Eau économisée</strong></td><td class="droite"><strong>{{ $nombre($rapport['impact']['eau']) }} L</strong></td></tr>
    <tr><td><strong>CO₂ évité</strong></td><td class="droite"><strong>{{ $nombre($rapport['impact']['co2']) }} kg</strong></td></tr>
  </table>

  <h2>Meilleurs déposants</h2>
  <table class="tableau">
    <tr><th>#</th><th>Membre</th><th class="droite">Vêtements déposés</th></tr>
    @forelse ($rapport['top_deposants'] as $deposant)
      <tr><td>{{ $loop->iteration }}</td><td>{{ $deposant->name }}</td><td class="droite">{{ $deposant->vetements_count }}</td></tr>
    @empty
      <tr><td colspan="3">Aucun dépôt pour le moment.</td></tr>
    @endforelse
  </table>

  <div class="pied">TexTileCycle · Module Dépôt &amp; catalogue · Rapport généré automatiquement</div>
</body>
</html>
