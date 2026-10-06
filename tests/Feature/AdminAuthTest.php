<?php

namespace Tests\Feature;

use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de l'espace agent : connexion, protection des endpoints,
 * liste globale filtrable.
 */
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_connexion_avec_identifiants_valides(): void
    {
        $this->postJson('/api/admin/login', [
            'email' => 'agent@asin.bj',
            'password' => 'demo1234',
        ])->assertStatus(200);

        // La session ouverte donne accès à la vue agent.
        $this->getJson('/api/admin/me')
            ->assertStatus(200)
            ->assertJsonPath('authenticated', true);

        $this->getJson('/api/admin/requests')->assertStatus(200);
    }

    public function test_connexion_avec_identifiants_invalides_refusee_422(): void
    {
        $this->postJson('/api/admin/login', [
            'email' => 'agent@asin.bj',
            'password' => 'mauvais-mot-de-passe',
        ])->assertStatus(422);

        $this->getJson('/api/admin/requests')->assertStatus(401);
    }

    public function test_liste_agent_sans_session_refusee_401(): void
    {
        $this->getJson('/api/admin/requests')->assertStatus(401);
    }

    public function test_deconnexion_revoque_l_acces(): void
    {
        $this->withSession(['admin_authenticated' => true]);

        $this->postJson('/api/admin/logout')->assertStatus(200);
        $this->getJson('/api/admin/requests')->assertStatus(401);
    }

    public function test_vue_agent_liste_toutes_les_demandes(): void
    {
        Request::factory()->count(3)->create();
        Request::factory()->npi('0987654321')->create();

        $this->asAdmin();

        // 4 demandes au total, sans filtre.
        $response = $this->withSession(['admin_authenticated' => true])
            ->getJson('/api/admin/requests');
        $response->assertStatus(200);
        $this->assertSame(4, $response->json('meta.total'));

        // Filtre par NPI.
        $response = $this->withSession(['admin_authenticated' => true])
            ->getJson('/api/admin/requests?npi=0987654321');
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('0987654321', $response->json('data.0.npi'));
    }

    protected function asAdmin(): void
    {
        // Les appels de ce test utilisent withSession directement.
    }
}
