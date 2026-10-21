<?php

namespace App\Jobs\Dons;

use App\Models\Don;
use App\Services\Dons\Ia\DetecteurRisque;
use App\Services\Dons\Ia\IaDons;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * IA 5 — anomalies : lancé (en file d'attente) à la création d'un don par un donateur.
 * Les règles donnent score_risque ; à partir du seuil, Gemini rédige une courte explication pour l'administrateur.
 * Sans IA, l'explication reprend simplement les règles déclenchées.
 */
class AnalyserRisqueDon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public Don $don) {}

    public function handle(DetecteurRisque $detecteur, IaDons $ia): void
    {
        $don = $this->don->fresh();
        if (! $don) {
            return;
        }

        ['score' => $score, 'raisons' => $raisons] = $detecteur->evaluer($don);
        $explication = null;

        if ($score >= Don::SEUIL_RISQUE) {
            $reponse = $ia->demander(
                'risque',
                "Un don de textiles vient d'être proposé sur une plateforme de dons à des associations.\n"
                ."Quantité : {$don->quantite} · Poids : ".($don->poids_kg ?? 'non précisé')." kg · Type : {$don->typeLabel()}\n"
                .'Message du donateur : '.(filled($don->message) ? '« '.Str::limit($don->message, 300).' »' : '(vide)')."\n"
                .'Signaux détectés : '.implode(' ', $raisons)."\n"
                ."En une ou deux phrases en français, explique à l'administrateur ce qui mérite d'être vérifié. Reste factuel, n'accuse pas le donateur.",
                ['type' => 'OBJECT', 'properties' => ['explication' => ['type' => 'STRING']], 'required' => ['explication']],
                ['explication' => ['required', 'string', 'min:10', 'max:500']],
                userId: $don->user_id,
            );
            $explication = $reponse['explication'] ?? null;
        }

        $don->forceFill([
            'score_risque' => $score,
            'ia_analyse' => [
                ...($don->ia_analyse ?? []),
                'risque' => [
                    'score' => $score,
                    'raisons' => $raisons,
                    'explication' => $explication ?? ($raisons ? implode(' ', $raisons) : null),
                    'source' => $explication ? 'ia' : 'regles',
                    'analyse_le' => now()->toIso8601String(),
                ],
            ],
        ])->saveQuietly();
    }
}
