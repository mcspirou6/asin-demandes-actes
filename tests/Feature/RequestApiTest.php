<?php

namespace Tests\Feature;

use App\Enums\ActType;
use App\Enums\RequestStatus;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tests de recette de l'API : socle + règles de gestion + bonus.
 */
class RequestApiTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Création
    // ------------------------------------------------------------------

    public function test_creation_valide_renvoie_201_avec_statut_submitted(): void
    {
        $response = $this->postJson('/api/requests', [
            'npi' => '0123456789',
            'act_type' => 'birth_certificate',
            'copies_count' => 2,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.npi', '0123456789')
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.copies_count', 2);

        // Vérifie que le statut initial est bien forcé par le serveur.
        $this->assertDatabaseHas('requests', [
            'npi' => '0123456789',
            'status' => 'submitted',
        ]);
    }

    public function test_le_client_ne_peut_pas_imposer_le_statut_initial(): void
    {
        $this->postJson('/api/requests', [
            'npi' => '1234567890',
            'act_type' => 'criminal_record',
            'copies_count' => 1,
            'status' => 'approved',
        ])->assertStatus(201)->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('requests', ['npi' => '1234567890', 'status' => 'submitted']);
    }

    public static function npiInvalideProvider(): array
    {
        return [
            'trop court' => ['12345678'],
            'trop long' => ['12345678901'],
            'avec lettres' => ['1234abcde9'],
            'vide' => [''],
            'nombre' => [1234567890.5],
        ];
    }

    #[DataProvider('npiInvalideProvider')]
    public function test_npi_invalide_refuse_422(mixed $npi): void
    {
        $this->postJson('/api/requests', [
            'npi' => $npi,
            'act_type' => 'birth_certificate',
            'copies_count' => 1,
        ])->assertStatus(422);
    }

    public function test_acte_invalide_refuse_422(): void
    {
        $this->postJson('/api/requests', [
            'npi' => '1234567890',
            'act_type' => 'passeport',
            'copies_count' => 1,
        ])->assertStatus(422);
    }

    public static function copiesInvalideProvider(): array
    {
        return [
            'zero' => [0],
            'trop eleve' => [6],
            'negatif' => [-1],
            'decimal' => [2.5],
            'texte' => ['deux'],
        ];
    }

    #[DataProvider('copiesInvalideProvider')]
    public function test_copies_invalide_refuse_422(mixed $copies): void
    {
        $this->postJson('/api/requests', [
            'npi' => '1234567890',
            'act_type' => 'birth_certificate',
            'copies_count' => $copies,
        ])->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Liste
    // ------------------------------------------------------------------

    public function test_liste_triee_du_plus_recent_au_plus_ancien(): void
    {
        $npi = '1234567890';

        // Les deux demandes ont exactement le même created_at : le tri
        // secondaire par id DESC garantit l'ordre attendu.
        $oldest = Request::factory()->npi($npi)->create(['created_at' => now()]);
        $newest = Request::factory()->npi($npi)->create(['created_at' => now()]);

        $response = $this->getJson("/api/users/{$npi}/requests");

        $response->assertStatus(200);
        $ids = array_column($response->json('data'), 'id');
        $this->assertSame([$newest->id, $oldest->id], $ids);
    }

    public function test_liste_n_renvoie_pas_les_demandes_des_autres_npi(): void
    {
        $npi = '1234567890';
        Request::factory()->npi($npi)->create();
        Request::factory()->npi('0987654321')->create();

        $response = $this->getJson("/api/users/{$npi}/requests");
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($npi, $response->json('data.0.npi'));
    }

    public function test_npi_sans_demande_renvoie_liste_vide(): void
    {
        $this->getJson('/api/users/9999999999/requests')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_filtre_par_statut(): void
    {
        $npi = '1234567890';
        Request::factory()->npi($npi)->status(RequestStatus::Submitted)->create();
        $processing = Request::factory()->npi($npi)->status(RequestStatus::Processing)->create();

        $response = $this->getJson("/api/users/{$npi}/requests?status=processing");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($processing->id, $response->json('data.0.id'));
    }

    public function test_filtre_statut_invalide_refuse_422(): void
    {
        Request::factory()->create();

        $this->getJson('/api/users/1234567890/requests?status=annule')
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Cycle de vie
    // ------------------------------------------------------------------

    public function test_submitted_vers_processing(): void
    {
        $request = Request::factory()->create();

        $this->patchJson("/api/requests/{$request->id}/status", ['status' => 'processing'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_processing_vers_approved(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        $this->patchJson("/api/requests/{$request->id}/status", ['status' => 'approved'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_processing_vers_rejected_avec_motif(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        $response = $this->patchJson("/api/requests/{$request->id}/status", [
            'status' => 'rejected',
            'rejection_reason' => 'Informations incorrectes.',
        ]);

        $response->assertStatus(200)->assertJsonPath('data.status', 'rejected');
        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'rejection_reason' => 'Informations incorrectes.',
        ]);
    }

    public static function transitionsInterditesProvider(): array
    {
        return [
            'submitted -> approved' => [RequestStatus::Submitted, 'approved'],
            'submitted -> rejected' => [RequestStatus::Submitted, 'rejected'],
            'approved -> processing' => [RequestStatus::Approved, 'processing'],
            'approved -> rejected' => [RequestStatus::Approved, 'rejected'],
            'rejected -> processing' => [RequestStatus::Rejected, 'processing'],
            'rejected -> approved' => [RequestStatus::Rejected, 'approved'],
        ];
    }

    #[DataProvider('transitionsInterditesProvider')]
    public function test_transition_interdite_refusee_409(RequestStatus $from, string $to): void
    {
        $request = Request::factory()->status($from)->create();

        $response = $this->patchJson("/api/requests/{$request->id}/status", ['status' => $to]);

        $response->assertStatus(409)
            ->assertJsonStructure(['message']);

        // La demande ne doit pas avoir été modifiée.
        $this->assertSame($from->value, $request->fresh()->status->value);
    }

    // ------------------------------------------------------------------
    // Rejet
    // ------------------------------------------------------------------

    public function test_rejet_sans_motif_refuse_422(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        $this->patchJson("/api/requests/{$request->id}/status", ['status' => 'rejected'])
            ->assertStatus(422);
    }

    public function test_rejet_motif_vide_refuse_422(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        $this->patchJson("/api/requests/{$request->id}/status", [
            'status' => 'rejected',
            'rejection_reason' => '   ',
        ])->assertStatus(422);
    }

    public function test_demande_inexistante_renvoie_404(): void
    {
        $this->patchJson('/api/requests/999999/status', ['status' => 'processing'])
            ->assertStatus(404);
    }

    public function test_statut_transmis_invalide_refuse_422(): void
    {
        $request = Request::factory()->create();

        $this->patchJson("/api/requests/{$request->id}/status", ['status' => 'archive'])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Répétition / idempotence
    // ------------------------------------------------------------------

    public function test_repetition_du_meme_appel_refusee_sans_effet_supplementaire(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        // Premier appel : processing -> approved.
        $this->patchJson("/api/requests/{$request->id}/status", ['status' => 'approved'])
            ->assertStatus(200);

        // Répétition du même appel : refusée, l'état final est figé.
        $this->patchJson("/api/requests/{$request->id}/status", ['status' => 'approved'])
            ->assertStatus(409);

        $this->assertSame('approved', $request->fresh()->status->value);
    }

    public function test_double_soumission_cree_deux_demandes_separees(): void
    {
        $payload = [
            'npi' => '0123456789',
            'act_type' => 'birth_certificate',
            'copies_count' => 2,
        ];

        $this->postJson('/api/requests', $payload)->assertStatus(201);
        $this->postJson('/api/requests', $payload)->assertStatus(201);

        // Comportement documenté : sans Idempotency-Key, une double
        // soumission crée deux demandes distinctes (limite assumée,
        // voir README section Idempotence).
        $this->assertDatabaseCount('requests', 2);
    }

    // ------------------------------------------------------------------
    // Bonus : pagination
    // ------------------------------------------------------------------

    public function test_pagination_max_20_par_page(): void
    {
        $npi = '1234567890';
        Request::factory()->count(25)->npi($npi)->create();

        $response = $this->getJson("/api/users/{$npi}/requests?page=1");

        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data'));
        $this->assertSame(25, $response->json('meta.total'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }

    // ------------------------------------------------------------------
    // Bonus : statistiques
    // ------------------------------------------------------------------

    public function test_statistiques_par_statut(): void
    {
        Request::factory()->count(2)->status(RequestStatus::Submitted)->create();
        Request::factory()->status(RequestStatus::Processing)->create();
        Request::factory()->status(RequestStatus::Approved)->create();
        Request::factory()->rejected('Motif test')->create();

        $this->getJson('/api/requests/stats')
            ->assertStatus(200)
            ->assertJson([
                'submitted' => 2,
                'processing' => 1,
                'approved' => 1,
                'rejected' => 1,
            ]);
    }
}
