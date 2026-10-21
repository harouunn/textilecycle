<?php

namespace App\Services\Ateliers\IA;

use App\Models\DemandeReparation;
use Illuminate\Support\Facades\Log;

class AnalysisService
{
    public function __construct(
        private VisionService $vision,
        private TextService $text
    ) {}

    public function analyser(DemandeReparation $demande, bool $forceReanalyse = false): void
    {
        if (!$forceReanalyse && $demande->analyse_statut === 'ok') {
            Log::info("Demande {$demande->id} déjà analysée, skip");
            return;
        }

        $visionOk = $demande->analyse_vision !== null;
        $texteOk = $demande->analyse_texte !== null;

        if (!$forceReanalyse && $visionOk && $texteOk) {
            return;
        }

        if (!$visionOk && $demande->photo) {
            $resultatVision = $this->vision->analyser($demande->photo);
            if ($resultatVision) {
                // Vérifier si c'est un message d'erreur (pas un vêtement)
                if (isset($resultatVision['erreur']) && $resultatVision['erreur'] === 'pas_vetement') {
                    $demande->analyse_message = $resultatVision['message'];
                    $visionOk = false; // Marquer comme échoué
                } else {
                    $demande->analyse_vision = $resultatVision;
                    $visionOk = true;
                }
            }
        } elseif (!$demande->photo) {
            $visionOk = true;
        }

        if (!$texteOk && $demande->description) {
            $resultatTexte = $this->text->analyser($demande->description);
            if ($resultatTexte) {
                $demande->analyse_texte = $resultatTexte;
                $texteOk = true;
            }
        } elseif (!$demande->description && !$demande->photo) {
            // Si ni photo ni description, on ne peut rien analyser
            $texteOk = false;
        } elseif (!$demande->description) {
            // Pas de description mais il y a une photo
            $texteOk = true;
        }

        if ($visionOk && $texteOk) {
            $demande->analyse_statut = 'ok';
        } else {
            $demande->analyse_statut = 'erreur';
        }

        $demande->analysed_at = now();
        $demande->save(); // Utiliser save() pour déclencher l'Observer et les notifications

        Log::info("Demande {$demande->id} analysée", [
            'statut' => $demande->analyse_statut,
            'vision_ok' => $visionOk,
            'texte_ok' => $texteOk,
        ]);
    }
}
