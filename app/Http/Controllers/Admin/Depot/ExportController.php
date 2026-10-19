<?php

namespace App\Http\Controllers\Admin\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Vetement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports CSV pour Excel (séparateur « ; » et BOM UTF-8 pour les accents), avec les filtres de la liste.
 * Les lignes sont lues par paquets (lazy) : l'export reste léger même avec beaucoup de données.
 */
class ExportController extends Controller
{
    public function vetements(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'categorie' => ['nullable', 'integer'],
            'etat' => ['nullable', Rule::enum(Etat::class)],
            'statut' => ['nullable', Rule::enum(StatutVetement::class)],
            'moderation' => ['nullable', Rule::enum(Moderation::class)],
        ]);

        $lignes = Vetement::query()->with(['categorie', 'user'])->filter($filters)->latest('date_depot')->lazy(200)
            ->map(fn (Vetement $v) => [
                $v->id, $v->titre, $v->categorie->nom, $v->taille->label(), $v->genre->label(), $v->matiere,
                $v->etat->label(), $v->statut->label(), $v->moderation->label(), $v->user->name, $v->date_depot->format('d/m/Y'),
            ]);

        return $this->csv('vetements', ['N°', 'Titre', 'Catégorie', 'Taille', 'Genre', 'Matière', 'État', 'Statut', 'Validation', 'Déposant', 'Déposé le'], $lignes);
    }

    public function reservations(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'statut' => ['nullable', Rule::enum(StatutReservation::class)],
        ]);

        $lignes = Reservation::query()->with(['vetement', 'user'])->filter($filters)->orderBy('date_retrait')->lazy(200)
            ->map(fn (Reservation $r) => [
                $r->code, $r->vetement->titre, $r->user->name, $r->user->email, $r->telephone,
                $r->date_retrait->format('d/m/Y'), $r->creneau->label(), $r->statut->label(), $r->created_at->format('d/m/Y H:i'),
            ]);

        return $this->csv('reservations', ['Code', 'Vêtement', 'Client', 'E-mail', 'Téléphone', 'Retrait', 'Créneau', 'Statut', 'Créée le'], $lignes);
    }

    /**
     * @param  list<string>  $entetes
     * @param  iterable<array<int, mixed>>  $lignes
     */
    private function csv(string $nom, array $entetes, iterable $lignes): StreamedResponse
    {
        return response()->streamDownload(function () use ($entetes, $lignes) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, $entetes, ';');
            foreach ($lignes as $ligne) {
                fputcsv($sortie, $ligne, ';');
            }
            fclose($sortie);
        }, "textilecycle-{$nom}-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
