<?php

namespace App\Services\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Taille;
use App\Models\Categorie;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Classement automatique d'un vêtement déposé, sans IA.
 *
 * La photo donne la couleur dominante et indique si le vêtement est uni (voir AnalyseImage).
 * Le titre saisi par le déposant donne le type (et donc la catégorie), la matière, l'état, le genre et la taille,
 * reconnus par mots-clés. Une description est ensuite rédigée à partir de ces éléments.
 * Chaque déduction est expliquée dans « indices » pour que le déposant puisse vérifier.
 */
class ClassificateurVetement
{
    /** Types de vêtement : libellé et mots qui les désignent (dans le titre et dans les catégories). */
    private const TYPES = [
        'chemise' => ['Chemise', ['chemise', 'chemisier', 'blouse']],
        't-shirt' => ['T-shirt', ['t-shirt', 'tshirt', 'tee-shirt', 'top', 'debardeur', 'polo', 'haut']],
        'jean' => ['Jean', ['jean']],
        'pantalon' => ['Pantalon', ['pantalon', 'short', 'legging', 'chino']],
        'robe' => ['Robe', ['robe', 'combinaison']],
        'jupe' => ['Jupe', ['jupe']],
        'pull' => ['Pull', ['pull', 'sweat', 'gilet', 'cardigan', 'maille']],
        'veste' => ['Veste', ['veste', 'blouson', 'blazer']],
        'manteau' => ['Manteau', ['manteau', 'parka', 'doudoune', 'impermeable', 'trench']],
    ];

    /** Matières : nom affiché et mots qui la désignent (mots entiers). */
    private const MATIERES = [
        'Coton' => ['coton'],
        'Lin' => ['lin'],
        'Laine' => ['laine'],
        'Denim' => ['denim', 'jean'],
        'Soie' => ['soie'],
        'Polyester' => ['polyester'],
        'Cuir' => ['cuir', 'simili'],
        'Velours' => ['velours'],
        'Cachemire' => ['cachemire'],
        'Viscose' => ['viscose'],
        'Satin' => ['satin'],
        'Jersey' => ['jersey'],
    ];

    /** États reconnus, du plus grave au meilleur ; « comme neuf » est testé avant « neuf ». */
    private const ETATS = [
        'a_reparer' => ['trou', 'dechir', 'tache', 'accroc', 'a reparer', 'decousu', 'casse'],
        'use' => ['use', 'usure', 'bouloch', 'delave', 'abime'],
        'tres_bon' => ['tres bon', 'comme neuf', 'excellent'],
        'neuf' => ['neuf', 'etiquette', 'jamais porte'],
    ];

    private const GENRES = [
        'enfant' => ['enfant', 'bebe', 'fille', 'garcon', 'junior'],
        'femme' => ['femme', 'dame', 'chemisier', 'robe', 'jupe'],
        'homme' => ['homme'],
    ];

    public function __construct(private AnalyseImage $analyseImage) {}

    /**
     * @param  Collection<int, Categorie>  $categories
     * @return array{categorie_id: ?int, matiere: ?string, etat: Etat, genre: Genre, taille: ?Taille, couleur: string, uni: bool, description: string, indices: list<string>}
     */
    public function classer(string $cheminPhoto, ?string $titre, Collection $categories): array
    {
        $photo = $this->analyseImage->analyser($cheminPhoto);
        $texte = $this->normaliser($titre);
        $indices = ["Photo : couleur dominante {$photo['couleur']} ({$photo['part']} % du vêtement), ".($photo['uni'] ? 'vêtement uni.' : 'vêtement à motifs ou multicolore.')];

        // Type et catégorie
        $type = $this->typeReconnu($texte);
        $categorie = $type ? $this->categoriePourType($type, $categories) : null;
        $indices[] = match (true) {
            $categorie !== null => 'Titre : type « '.self::TYPES[$type][0]." » → catégorie {$categorie->nom}.",
            $type !== null => 'Titre : type « '.self::TYPES[$type][0].' » reconnu, mais aucune catégorie ne correspond : choisissez-la.',
            default => 'Aucun type de vêtement reconnu dans le titre : choisissez la catégorie.',
        };

        // Matière
        $matiere = $this->premiereCle(self::MATIERES, $texte, motEntier: true);
        if ($matiere !== null) {
            $indices[] = "Titre : matière {$matiere}.";
        } elseif (in_array($type, ['jean', 'pantalon'], true) && str_starts_with($photo['couleur'], 'bleu')) {
            $matiere = 'Denim';
            $indices[] = 'Pantalon bleu sur la photo : matière probable Denim, à vérifier.';
        } else {
            $indices[] = 'Matière non indiquée dans le titre : à compléter.';
        }

        // État
        $cleEtat = $this->premiereCle(self::ETATS, $texte);
        $etat = $cleEtat ? Etat::from($cleEtat) : Etat::Bon;
        $indices[] = $cleEtat
            ? "Titre : état « {$etat->label()} »."
            : 'Aucun indice d\'état dans le titre : « Bon état » par défaut, à vérifier.';

        // Genre et taille
        $cleGenre = $this->premiereCle(self::GENRES, $texte);
        $genre = $cleGenre ? Genre::from($cleGenre) : Genre::Unisexe;
        $taille = preg_match('/\btaille\s*:?\s*(xxl|xl|xs|s|m|l)\b/', $texte, $m) ? Taille::from(Str::upper($m[1])) : null;

        return [
            'categorie_id' => $categorie?->id,
            'matiere' => $matiere,
            'etat' => $etat,
            'genre' => $genre,
            'taille' => $taille,
            'couleur' => $photo['couleur'],
            'uni' => $photo['uni'],
            'description' => $this->description($type, $matiere, $photo, $etat, $genre, $taille),
            'indices' => $indices,
        ];
    }

    private function normaliser(?string $texte): string
    {
        return Str::of($texte ?? '')->lower()->ascii()->squish()->toString();
    }

    /**
     * Le type cité le plus tôt dans le titre : « robe chemise » est une robe.
     */
    private function typeReconnu(string $texte): ?string
    {
        $positions = [];
        foreach (self::TYPES as $cle => [, $mots]) {
            if (preg_match($this->motif($mots), $texte, $m, PREG_OFFSET_CAPTURE)) {
                $positions[$cle] = $m[0][1];
            }
        }
        asort($positions);

        return array_key_first($positions);
    }

    /**
     * Catégorie dont le nom ou la description cite ce type (« Pantalons & jeans » pour un jean).
     *
     * @param  Collection<int, Categorie>  $categories
     */
    private function categoriePourType(string $type, Collection $categories): ?Categorie
    {
        $motif = $this->motif(self::TYPES[$type][1]);

        return $categories->first(fn (Categorie $categorie) => preg_match($motif, $this->normaliser($categorie->nom)) === 1)
            ?? $categories->first(fn (Categorie $categorie) => preg_match($motif, $this->normaliser($categorie->description)) === 1);
    }

    /**
     * @param  array<string, list<string>>  $motsParCle
     */
    private function premiereCle(array $motsParCle, string $texte, bool $motEntier = false): ?string
    {
        foreach ($motsParCle as $cle => $mots) {
            if (preg_match($this->motif($mots, $motEntier), $texte)) {
                return $cle;
            }
        }

        return null;
    }

    /**
     * Début de mot (« trou » reconnaît « troue ») ou mot entier, pluriel compris (« lin » ne reconnaît pas « linge »).
     *
     * @param  list<string>  $mots
     */
    private function motif(array $mots, bool $motEntier = false): string
    {
        $alternatives = implode('|', array_map(fn (string $mot) => preg_quote($mot, '/'), $mots));

        return '/\b(?:'.$alternatives.')'.($motEntier ? 's?\b' : '').'/';
    }

    /**
     * @param  array{couleur: string, part: int, uni: bool}  $photo
     */
    private function description(?string $type, ?string $matiere, array $photo, Etat $etat, Genre $genre, ?Taille $taille): string
    {
        $phrases = [
            ($type ? self::TYPES[$type][0] : 'Vêtement')
                .($matiere ? ' en '.Str::lower($matiere) : '')
                .", coloris {$photo['couleur']}".($photo['uni'] ? ', uni.' : ', à motifs.'),
            match ($etat) {
                Etat::Neuf => 'Jamais porté, en parfait état.',
                Etat::TresBon => 'Très bon état, peu porté.',
                Etat::Bon => 'Bon état général.',
                Etat::Use => 'Porté, présente des signes d\'usure.',
                Etat::AReparer => 'Présente un défaut à réparer (voir la photo).',
            },
            match ($genre) {
                Genre::Homme => 'Coupe homme.',
                Genre::Femme => 'Coupe femme.',
                Genre::Enfant => 'Pour enfant.',
                Genre::Unisexe => 'Coupe unisexe.',
            },
        ];

        if ($taille) {
            $phrases[] = "Taille {$taille->label()}.";
        }

        return implode(' ', $phrases);
    }
}
