<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreditEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_record_credit_consent_for_self(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/credit/consents', [
            'source' => 'manual',
            'scope' => 'credit_scoring_profile_access',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.source', 'manual');
    }

    public function test_simulate_purchase_validates_amount_with_form_request(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/credit/simulate-purchase', [
            'amount' => 0,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_user_can_score_decide_and_fetch_explanation_for_self(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $scoreResponse = $this->postJson('/api/v1/credit/score');
        $scoreResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['id', 'score', 'band', 'version'],
            ]);

        $decisionResponse = $this->postJson('/api/v1/credit/decision', [
            'requested_amount' => 250,
            'application_type' => 'voucher_bnpl',
        ]);

        $decisionResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['id', 'decision', 'score', 'application_type'],
            ]);

        $decisionId = (int) $decisionResponse->json('data.id');

        $explanationResponse = $this->getJson("/api/v1/credit/decision/{$decisionId}/explanation");
        $explanationResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.decision_id', $decisionId);
    }

    public function test_non_admin_cannot_score_another_user_profile(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        Sanctum::actingAs($actor);

        $response = $this->postJson("/api/v1/credit/score/{$target->id}");

        $response
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_user_cannot_view_another_users_decision_explanation(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $decisionId = (int) $this->postJson('/api/v1/credit/decision', [
            'requested_amount' => 300,
        ])->json('data.id');

        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/credit/decision/{$decisionId}/explanation");
        $response
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }
}
