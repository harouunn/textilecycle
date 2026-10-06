<?php

namespace Tests\Feature\Ateliers\Admin;

use App\Models\Atelier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtelierControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.ateliers.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_workshops(): void
    {
        Atelier::factory()->create(['nom' => 'Fil d\'Or']);

        $response = $this->actingAs(User::factory()->create())->get(route('admin.ateliers.index'));

        $response->assertOk();
        $response->assertSeeText('Fil d\'Or');
    }

    public function test_admin_creates_a_workshop(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.ateliers.store'), [
            ...$this->payload(),
            'actif' => '1',
        ]);

        $response->assertRedirect(route('admin.ateliers.index'));
        $this->assertTrue(Atelier::query()->sole()->actif);
    }

    public function test_unchecking_active_deactivates_the_workshop(): void
    {
        $atelier = Atelier::factory()->create(['actif' => true]);

        // Une case décochée n'est pas envoyée par le navigateur : « actif » est absent.
        $this->actingAs(User::factory()->create())->put(route('admin.ateliers.update', $atelier), $this->payload());

        $this->assertFalse($atelier->fresh()->actif);
    }

    public function test_create_requires_name_address_city_and_postcode(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.ateliers.store'), []);

        $response->assertSessionHasErrors(['nom', 'adresse', 'ville', 'code_postal']);
        $this->assertDatabaseCount('ateliers', 0);
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'nom' => 'Fil d\'Or',
            'adresse' => '12 rue de Marseille',
            'ville' => 'Tunis',
            'code_postal' => '1000',
        ];
    }
}
