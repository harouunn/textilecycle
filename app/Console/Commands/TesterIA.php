<?php

namespace App\Console\Commands;

use App\Models\Categorie;
use App\Services\Depot\ClassificateurIA;
use Illuminate\Console\Command;

/**
 * Vérifie l'IA locale du module Dépôt et, si une photo est donnée, la classe comme le ferait le site.
 *
 * php artisan depot:ia
 * php artisan depot:ia public/front/images/product-item-1.jpg --titre="Jean"
 */
class TesterIA extends Command
{
    protected $signature = 'depot:ia {photo? : Chemin d\'une photo à classer} {--titre= : Titre saisi par le déposant}';

    protected $description = "Vérifie qu'Ollama et le modèle de vision sont prêts, puis classe une photo de test";

    public function handle(ClassificateurIA $ia): int
    {
        $etat = $ia->etat();
        $this->line("Modèle configuré : {$etat['modele']}");

        if (! $etat['en_ligne']) {
            $this->error('Ollama ne répond pas sur '.config('depot.ia.url').'. Lancez Ollama (ou « ollama serve »).');

            return self::FAILURE;
        }

        if (! $etat['modele_installe']) {
            $this->error("Le modèle n'est pas installé. Lancez : ollama pull {$etat['modele']}");

            return self::FAILURE;
        }

        $this->info('Ollama est en ligne et le modèle est installé.');

        if (! $photo = $this->argument('photo')) {
            return self::SUCCESS;
        }

        if (! is_file($photo)) {
            $this->error("Photo introuvable : {$photo}");

            return self::FAILURE;
        }

        $debut = microtime(true);
        $resultat = $ia->classer($photo, $this->option('titre'), Categorie::query()->actives()->get());

        if ($resultat === null) {
            $this->error("L'IA n'a pas pu classer la photo (voir storage/logs/laravel.log).");

            return self::FAILURE;
        }

        $this->table(['Champ', 'Valeur proposée'], [
            ['Catégorie', Categorie::find($resultat['categorie_id'])?->nom ?? '—'],
            ['Matière', $resultat['matiere'] ?? '—'],
            ['État', $resultat['etat']->label()],
            ['Genre', $resultat['genre']->label()],
            ['Taille', $resultat['taille']?->value ?? 'inconnue'],
            ['Couleur', $resultat['couleur']],
        ]);
        $this->line($resultat['description']);
        collect($resultat['indices'])->each(fn ($indice) => $this->line("  • {$indice}"));
        $this->info(sprintf('Analyse faite en %.1f s.', microtime(true) - $debut));

        return self::SUCCESS;
    }
}
