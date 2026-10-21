<?php

namespace App\Services\Dons\Ia;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\TypeArticle;
use App\Models\Association;
use App\Models\DonHistorique;
use App\Services\Dons\ImpactCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * IA 4 — aide à la rédaction (description d'association, texte de campagne, rapport d'impact mensuel).
 * Le texte proposé est seulement affiché dans le formulaire : rien n'est enregistré sans relecture.
 * Sans IA : un modèle de texte rempli avec les données (campagne, rapport) ; la description reste à écrire.
 */
class RedactionIa
{
    private const CONSIGNE = 'Tu rédiges des textes pour une plateforme tunisienne de dons de textiles à des associations. '
        .'Écris en français clair et chaleureux, sans exagération ni information inventée : n\'utilise que les faits fournis. '
        .'Pas de titre, pas de markdown, pas d\'émojis.';

    public function __construct(private IaDons $ia, private ImpactCalculator $impact) {}

    /**
     * @return array{ok: bool, texte: ?string, ia: bool, message: string}
     */
    public function descriptionAssociation(string $nom, string $ville, ?string $besoins, ?string $brouillon): array
    {
        $texte = $this->rediger(
            "Rédige la présentation publique (80 à 150 mots, 2 paragraphes) de l'association « {$nom} », située à {$ville}.\n"
            .'Ses besoins en textiles : '.(filled($besoins) ? $besoins : 'non précisés')."\n"
            .(filled($brouillon) ? "Texte actuel à améliorer (garde ses faits) :\n".Str::limit($brouillon, 2000) : ''),
            80,
        );

        return $texte !== null
            ? $this->succes($texte)
            : ['ok' => false, 'texte' => null, 'ia' => false, 'message' => 'La rédaction automatique est indisponible : écrivez la description vous-même.'];
    }

    /**
     * @param  list<string>  $types  valeurs de TypeArticle
     * @return array{ok: bool, texte: ?string, ia: bool, message: string}
     */
    public function texteCampagne(string $titre, int $objectifKg, array $types, ?string $association): array
    {
        $libelles = collect($types)->map(fn ($t) => TypeArticle::tryFrom($t)?->label())->filter()->join(', ') ?: 'tous les textiles';

        $texte = $this->rediger(
            "Rédige le texte d'appel aux dons (60 à 120 mots) de la campagne « {$titre} »".($association ? " de l'association {$association}" : '').".\n"
            ."Objectif : {$objectifKg} kg. Articles recherchés : {$libelles}.\n"
            .'Explique pourquoi donner et comment (déposer ou faire collecter ses textiles via TexTileCycle).',
            40,
        );

        return $texte !== null ? $this->succes($texte) : [
            'ok' => true,
            'ia' => false,
            'message' => 'Rédaction IA indisponible : voici un modèle à compléter.',
            'texte' => ($association ? "{$association} lance la campagne « {$titre} »" : "Nous lançons la campagne « {$titre} »")
                ." pour collecter {$objectifKg} kg de textiles : {$libelles}.\n"
                .'Vos vêtements en bon état peuvent changer le quotidien des personnes que nous accompagnons. '
                .'Proposez votre don sur TexTileCycle : déposez-le sur place ou choisissez un créneau de collecte à domicile. Merci pour votre générosité !',
        ];
    }

    /**
     * Rapport d'impact du mois à partir des vrais chiffres de l'association (dons reçus pendant ce mois).
     *
     * @return array{ok: bool, texte: ?string, ia: bool, message: string, stats: array<string, mixed>}
     */
    public function rapportMensuel(Association $association, Carbon $mois): array
    {
        $stats = $this->statistiquesDuMois($association, $mois);
        $libelleMois = $mois->locale('fr')->translatedFormat('F Y');

        if ($stats['dons'] === 0) {
            return ['ok' => true, 'ia' => false, 'stats' => $stats, 'message' => 'Aucun don reçu ce mois-ci.',
                'texte' => "En {$libelleMois}, {$association->nom} n'a pas encore reçu de don via TexTileCycle."];
        }

        $faits = "Association : {$association->nom} ({$association->ville}). Mois : {$libelleMois}.\n"
            ."Dons reçus : {$stats['dons']}. Textiles reçus : {$stats['kg']} kg.\n"
            .'Répartition : '.collect($stats['types'])->map(fn ($n, $type) => "{$type} : {$n} don(s)")->join(', ').".\n"
            ."Impact estimé : {$stats['co2_kg']} kg de CO₂ évités, {$stats['eau_litres']} litres d'eau économisés.";

        $texte = $this->rediger(
            "Rédige le rapport d'impact mensuel (100 à 180 mots) destiné aux donateurs, à partir de ces chiffres réels. "
            ."Reprends les chiffres exacts, remercie les donateurs.\n".$faits,
            60,
        );

        return $texte !== null ? [...$this->succes($texte), 'stats' => $stats] : [
            'ok' => true,
            'ia' => false,
            'stats' => $stats,
            'message' => 'Rédaction IA indisponible : voici un rapport établi à partir des chiffres.',
            'texte' => "En {$libelleMois}, {$association->nom} a reçu {$stats['dons']} don(s) via TexTileCycle, soit {$stats['kg']} kg de textiles ("
                .collect($stats['types'])->map(fn ($n, $type) => mb_strtolower($type)." : {$n}")->join(', ').'). '
                ."Grâce à vous, environ {$stats['co2_kg']} kg de CO₂ et {$stats['eau_litres']} litres d'eau ont été économisés (estimation). Merci à tous les donateurs !",
        ];
    }

    /**
     * Dons de l'association passés à « Reçu » pendant le mois (date de réception de l'historique).
     *
     * @return array{dons: int, kg: float, types: array<string, int>, co2_kg: float, eau_litres: float}
     */
    public function statistiquesDuMois(Association $association, Carbon $mois): array
    {
        $dons = DonHistorique::query()
            ->where('nouveau_statut', DonStatut::Recu)
            ->whereBetween('don_historiques.created_at', [$mois->copy()->startOfMonth(), $mois->copy()->endOfMonth()])
            ->join('dons', 'dons.id', '=', 'don_historiques.don_id')
            ->where('dons.association_id', $association->id)
            ->whereNull('dons.deleted_at')
            ->distinct()
            ->get(['dons.id', 'dons.poids_kg', 'dons.type_article']);

        $kg = round((float) $dons->sum('poids_kg'), 1);
        $impact = $this->impact->pourKg($kg);

        return [
            'dons' => $dons->count(),
            'kg' => $kg,
            'types' => $dons->countBy(fn ($d) => TypeArticle::tryFrom($d->type_article)?->label() ?? $d->type_article)->all(),
            'co2_kg' => $impact['co2_kg'],
            'eau_litres' => $impact['eau_litres'],
        ];
    }

    private function rediger(string $consigne, int $minimum): ?string
    {
        $reponse = $this->ia->demander(
            'redaction',
            $consigne,
            ['type' => 'OBJECT', 'properties' => ['texte' => ['type' => 'STRING']], 'required' => ['texte']],
            ['texte' => ['required', 'string', "min:{$minimum}", 'max:5000']],
            systeme: self::CONSIGNE,
        );

        return $reponse !== null ? trim($reponse['texte']) : null;
    }

    /**
     * @return array{ok: bool, texte: string, ia: bool, message: string}
     */
    private function succes(string $texte): array
    {
        return ['ok' => true, 'texte' => $texte, 'ia' => true, 'message' => 'Texte proposé par l\'IA : relisez-le et modifiez-le avant d\'enregistrer.'];
    }
}
