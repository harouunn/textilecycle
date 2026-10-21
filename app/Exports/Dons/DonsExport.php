<?php

namespace App\Exports\Dons;

use App\Models\Don;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export Excel / CSV de la liste des dons du back office, avec les mêmes filtres que l'écran (Don::scopeFiltrer).
 *
 * @implements WithMapping<Don>
 */
class DonsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private array $filters) {}

    public function query(): Builder
    {
        return Don::query()
            ->with(['association', 'user', 'campagne'])
            ->filtrer($this->filters)
            ->latest();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['N°', 'Référence', 'Proposé le', 'Association', 'Campagne', 'Donateur', 'E-mail', 'Type d\'article', 'Quantité', 'Poids (kg)', 'État', 'Mode de remise', 'Date de remise', 'Statut'];
    }

    /**
     * @param  Don  $don
     * @return list<mixed>
     */
    public function map($don): array
    {
        return [
            $don->id,
            $don->reference,
            $don->created_at->format('d/m/Y H:i'),
            $don->association?->nom,
            $don->campagne?->titre,
            $don->user?->name,
            $don->user?->email,
            $don->typeLabel(),
            $don->quantite,
            $don->poids_kg !== null ? (float) $don->poids_kg : null,
            $don->etatLabel(),
            $don->modeLabel(),
            $don->date_remise?->format('d/m/Y'),
            $don->statutLabel(),
        ];
    }
}
