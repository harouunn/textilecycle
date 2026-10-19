<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Deux comptes de démonstration (mot de passe : password) :
     *  - test@example.com   : administrateur (back office /admin)
     *  - client@example.com : client (site public)
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->create([
            'name' => 'Client Démo',
            'email' => 'client@example.com',
        ]);
    }
}
