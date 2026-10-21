<?php

namespace App\Services\Dons;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\ModeRemise;
use App\Events\Dons\DonStatutChanged;
use App\Exceptions\Dons\InvalidDonTransitionException;
use App\Jobs\Dons\AnalyserRisqueDon;
use App\Models\Association;
use App\Models\CreneauCollecte;
use App\Models\Don;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Seul point d'entrée pour proposer un don et changer son statut : vérifie la transition,
 * l'enregistre dans l'historique et émet DonStatutChanged.
 * Les droits (qui peut faire quoi) sont vérifiés en amont par DonPolicy.
 */
class DonWorkflowService
{
    /**
     * Un donateur propose un don. Pour une collecte à domicile, la place sur le créneau
     * est réservée sous verrou : deux donateurs ne peuvent pas prendre la dernière place.
     *
     * Le don est ensuite analysé en file d'attente (AnalyserRisqueDon : score de risque).
     *
     * @param  array<string, mixed>  $data  données validées (FaireDonRequest)
     * @param  array<string, mixed>|null  $iaAnalyse  analyse IA déjà validée (ex. ['photo' => ...]), enregistrée dans ia_analyse
     *
     * @throws ValidationException si le créneau est complet ou n'appartient pas à l'association
     */
    public function proposer(Association $association, array $data, User $by, ?array $iaAnalyse = null): Don
    {
        return DB::transaction(function () use ($association, $data, $by, $iaAnalyse) {
            $collecte = ($data['mode_remise'] ?? null) === ModeRemise::CollecteADomicile->value;
            $data['creneau_id'] = $collecte ? ($data['creneau_id'] ?? null) : null;

            if ($data['creneau_id']) {
                $creneau = CreneauCollecte::query()
                    ->whereKey($data['creneau_id'])
                    ->where('association_id', $association->id)
                    ->lockForUpdate()
                    ->first();

                if (! $creneau || $creneau->date->lt(today()) || $creneau->placesRestantes() < 1) {
                    throw ValidationException::withMessages([
                        'creneau_id' => 'Ce créneau de collecte vient d\'être complet. Choisissez-en un autre.',
                    ]);
                }

                $data['date_remise'] = $creneau->date->toDateString();
            }

            $don = $association->dons()->make([
                ...$data,
                'user_id' => $by->id,
                'statut' => DonStatut::Propose,
            ]);
            // ia_analyse n'est pas fillable : seul le serveur y écrit, jamais un formulaire.
            $don->forceFill(['ia_analyse' => $iaAnalyse ?: null])->save();

            DonStatutChanged::dispatch($don, null, DonStatut::Propose, $by, null);
            AnalyserRisqueDon::dispatch($don)->afterCommit();

            return $don;
        });
    }

    /**
     * @throws InvalidDonTransitionException
     */
    public function transition(Don $don, DonStatut $to, ?string $commentaire, User $by): Don
    {
        $commentaire = filled($commentaire) ? trim($commentaire) : null;

        DB::transaction(function () use ($don, $to, $commentaire, $by) {
            // Relu sous verrou : deux clics simultanés ne peuvent pas appliquer deux transitions.
            $locked = Don::query()->whereKey($don->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->statut;

            if (! $from->canTransitionTo($to)) {
                throw new InvalidDonTransitionException($from, $to);
            }

            $locked->statut = $to;
            // La référence du reçu est fixée à la réception et ne change plus ensuite.
            if ($to === DonStatut::Recu && $locked->reference === null) {
                $locked->reference = Don::genererReference($locked->id);
            }
            $locked->save();

            $locked->historiques()->create([
                'user_id' => $by->id,
                'ancien_statut' => $from,
                'nouveau_statut' => $to,
                'commentaire' => $commentaire,
            ]);

            $don->setRawAttributes($locked->getAttributes(), true);
            $don->unsetRelation('historiques');

            DonStatutChanged::dispatch($don, $from, $to, $by, $commentaire);
        });

        return $don;
    }
}
