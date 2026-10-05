<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_and_register_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertSeeText('Donnez une seconde vie à vos vêtements');
        $response->assertSee(route('login'));
        $response->assertSee(route('register'));
        $response->assertDontSeeText('Déconnexion');
    }

    public function test_authenticated_user_sees_their_name_and_logout_button(): void
    {
        $user = User::factory()->create(['name' => 'Amina Trabelsi']);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertSeeText('Amina Trabelsi');
        $response->assertSeeText('Déconnexion');
        $response->assertSee(route('logout'));
        $response->assertDontSee(route('register'));
    }
}
