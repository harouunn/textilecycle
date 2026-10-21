<?php

namespace App\Http\Controllers\Dons;

use App\Enums\Dons\TypeArticle;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Services\Dons\Ia\AnalysePhotoDon;
use App\Services\Dons\Ia\AssistantDonateur;
use App\Services\Dons\Ia\RedactionIa;
use App\Services\Dons\MatchingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Points d'entrée AJAX des fonctionnalités IA du module (limités à 10 requêtes par minute et par compte).
 * Ils répondent toujours en JSON 200 : si l'IA échoue, la réponse contient la version sans IA ou ok=false.
 */
class IaController extends Controller
{
    use AuthorizesRequests;

    /**
     * IA 1 — analyse de la photo du don (formulaire « Faire un don »).
     */
    public function analyserPhoto(Request $request, AnalysePhotoDon $analyse): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Choisissez une photo.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'Formats acceptés : JPG, PNG ou WebP.',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
        ]);

        return response()->json($analyse->analyser($request->file('photo'), $request->user()));
    }

    /**
     * IA 2 — associations recommandées pour le don en cours de saisie.
     */
    public function recommandations(Request $request, MatchingService $matching): JsonResponse
    {
        $data = $request->validate([
            'type_article' => ['required', Rule::enum(TypeArticle::class)],
            'ville' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'poids_kg' => ['nullable', 'numeric', 'min:0', 'max:5000'],
        ], [
            'type_article.required' => 'Choisissez d\'abord le type d\'article.',
            'type_article.enum' => 'Le type d\'article est invalide.',
            'description.max' => 'La description est trop longue pour la recommandation (1000 caractères au plus).',
            'poids_kg.numeric' => 'Le poids doit être un nombre.',
            'poids_kg.max' => 'Le poids ne peut pas dépasser :max kg.',
        ]);

        $resultat = $matching->recommander(
            TypeArticle::from($data['type_article']),
            $data['ville'] ?? null,
            $data['description'] ?? null,
            isset($data['poids_kg']) ? (float) $data['poids_kg'] : null,
        );

        return response()->json([
            ...$resultat,
            'message' => $resultat['simplifiee'] ? 'Recommandation simplifiée (sans IA) : basée sur la ville, la capacité et les campagnes.' : null,
        ]);
    }

    /**
     * IA 3 — assistant des donateurs (ouvert aux visiteurs).
     */
    public function assistant(Request $request, AssistantDonateur $assistant): JsonResponse
    {
        $data = $request->validate(['question' => ['required', 'string', 'min:2', 'max:500']], [
            'question.required' => 'Posez une question.',
            'question.max' => 'Votre question ne peut pas dépasser 500 caractères.',
        ]);

        return response()->json($assistant->repondre($data['question'], $request->session()));
    }

    public function oublierAssistant(Request $request): JsonResponse
    {
        $request->session()->forget(AssistantDonateur::CLE_SESSION);

        return response()->json(['ok' => true]);
    }

    /**
     * IA 4 — rédaction d'un texte, affiché dans le formulaire pour relecture (jamais enregistré ici).
     */
    public function rediger(Request $request, RedactionIa $redaction): JsonResponse
    {
        $type = $request->validate(['type' => ['required', 'in:description,campagne,rapport']], [
            'type.required' => 'Type de texte manquant.',
            'type.in' => 'Type de texte inconnu.',
        ])['type'];

        return response()->json(match ($type) {
            'description' => $this->redigerDescription($request, $redaction),
            'campagne' => $this->redigerCampagne($request, $redaction),
            'rapport' => $this->redigerRapport($request, $redaction),
        });
    }

    private function redigerDescription(Request $request, RedactionIa $redaction): array
    {
        $data = $request->validate([
            'association_id' => ['nullable', 'integer'],
            'nom' => ['required', 'string', 'max:255'],
            'ville' => ['required', 'string', 'max:100'],
            'besoins' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:5000'],
        ], [
            'nom.required' => 'Renseignez d\'abord le nom de l\'association.',
            'ville.required' => 'Renseignez d\'abord la ville de l\'association.',
        ]);

        // Association existante : son administrateur ou son responsable ; nouvelle association : l'administrateur.
        if (isset($data['association_id'])) {
            $this->authorize('modifierPresentation', Association::query()->findOrFail($data['association_id']));
        } else {
            $this->authorize('create', Association::class);
        }

        return $redaction->descriptionAssociation($data['nom'], $data['ville'], $data['besoins'] ?? null, $data['description'] ?? null);
    }

    private function redigerCampagne(Request $request, RedactionIa $redaction): array
    {
        $this->authorize('create', CampagneCollecte::class);

        $data = $request->validate([
            'titre' => ['required', 'string', 'max:150'],
            'objectif_kg' => ['required', 'integer', 'min:1'],
            'type_articles_cibles' => ['nullable', 'array'],
            'type_articles_cibles.*' => [Rule::enum(TypeArticle::class)],
            'association_id' => ['nullable', 'integer'],
        ], [
            'titre.required' => 'Renseignez d\'abord le titre de la campagne.',
            'objectif_kg.required' => 'Renseignez d\'abord l\'objectif en kg.',
        ]);

        return $redaction->texteCampagne(
            $data['titre'],
            (int) $data['objectif_kg'],
            $data['type_articles_cibles'] ?? [],
            isset($data['association_id']) ? Association::query()->find($data['association_id'])?->nom : null,
        );
    }

    private function redigerRapport(Request $request, RedactionIa $redaction): array
    {
        $data = $request->validate([
            'association_id' => ['required', 'integer'],
            'mois' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
        ], [
            'mois.required' => 'Choisissez le mois du rapport.',
            'mois.date_format' => 'Le mois doit être au format AAAA-MM.',
            'mois.before_or_equal' => 'Le rapport ne peut pas porter sur un mois à venir.',
        ]);
        $association = Association::query()->findOrFail($data['association_id']);
        $this->authorize('view', $association);

        return $redaction->rapportMensuel($association, Carbon::createFromFormat('!Y-m', $data['mois']));
    }
}
