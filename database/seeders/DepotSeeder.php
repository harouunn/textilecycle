<?php

namespace Database\Seeders;

use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutVetement;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use App\Services\Depot\DetecteurDoublons;
use App\Services\Depot\GestionReservations;
use App\Services\Depot\OptimiseurPhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Module « Dépôt & vêtements » : données de démonstration réalistes, avec de vraies photos.
 *  - 7 catégories ;
 *  - 28 vêtements dont la photo correspond au titre (images du template copiées dans storage/app/public/vetements,
 *    avec miniature et empreinte visuelle comme un vrai dépôt) ;
 *  - tous les cas du module : en ligne, à valider, refusé (avec motif), réservé, donné, recyclé, doublon de photo ;
 *  - 5 réservations passées par le vrai circuit métier (en attente, confirmée, retirée, annulée, expirée).
 *
 * php artisan db:seed --class=DepotSeeder
 */
class DepotSeeder extends Seeder
{
    /** Dossier des images du template utilisées comme photos de démonstration. */
    private const IMAGES = 'front/images/';

    public function run(GestionReservations $reservations, OptimiseurPhoto $optimiseur, DetecteurDoublons $doublons): void
    {
        $categories = collect([
            ['nom' => 'Hauts', 'description' => 'T-shirts, chemises, blouses et tops.', 'icone' => 'bx-shopping-bag', 'couleur' => '#3d7a4f'],
            ['nom' => 'Pantalons & jeans', 'description' => 'Pantalons, jeans, shorts et joggings.', 'icone' => 'bx-closet', 'couleur' => '#4a6fa5'],
            ['nom' => 'Robes & jupes', 'description' => 'Robes, jupes et combinaisons.', 'icone' => 'bx-star', 'couleur' => '#b5838d'],
            ['nom' => 'Pulls & gilets', 'description' => 'Pulls, sweats, gilets et cardigans.', 'icone' => 'bx-wind', 'couleur' => '#8a5a44'],
            ['nom' => 'Vestes & manteaux', 'description' => 'Vestes, blousons, blazers, trenchs et manteaux.', 'icone' => 'bx-cloud-rain', 'couleur' => '#6d597a'],
            ['nom' => 'Accessoires', 'description' => 'Sacs, lunettes, bijoux et petite maroquinerie.', 'icone' => 'bx-shopping-bag', 'couleur' => '#c08a3e'],
            ['nom' => 'Enfants', 'description' => 'Vêtements pour bébés et enfants.', 'icone' => 'bx-happy', 'couleur' => '#d08c3c'],
        ])->mapWithKeys(fn (array $categorie, int $index) => [$categorie['nom'] => Categorie::query()->updateOrCreate(
            ['nom' => $categorie['nom']],
            [...$categorie, 'ordre' => $index + 1, 'active' => true],
        )]);

        $deposants = $this->deposants();

        $vetements = collect($this->vetements())->map(function (array $donnees, int $index) use ($categories, $deposants, $optimiseur, $doublons) {
            $photo = $this->copierPhoto($donnees['image'], $index, $optimiseur);

            return Vetement::query()->create([
                'categorie_id' => $categories[$donnees['categorie']]->id,
                'user_id' => $deposants[$index % $deposants->count()]->id,
                'titre' => $donnees['titre'],
                'description' => $donnees['description'],
                'taille' => $donnees['taille'],
                'genre' => $donnees['genre'],
                'matiere' => $donnees['matiere'],
                'etat' => $donnees['etat'],
                'photo' => $photo,
                'photo_empreinte' => $doublons->empreinte($photo),
                'statut' => $donnees['statut'] ?? StatutVetement::Disponible,
                'moderation' => $donnees['moderation'] ?? Moderation::Approuve,
                'motif_refus' => $donnees['motif_refus'] ?? null,
                'date_depot' => today()->subDays($donnees['il_y_a'])->toDateString(),
            ]);
        });

        $this->reservations($reservations, $vetements, $deposants);
    }

    /**
     * Comptes de démonstration qui déposent et réservent (mot de passe : password).
     *
     * @return Collection<int, User>
     */
    private function deposants()
    {
        $clients = collect([
            ['name' => 'Client Démo', 'email' => 'client@example.com'],
            ['name' => 'Inès Ben Ali', 'email' => 'ines@example.com'],
            ['name' => 'Youssef Trabelsi', 'email' => 'youssef@example.com'],
            ['name' => 'Salma Gharbi', 'email' => 'salma@example.com'],
        ]);

        return $clients->map(fn (array $client) => User::query()->where('email', $client['email'])->first()
            ?? User::factory()->create($client));
    }

    /**
     * Copie une image du template comme si un client l'avait déposée : photo, miniature 600 px.
     */
    private function copierPhoto(string $image, int $index, OptimiseurPhoto $optimiseur): string
    {
        $chemin = Vetement::PHOTO_DIRECTORY.'/demo-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'-'.$image;
        Storage::disk('public')->put($chemin, (string) file_get_contents(public_path(self::IMAGES.$image)));
        $optimiseur->optimiser($chemin);

        return $chemin;
    }

    /**
     * Réservations à chaque étape, passées par GestionReservations (le statut des vêtements suit).
     */
    private function reservations(GestionReservations $reservations, $vetements, $deposants): void
    {
        $scenarios = [
            ['etape' => 'en_attente', 'message' => 'Je passerai avec un sac, merci !'],
            ['etape' => 'confirmee', 'message' => 'Est-il possible de l\'essayer sur place ?'],
            ['etape' => 'retiree', 'message' => null],
            ['etape' => 'annulee', 'message' => null],
            ['etape' => 'expiree', 'message' => 'Je viendrai en fin de matinée.'],
        ];

        $disponibles = $vetements->filter(fn (Vetement $v) => $v->moderation === Moderation::Approuve && $v->statut === StatutVetement::Disponible)->values();

        foreach ($scenarios as $index => $scenario) {
            $vetement = $disponibles[$index * 3];
            $client = $deposants->first(fn (User $user) => $user->id !== $vetement->user_id);

            $reservation = $reservations->reserver($vetement, $client, [
                'date_retrait' => today()->addDays($index + 2)->toDateString(),
                'creneau' => $index % 2 ? 'apres_midi' : 'matin',
                'telephone' => '22 '.(410 + $index * 37).' '.(120 + $index * 53),
                'message' => $scenario['message'],
            ]);

            if ($scenario['etape'] === 'confirmee') {
                $reservations->confirmer($reservation);
            } elseif ($scenario['etape'] === 'retiree') {
                $reservations->marquerRetiree($reservation);
            } elseif ($scenario['etape'] === 'annulee') {
                $reservations->annuler($reservation, 'Le client a trouvé une autre taille.');
            } elseif ($scenario['etape'] === 'expiree') {
                // Date de retrait dépassée : la tâche quotidienne l'annule et remet le vêtement au catalogue.
                $reservation->update(['date_retrait' => today()->subDays(2)]);
                $reservations->expirer();
            }
        }
    }

    /**
     * Vêtements de démonstration : la photo (image du template) correspond au titre et à la catégorie.
     *
     * @return list<array<string, mixed>>
     */
    private function vetements(): array
    {
        $refuse = ['moderation' => Moderation::Refuse, 'motif_refus' => 'La photo ne montre pas le vêtement en entier : merci d\'en déposer une autre.'];

        return [
            ['titre' => 'Pull sans manches torsadé', 'categorie' => 'Pulls & gilets', 'image' => 'product-item-1.jpg', 'taille' => 'M', 'genre' => 'homme', 'matiere' => 'Laine', 'etat' => 'tres_bon', 'il_y_a' => 2,
                'description' => 'Pull sans manches bleu ciel à torsades, à porter sur une chemise. Maille douce, aucune bouloche.'],
            ['titre' => 'Pull court en maille kaki', 'categorie' => 'Pulls & gilets', 'image' => 'product-item-10.jpg', 'taille' => 'S', 'genre' => 'femme', 'matiere' => 'Laine', 'etat' => 'bon', 'il_y_a' => 4,
                'description' => 'Pull court col montant vert kaki, coupe ajustée. Très chaud pour l\'hiver.'],
            ['titre' => 'Débardeur côtelé beige', 'categorie' => 'Hauts', 'image' => 'product-item-2.jpg', 'taille' => 'S', 'genre' => 'femme', 'matiere' => 'Coton', 'etat' => 'tres_bon', 'il_y_a' => 5,
                'description' => 'Débardeur en coton côtelé couleur sable, facile à associer. Porté deux fois.'],
            ['titre' => 'Jean baggy délavé', 'categorie' => 'Pantalons & jeans', 'image' => 'product-item-3.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Denim', 'etat' => 'bon', 'il_y_a' => 6,
                'description' => 'Jean ample taille haute, bleu délavé. Légère usure naturelle en bas des jambes.'],
            ['titre' => 'T-shirt blanc basique', 'categorie' => 'Hauts', 'image' => 'product-item-4.jpg', 'taille' => 'L', 'genre' => 'homme', 'matiere' => 'Coton', 'etat' => 'neuf', 'il_y_a' => 7,
                'description' => 'T-shirt blanc en coton épais, coupe droite. Jamais porté, acheté en double.'],
            ['titre' => 'Robe fleurie à bretelles', 'categorie' => 'Robes & jupes', 'image' => 'product-item-5.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Viscose', 'etat' => 'tres_bon', 'il_y_a' => 9,
                'description' => 'Robe d\'été noire à fleurs rouges, fluide et légère. Parfaite pour les beaux jours.'],
            ['titre' => 'Blazer noir croisé', 'categorie' => 'Vestes & manteaux', 'image' => 'product-item-6.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Laine', 'etat' => 'bon', 'il_y_a' => 10,
                'description' => 'Blazer noir coupe droite, boutons dorés. Idéal pour un entretien ou une soirée.'],
            ['titre' => 'Jean droit bleu clair', 'categorie' => 'Pantalons & jeans', 'image' => 'product-item-7.jpg', 'taille' => 'S', 'genre' => 'femme', 'matiere' => 'Denim', 'etat' => 'tres_bon', 'il_y_a' => 12,
                'description' => 'Jean droit taille haute bleu clair, longueur cheville. Comme neuf.'],
            ['titre' => 'Chemise blanche oversize', 'categorie' => 'Hauts', 'image' => 'product-item-8.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Coton', 'etat' => 'bon', 'il_y_a' => 14,
                'description' => 'Chemise blanche ample à porter ouverte ou nouée. Un petit pli au col.'],
            ['titre' => 'Pantalon beige taille haute', 'categorie' => 'Pantalons & jeans', 'image' => 'product-item-9.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Coton', 'etat' => 'tres_bon', 'il_y_a' => 16,
                'description' => 'Pantalon beige à pinces, taille haute, avec ceinture. Tenue chic et confortable.'],
            ['titre' => 'T-shirt noir à liseré', 'categorie' => 'Hauts', 'image' => 'cat-item1.jpg', 'taille' => 'XL', 'genre' => 'homme', 'matiere' => 'Coton', 'etat' => 'bon', 'il_y_a' => 18,
                'description' => 'T-shirt noir épais avec liseré gris aux épaules. Couleur encore bien noire.'],
            ['titre' => 'Pull blanc texturé', 'categorie' => 'Pulls & gilets', 'image' => 'cat-item2.jpg', 'taille' => 'S', 'genre' => 'femme', 'matiere' => 'Coton', 'etat' => 'neuf', 'il_y_a' => 20,
                'description' => 'Pull blanc à manches longues avec un joli relief, col échancré. Étiquette encore attachée.'],
            ['titre' => 'Trench beige long', 'categorie' => 'Vestes & manteaux', 'image' => 'banner-image-1.jpg', 'taille' => 'L', 'genre' => 'unisexe', 'matiere' => 'Coton', 'etat' => 'tres_bon', 'il_y_a' => 24,
                'description' => 'Trench classique beige, long, avec ceinture. Imperméable léger pour la mi-saison.'],
            ['titre' => 'Manteau à carreaux', 'categorie' => 'Vestes & manteaux', 'image' => 'banner-image-2.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Laine', 'etat' => 'use', 'il_y_a' => 28,
                'description' => 'Manteau long à carreaux marron. Doublure un peu usée mais encore très chaud.'],
            ['titre' => 'Veste en cuir vintage', 'categorie' => 'Vestes & manteaux', 'image' => 'banner-image-4.jpg', 'taille' => 'L', 'genre' => 'homme', 'matiere' => 'Cuir', 'etat' => 'use', 'il_y_a' => 33,
                'description' => 'Veste en cuir vieilli, style années 90. Cuir patiné, fermeture éclair qui fonctionne.'],
            ['titre' => 'Manteau camel à liseré', 'categorie' => 'Vestes & manteaux', 'image' => 'banner-image-5.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Laine', 'etat' => 'bon', 'il_y_a' => 38,
                'description' => 'Manteau camel avec un liseré noir, col chemise. Élégant et chaud.'],
            ['titre' => 'Sweat court marron', 'categorie' => 'Pulls & gilets', 'image' => 'banner-image-6.jpg', 'taille' => 'S', 'genre' => 'femme', 'matiere' => 'Coton', 'etat' => 'bon', 'il_y_a' => 45,
                'description' => 'Sweat court couleur chocolat, manches longues. Doux à l\'intérieur.'],
            ['titre' => 'Robe longue noire', 'categorie' => 'Robes & jupes', 'image' => 'insta-item1.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Polyester', 'etat' => 'tres_bon', 'il_y_a' => 52,
                'description' => 'Robe longue noire à manches longues, volants en bas. Se porte avec des bottines.'],
            ['titre' => 'Chemise rayée bleue', 'categorie' => 'Hauts', 'image' => 'insta-item5.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Coton', 'etat' => 'tres_bon', 'il_y_a' => 60,
                'description' => 'Chemise à rayures bleues et blanches, manches bouffantes. Fraîche et lumineuse.'],
            ['titre' => 'Robe en lin blanche', 'categorie' => 'Robes & jupes', 'image' => 'post-image7.jpg', 'taille' => 'S', 'genre' => 'femme', 'matiere' => 'Lin', 'etat' => 'neuf', 'il_y_a' => 70,
                'description' => 'Robe sans manches en lin blanc, avec un pendentif. Neuve, jamais portée.'],
            ['titre' => 'Lunettes de soleil écaille', 'categorie' => 'Accessoires', 'image' => 'post-image6.jpg', 'taille' => 'M', 'genre' => 'unisexe', 'matiere' => 'Acétate', 'etat' => 'tres_bon', 'il_y_a' => 80,
                'description' => 'Lunettes de soleil à monture écaille, verres sans rayure. Avec leur étui.'],
            ['titre' => 'Vanity en cuir caramel', 'categorie' => 'Accessoires', 'image' => 'cat-item3.jpg', 'taille' => 'M', 'genre' => 'unisexe', 'matiere' => 'Cuir', 'etat' => 'bon', 'il_y_a' => 95,
                'description' => 'Petite trousse rigide en cuir caramel avec dragonne. Parfaite pour les bijoux.'],

            // Vêtements déjà partis : ils comptent dans l'impact écologique.
            ['titre' => 'T-shirt noir imprimé', 'categorie' => 'Hauts', 'image' => 'post-image2.jpg', 'taille' => 'M', 'genre' => 'homme', 'matiere' => 'Coton', 'etat' => 'bon', 'il_y_a' => 110, 'statut' => StatutVetement::Donne,
                'description' => 'T-shirt noir avec petit imprimé sur la poitrine.'],
            ['titre' => 'Robe en lin kaki', 'categorie' => 'Robes & jupes', 'image' => 'post-image9.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Lin', 'etat' => 'use', 'il_y_a' => 130, 'statut' => StatutVetement::Recycle,
                'description' => 'Robe ample en lin kaki, manches 3/4. Tissu un peu délavé.'],

            // À valider par l'admin (dont un doublon : même photo qu'un vêtement déjà en ligne).
            ['titre' => 'Short en jean effiloché', 'categorie' => 'Pantalons & jeans', 'image' => 'insta-item2.jpg', 'taille' => 'S', 'genre' => 'femme', 'matiere' => 'Denim', 'etat' => 'use', 'il_y_a' => 1, 'moderation' => Moderation::EnAttente,
                'description' => 'Short en jean taille haute, bas effiloché. Idéal pour l\'été.'],
            ['titre' => 'Jogging rose pastel', 'categorie' => 'Pantalons & jeans', 'image' => 'insta-item3.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Coton', 'etat' => 'bon', 'il_y_a' => 1, 'moderation' => Moderation::EnAttente,
                'description' => 'Pantalon de jogging rose pastel, élastique à la taille et aux chevilles.'],
            ['titre' => 'Jean clair (déjà déposé ?)', 'categorie' => 'Pantalons & jeans', 'image' => 'product-item-7.jpg', 'taille' => 'S', 'genre' => 'femme', 'matiere' => 'Denim', 'etat' => 'tres_bon', 'il_y_a' => 0, 'moderation' => Moderation::EnAttente,
                'description' => 'Jean droit bleu clair. La photo ressemble à un vêtement déjà en ligne : l\'admin voit l\'alerte doublon.'],
            ['titre' => 'Blazer pied-de-poule', 'categorie' => 'Vestes & manteaux', 'image' => 'insta-item6.jpg', 'taille' => 'M', 'genre' => 'femme', 'matiere' => 'Laine', 'etat' => 'a_reparer', 'il_y_a' => 3, ...$refuse,
                'description' => 'Blazer vert pied-de-poule, un bouton manquant.'],
        ];
    }
}
