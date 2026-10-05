<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_the_dashboard(): void
    {
        $user = User::factory()->create(['name' => 'Amina Trabelsi']);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertSeeText('Bonjour Amina Trabelsi');
        $response->assertSeeText('Le cycle TexTileCycle');
    }
}
