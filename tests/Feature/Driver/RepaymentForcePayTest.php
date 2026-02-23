<?php

namespace Tests\Feature\Driver;

use App\Models\Lease;
use App\Models\Repayment;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RepaymentForcePayTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_checkout_requires_force_now_intent(): void
    {
        $user = $this->makeDriverUser();
        $repayment = $this->makeRepaymentForUser($user);

        $response = $this->actingAs($user)->post(route('payments.paystack.repayment', $repayment), [
            'payment_method' => 'card',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Manual checkout is disabled unless you explicitly choose Force Pay Now.');
    }

    public function test_force_now_initializes_paystack_and_redirects(): void
    {
        $user = $this->makeDriverUser();
        $repayment = $this->makeRepaymentForUser($user);

        $mock = \Mockery::mock(PaystackService::class);
        $mock->shouldReceive('initializeRepaymentCheckout')
            ->once()
            ->andReturn([
                'reference' => 'RPY-TEST-001',
                'authorization_url' => 'https://paystack.test/checkout',
                'access_code' => 'ACC_TEST',
            ]);
        $this->app->instance(PaystackService::class, $mock);

        $response = $this->actingAs($user)->post(route('payments.paystack.repayment', $repayment), [
            'payment_intent' => 'force_now',
            'payment_method' => 'card',
        ]);

        $response->assertRedirect('https://paystack.test/checkout');
    }

    private function makeDriverUser(): User
    {
        Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('driver');

        return $user;
    }

    private function makeRepaymentForUser(User $user): Repayment
    {
        $lease = Lease::create([
            'user_id' => $user->id,
            'principal_amount' => 1000,
            'interest_rate' => 5,
            'interest_amount' => 50,
            'total_amount' => 1050,
            'term_days' => 30,
            'daily_repayment' => 35,
            'status' => 'active',
            'issued_at' => now(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        return Repayment::create([
            'lease_id' => $lease->id,
            'user_id' => $user->id,
            'amount' => 35,
            'due_date' => now()->toDateString(),
            'status' => 'pending',
        ]);
    }
}

