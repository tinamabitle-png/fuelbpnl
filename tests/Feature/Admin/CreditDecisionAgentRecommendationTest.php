<?php

namespace Tests\Feature\Admin;

use App\Models\CreditAuditLog;
use App\Models\CreditDecision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditDecisionAgentRecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_employee_can_generate_credit_agent_recommendation(): void
    {
        $employeeRole = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        $employee = User::factory()->create();
        $employee->assignRole($employeeRole);

        $applicant = User::factory()->create();

        $decision = CreditDecision::create([
            'user_id' => $applicant->id,
            'decision' => 'review',
            'application_type' => 'voucher_bnpl',
            'model_version' => 'rules-v1',
            'policy_version' => 'policy-v1',
            'source' => 'internal_credit_engine',
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($employee)->post(
            route('admin.credit-decisions.agent-recommendation', $decision)
        );

        $response
            ->assertRedirect(route('admin.credit-decisions.show', $decision));

        $decision->refresh();

        $this->assertIsArray($decision->explanation_json);
        $this->assertSame(
            'credit_analyst_agent',
            data_get($decision->explanation_json, 'agent_recommendation.agent.name')
        );
        $this->assertNotNull(
            data_get($decision->explanation_json, 'agent_recommendation.recommendation.decision')
        );

        $this->assertDatabaseHas((new CreditAuditLog())->getTable(), [
            'action' => 'credit_agent_recommendation_generated',
            'entity_type' => CreditDecision::class,
            'entity_id' => $decision->id,
            'actor_id' => $employee->id,
        ]);
    }

    public function test_auditor_cannot_generate_credit_agent_recommendation(): void
    {
        $auditorRole = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);

        $auditor = User::factory()->create();
        $auditor->assignRole($auditorRole);

        $applicant = User::factory()->create();

        $decision = CreditDecision::create([
            'user_id' => $applicant->id,
            'decision' => 'approve',
            'application_type' => 'voucher_bnpl',
            'model_version' => 'rules-v1',
            'policy_version' => 'policy-v1',
            'source' => 'internal_credit_engine',
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($auditor)->post(
            route('admin.credit-decisions.agent-recommendation', $decision)
        );

        $response->assertForbidden();
    }
}
