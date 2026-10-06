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

    /**
     * Simule une session agent connectée : les endpoints de traitement
     * (PATCH statut) sont réservés à l'agent.
     */
    protected function asAdmin(): static
    {
        return $this->withSession(['admin_authenticated' => true]);
    }

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
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.copies_count', 2);

        // Un code de suivi est remis à l'usager (format ASIN-XXXXXX).
        $this->assertMatchesRegularExpression(
            '/^ASIN-[A-Z0-9]{6}$/',
            $response->json('data.tracking_code')
        );

        // Vérifie que le statut initial est bien forcé par le serveur.
        $this->assertDatabaseHas('requests', [
            'npi' => '0123456789',
            'status' => 'submitted',
        ]);
    }

    public function test_le_code_de_suivi_est_unique(): void
    {
        $this->postJson('/api/requests', [
            'npi' => '1234567890', 'act_type' => 'birth_certificate', 'copies_count' => 1,
        ]);
        $this->postJson('/api/requests', [
            'npi' => '1234567890', 'act_type' => 'birth_certificate', 'copies_count' => 1,
        ]);

        $codes = Request::query()->pluck('tracking_code');
        $this->assertSame($codes->count(), $codes->unique()->count());
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
    // Suivi par code de suivi (espace usager)
    // ------------------------------------------------------------------

    public function test_suivi_par_code_de_suivi(): void
    {
        $request = Request::factory()->create();

        $this->getJson("/api/requests/track/{$request->tracking_code}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $request->id)
            ->assertJsonPath('data.tracking_code', $request->tracking_code)
            ->assertJsonMissingPath('data.npi');
    }

    public function test_suivi_code_inconnu_renvoie_404(): void
    {
        $this->getJson('/api/requests/track/ASIN-ZZZZZZ')
            ->assertStatus(404);
    }

    public function test_depot_traitement_agent_et_suivi_usager_sont_synchronises(): void
    {
        $created = $this->postJson('/api/requests', [
            'npi' => '0123456789',
            'act_type' => 'birth_certificate',
            'copies_count' => 2,
        ])->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonMissingPath('data.npi');

        $code = $created->json('data.tracking_code');
        $id = $created->json('data.id');

        $this->postJson('/api/admin/login', [
            'email' => 'agent@asin.bj',
            'password' => 'demo1234',
        ])->assertOk();

        $this->getJson('/api/admin/requests')
            ->assertOk()
            ->assertJsonFragment(['tracking_code' => $code]);

        $this->patchJson("/api/requests/{$id}/status", ['status' => 'processing'])
            ->assertOk()
            ->assertJsonPath('data.status', 'processing');

        $this->postJson('/api/admin/logout')->assertOk();

        $this->getJson("/api/requests/track/{$code}")
            ->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonMissingPath('data.npi');
    }

    // ------------------------------------------------------------------
    // Cycle de vie
    // ------------------------------------------------------------------

    public function test_submitted_vers_processing(): void
    {
        $request = Request::factory()->create();

        $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", ['status' => 'processing'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_processing_vers_approved(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", ['status' => 'approved'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_processing_vers_rejected_avec_motif(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        $response = $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", [
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

        $response = $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", ['status' => $to]);

        $response->assertStatus(409)
            ->assertJsonStructure(['message']);

        // La demande ne doit pas avoir été modifiée.
        $this->assertSame($from->value, $request->fresh()->status->value);
    }

    public function test_transition_sans_agent_connecte_refusee_401(): void
    {
        $request = Request::factory()->create();

        $this->patchJson("/api/requests/{$request->id}/status", ['status' => 'processing'])
            ->assertStatus(401);

        $this->assertSame('submitted', $request->fresh()->status->value);
    }

    // ------------------------------------------------------------------
    // Rejet
    // ------------------------------------------------------------------

    public function test_rejet_sans_motif_refuse_422(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", ['status' => 'rejected'])
            ->assertStatus(422);
    }

    public function test_rejet_motif_vide_refuse_422(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", [
            'status' => 'rejected',
            'rejection_reason' => '   ',
        ])->assertStatus(422);
    }

    public function test_demande_inexistante_renvoie_404(): void
    {
        $this->asAdmin()->patchJson('/api/requests/999999/status', ['status' => 'processing'])
            ->assertStatus(404);
    }

    public function test_statut_transmis_invalide_refuse_422(): void
    {
        $request = Request::factory()->create();

        $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", ['status' => 'archive'])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Répétition / idempotence
    // ------------------------------------------------------------------

    public function test_repetition_du_meme_appel_refusee_sans_effet_supplementaire(): void
    {
        $request = Request::factory()->status(RequestStatus::Processing)->create();

        // Premier appel : processing -> approved.
        $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", ['status' => 'approved'])
            ->assertStatus(200);

        // Répétition du même appel : refusée, l'état final est figé.
        $this->asAdmin()->patchJson("/api/requests/{$request->id}/status", ['status' => 'approved'])
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

    public function test_liste_par_npi_et_statistiques_ne_sont_plus_accessibles_publiquement(): void
    {
        $this->getJson('/api/users/0123456789/requests')->assertNotFound();
        $this->getJson('/api/requests/stats')->assertNotFound();
    }
}
