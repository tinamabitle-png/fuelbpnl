<?php

namespace Tests\Unit;

use App\Services\RepaymentPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepaymentPolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_defaults_when_no_settings_exist(): void
    {
        $service = app(RepaymentPolicyService::class);
        $policy = $service->get();

        $this->assertSame(3, $policy['autopay_max_retries']);
        $this->assertSame(24, $policy['autopay_retry_hours']);
        $this->assertSame(2, $policy['autopay_grace_days']);
        $this->assertSame(5, $policy['autopay_auto_disable_threshold']);
    }

    public function test_updates_and_reads_policy_values(): void
    {
        $service = app(RepaymentPolicyService::class);
        $service->update([
            'autopay_max_retries' => 6,
            'autopay_retry_hours' => 12,
            'autopay_grace_days' => 1,
            'autopay_auto_disable_threshold' => 8,
        ]);

        $policy = $service->get();
        $this->assertSame(6, $policy['autopay_max_retries']);
        $this->assertSame(12, $policy['autopay_retry_hours']);
        $this->assertSame(1, $policy['autopay_grace_days']);
        $this->assertSame(8, $policy['autopay_auto_disable_threshold']);

        $this->assertDatabaseHas('settings', ['key' => 'autopay_max_retries', 'value' => '6']);
        $this->assertDatabaseHas('settings', ['key' => 'autopay_retry_hours', 'value' => '12']);
        $this->assertDatabaseHas('settings', ['key' => 'autopay_grace_days', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'autopay_auto_disable_threshold', 'value' => '8']);
    }
}
