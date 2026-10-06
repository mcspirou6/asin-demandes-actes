<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests de l'assistant conversationnel (réponses prédéfinies).
 * L'endpoint ne dépend pas de la base : pas besoin de RefreshDatabase.
 */
class ChatbotTest extends TestCase
{
    public function test_question_connue_renvoie_reponse_predéfinie(): void
    {
        $response = $this->getJson('/api/chatbot?q=' . rawurlencode('Comment suivre ma demande ?'));

        $response->assertStatus(200)
            ->assertJsonPath('matched', true)
            ->assertJsonStructure(['matched', 'answer']);
    }

    public function test_question_avec_accents_est_normaisee(): void
    {
        // "résidence" avec accents doit correspondre au mot-clé "residence".
        $response = $this->getJson('/api/chatbot?q=' . rawurlencode('Puis-je avoir un certificat de résidence ?'));

        $response->assertStatus(200)
            ->assertJsonPath('matched', true);
    }

    public function test_question_inconnue_renvoie_reponse_de_repli(): void
    {
        $response = $this->getJson('/api/chatbot?q=' . rawurlencode('xyzzvfghj'));

        $response->assertStatus(200)
            ->assertJsonPath('matched', false)
            ->assertJsonPath('answer', config('chatbot.fallback'));
    }

    public function test_question_absente_refusee_422(): void
    {
        $this->getJson('/api/chatbot')->assertStatus(422);
    }

    public function test_question_trop_courte_refusee_422(): void
    {
        $this->getJson('/api/chatbot?q=a')->assertStatus(422);
    }
}
