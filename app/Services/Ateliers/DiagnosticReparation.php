<?php

namespace App\Services\Ateliers;

use App\Enums\Ateliers\TypeReparation;
use App\Enums\Ateliers\TypeVetement;
use Illuminate\Support\Str;

/**
 * Diagnostic automatique d'une demande de réparation, à partir de sa description et/ou de sa photo.
 *
 * Les réparations sont reconnues par mots-clés dans la description. Le coût additionne le tarif
 * de chaque réparation, multiplié par le type de vêtement, la gravité et la matière. Le délai part
 * de la réparation la plus longue, puis s'allonge avec les réparations en plus et la charge de l'atelier.
 * Sans réparation reconnue (photo seule ou texte vague), l'estimation est provisoire : « à expertiser ».
 */
class DiagnosticReparation
{
    /** Mots qui signalent un dommage important. */
    private const MOTS_GRAVITE = ['grand', 'gros', 'enorme', 'plusieurs', 'important', 'tres abime', 'partout'];

    /** Matières délicates : plus chères et plus longues à travailler. */
    private const MOTS_MATIERE_DELICATE = ['cuir', 'daim', 'soie', 'velours'];

    private const COEFFICIENT_GRAVITE = 1.3;

    private const COEFFICIENT_MATIERE_DELICATE = 1.5;

    private const JOURS_MATIERE_DELICATE = 2;

    /** L'atelier ajoute un jour de délai par tranche de 5 demandes déjà en cours. */
    private const DEMANDES_PAR_JOUR_DE_RETARD = 5;

    /**
     * @return array{reparations: list<TypeReparation>, cout_estime: float, delai_estime_jours: int, diagnostic: string}
     */
    public function analyser(?string $description, bool $avecPhoto, TypeVetement $typeVetement, int $demandesEnCours = 0): array
    {
        $texte = Str::of($description ?? '')->lower()->ascii()->squish()->toString();

        $reparations = $this->reparationsReconnues($texte);
        $aExpertiser = $reparations === [];
        if ($aExpertiser) {
            $reparations = [TypeReparation::AExpertiser];
        }

        $grave = $this->contientUnDe($texte, self::MOTS_GRAVITE);
        $delicat = $this->contientUnDe($texte, self::MOTS_MATIERE_DELICATE);

        $cout = array_sum(array_map(fn (TypeReparation $r) => $r->coutBase(), $reparations))
            * $typeVetement->coefficient()
            * ($grave ? self::COEFFICIENT_GRAVITE : 1)
            * ($delicat ? self::COEFFICIENT_MATIERE_DELICATE : 1);

        $delai = max(array_map(fn (TypeReparation $r) => $r->delaiBase(), $reparations))
            + (count($reparations) - 1)
            + ($delicat ? self::JOURS_MATIERE_DELICATE : 0)
            + intdiv(max($demandesEnCours, 0), self::DEMANDES_PAR_JOUR_DE_RETARD);

        return [
            'reparations' => $reparations,
            'cout_estime' => round($cout, 2),
            'delai_estime_jours' => $delai,
            'diagnostic' => $this->explication($reparations, $aExpertiser, $avecPhoto, $typeVetement, $grave, $delicat, $demandesEnCours),
        ];
    }

    /**
     * @return list<TypeReparation>
     */
    private function reparationsReconnues(string $texte): array
    {
        if ($texte === '') {
            return [];
        }

        return array_values(array_filter(
            TypeReparation::cases(),
            fn (TypeReparation $reparation) => $this->contientUnDe($texte, $reparation->motsCles()),
        ));
    }

    /**
     * Cherche les mots en début de mot : « trou » reconnaît « troue » et « trous »,
     * mais « pression » ne reconnaît pas « impression ».
     *
     * @param  list<string>  $mots
     */
    private function contientUnDe(string $texte, array $mots): bool
    {
        if ($mots === []) {
            return false;
        }

        $alternatives = implode('|', array_map(fn (string $mot) => preg_quote($mot, '/'), $mots));

        return preg_match('/\b(?:'.$alternatives.')/', $texte) === 1;
    }

    /**
     * @param  list<TypeReparation>  $reparations
     */
    private function explication(array $reparations, bool $aExpertiser, bool $avecPhoto, TypeVetement $typeVetement, bool $grave, bool $delicat, int $demandesEnCours): string
    {
        $lignes = [];

        if ($aExpertiser) {
            $lignes[] = $avecPhoto
                ? 'Aucune réparation précise n\'a été reconnue dans le texte : l\'atelier examinera la photo pour confirmer le diagnostic.'
                : 'La description ne permet pas d\'identifier la réparation : l\'atelier vous recontactera pour préciser le besoin.';
            $lignes[] = 'Estimation provisoire, basée sur une intervention moyenne.';
        } else {
            $lignes[] = 'Réparation(s) identifiée(s) : '.implode(', ', array_map(fn (TypeReparation $r) => Str::lower($r->label()), $reparations)).'.';
            if ($avecPhoto) {
                $lignes[] = 'La photo jointe permettra à l\'atelier de vérifier le diagnostic.';
            }
        }

        if ($typeVetement->coefficient() > 1) {
            $lignes[] = "Vêtement de type « {$typeVetement->label()} » : travail plus long, tarif majoré.";
        }
        if ($grave) {
            $lignes[] = 'Dommage important signalé : tarif majoré de 30 %.';
        }
        if ($delicat) {
            $lignes[] = 'Matière délicate (cuir, daim, soie ou velours) : tarif majoré de 50 % et 2 jours de plus.';
        }
        if ($demandesEnCours >= self::DEMANDES_PAR_JOUR_DE_RETARD) {
            $lignes[] = "L'atelier traite déjà {$demandesEnCours} demande(s) : le délai en tient compte.";
        }

        return implode("\n", $lignes);
    }
}
