<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use App\Services\Dons\DonWorkflowService;
use App\Services\Dons\RecuDon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecuDonTest extends TestCase
{
    use RefreshDatabase;

    private User $donateur;

    private User $responsable;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->donateur = User::factory()->create(['name' => 'Amel Ben Salah']);
        $this->responsable = User::factory()->create();
    }

    private function donRecu(): Don
    {
        $don = Don::factory()->statut(DonStatut::Planifie)->create([
            'user_id' => $this->donateur->id,
            'association_id' => Association::factory()->gereePar($this->responsable)->create(['nom' => 'Croissant Rouge'])->id,
            'poids_kg' => 4.5,
        ]);

        return app(DonWorkflowService::class)->transition($don, DonStatut::Recu, null, $this->responsable);
    }

    public function test_receiving_a_donation_assigns_a_reference_and_stores_the_pdf(): void
    {
        $don = $this->donRecu();

        $this->assertSame(sprintf('DON-%d-%06d', now()->year, $don->id), $don->reference);
        $this->assertMatchesRegularExpression('/^DON-\d{4}-\d{6}$/', $don->fresh()->reference);
        Storage::disk('local')->assertExists(app(RecuDon::class)->chemin($don));

        // La référence ne change plus, même quand le don est ensuite distribué.
        app(DonWorkflowService::class)->transition($don, DonStatut::Distribue, null, $this->responsable);
        $this->assertSame($don->reference, $don->fresh()->reference);
    }

    public function test_donor_downloads_the_pdf_receipt(): void
    {
        $don = $this->donRecu();

        $reponse = $this->actingAs($this->donateur)->get(route('dons.mes-dons.recu', $don));

        $reponse->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload("recu-{$don->reference}.pdf");
        $this->assertStringStartsWith('%PDF', $reponse->getContent());

        $this->actingAs($this->donateur)->get(route('dons.mes-dons'))->assertSee(route('dons.mes-dons.recu', $don));
    }

    public function test_receipt_is_regenerated_if_the_file_is_missing(): void
    {
        $don = $this->donRecu();
        Storage::disk('local')->delete(app(RecuDon::class)->chemin($don));

        $this->actingAs($this->donateur)->get(route('dons.mes-dons.recu', $don))->assertOk();
        Storage::disk('local')->assertExists(app(RecuDon::class)->chemin($don));
    }

    public function test_receipt_access_rules(): void
    {
        $don = $this->donRecu();
        $url = route('dons.mes-dons.recu', $don);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs($this->responsable)->get($url)->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
    }

    public function test_no_receipt_before_the_donation_is_received(): void
    {
        $don = Don::factory()->statut(DonStatut::Accepte)->create(['user_id' => $this->donateur->id]);

        $this->actingAs($this->donateur)->get(route('dons.mes-dons.recu', $don))->assertNotFound();
        $this->actingAs($this->donateur)->get(route('dons.mes-dons'))->assertDontSee(route('dons.mes-dons.recu', $don));
    }

    public function test_public_verification_page_confirms_a_receipt_with_minimal_data(): void
    {
        $don = $this->donRecu();

        $this->get(route('dons.verifier', $don->reference))
            ->assertOk()
            ->assertSeeText(['Reçu authentique', $don->reference, 'Croissant Rouge', 'Amel'])
            ->assertDontSeeText(['Ben Salah', $this->donateur->email]);
    }

    public function test_unknown_or_malformed_references(): void
    {
        $this->get(route('dons.verifier', 'DON-2026-999999'))->assertOk()->assertSeeText('Reçu introuvable');
        $this->get('/dons/verifier/nimporte-quoi')->assertNotFound();
    }

    public function test_qr_code_points_to_the_verification_page(): void
    {
        $don = $this->donRecu();
        $qr = app(RecuDon::class)->qrDataUri(route('dons.verifier', $don->reference));

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $qr);
        $this->assertStringContainsString('<svg', base64_decode(substr($qr, strlen('data:image/svg+xml;base64,'))));
    }
}
