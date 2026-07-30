<?php

namespace Tests\Feature\Api;

use App\Models\CreditLimit;
use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\Lease;
use App\Models\Repayment;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RepaymentFlowRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_make_payment_only_deducts_amount_applied_to_installments(): void
    {
        $user = $this->createUserWithCredit(balance: 500, outstanding: 200, creditUsed: 200);
        $lease = $this->createVisibleLease($user, totalAmount: 200);
        $firstRepayment = $this->createRepayment($lease, $user, 100, now()->subDay()->toDateString());
        $secondRepayment = $this->createRepayment($lease, $user, 100, now()->addDay()->toDateString());

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/repayments/make-payment', [
            'amount' => 150,
            'payment_method' => 'wallet',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame(100.0, (float) $response->json('data.total_paid'));
        $this->assertSame(50.0, (float) $response->json('data.unapplied_amount'));
        $this->assertCount(1, (array) $response->json('data.processed_repayments'));

        $firstRepayment->refresh();
        $secondRepayment->refresh();
        $user->wallet->refresh();
        $user->creditLimit->refresh();
        $lease->refresh();

        $this->assertSame('paid', $firstRepayment->status);
        $this->assertSame('wallet', $firstRepayment->payment_method);
        $this->assertSame('pending', $secondRepayment->status);
        $this->assertSame(400.0, (float) $user->wallet->balance);
        $this->assertSame(100.0, (float) $user->wallet->outstanding_balance);
        $this->assertSame(100.0, (float) $user->wallet->total_repayments);
        $this->assertSame(100.0, (float) $user->creditLimit->used);
        $this->assertSame('active', $lease->status);
    }

    public function test_paystack_verify_rejects_non_repayment_transactions(): void
    {
        $user = $this->createUserWithCredit(balance: 0, outstanding: 100, creditUsed: 100);
        $lease = $this->createVisibleLease($user, totalAmount: 100);
        $repayment = $this->createRepayment($lease, $user, 100, now()->toDateString());

        Sanctum::actingAs($user);

        $mock = \Mockery::mock(PaystackService::class);
        $mock->shouldReceive('verifyTransaction')
            ->once()
            ->with('AUTOSETUP-REF')
            ->andReturn([
                'reference' => 'AUTOSETUP-REF',
                'amount' => 10000,
                'status' => 'success',
                'metadata' => [
                    'scope' => 'autopay_setup',
                    'user_id' => $user->id,
                ],
            ]);
        $this->app->instance(PaystackService::class, $mock);

        $response = $this->postJson('/api/v1/repayments/paystack/verify', [
            'reference' => 'AUTOSETUP-REF',
            'repayment_id' => $repayment->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $repayment->refresh();
        $user->wallet->refresh();
        $user->creditLimit->refresh();
        $lease->refresh();

        $this->assertSame('pending', $repayment->status);
        $this->assertSame(100.0, (float) $user->wallet->outstanding_balance);
        $this->assertSame(0.0, (float) $user->wallet->total_repayments);
        $this->assertSame(100.0, (float) $user->creditLimit->used);
        $this->assertSame('active', $lease->status);
    }

    public function test_paystack_verify_settles_repayment_and_updates_balances(): void
    {
        $user = $this->createUserWithCredit(balance: 0, outstanding: 100, creditUsed: 100);
        $lease = $this->createVisibleLease($user, totalAmount: 100);
        $repayment = $this->createRepayment($lease, $user, 100, now()->toDateString());

        Sanctum::actingAs($user);

        $mock = \Mockery::mock(PaystackService::class);
        $mock->shouldReceive('verifyTransaction')
            ->once()
            ->with('RPY-REF-001')
            ->andReturn([
                'reference' => 'RPY-REF-001',
                'amount' => 10000,
                'status' => 'success',
                'metadata' => [
                    'scope' => 'repayment',
                    'repayment_id' => $repayment->id,
                    'lease_id' => $lease->id,
                    'user_id' => $user->id,
                ],
                'authorization' => [
                    'authorization_code' => 'AUTH_test_123',
                ],
                'customer' => [
                    'email' => $user->email,
                    'customer_code' => 'CUS_test_123',
                ],
            ]);
        $this->app->instance(PaystackService::class, $mock);

        $response = $this->postJson('/api/v1/repayments/paystack/verify', [
            'reference' => 'RPY-REF-001',
            'repayment_id' => $repayment->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reference', 'RPY-REF-001');

        $repayment->refresh();
        $user->wallet->refresh();
        $user->creditLimit->refresh();
        $lease->refresh();

        $this->assertSame('paid', $repayment->status);
        $this->assertSame('paystack_card', $repayment->payment_method);
        $this->assertSame(0.0, (float) $user->wallet->outstanding_balance);
        $this->assertSame(100.0, (float) $user->wallet->total_repayments);
        $this->assertSame(0.0, (float) $user->creditLimit->used);
        $this->assertSame('completed', $lease->status);
        $this->assertFalse((bool) $user->autopay_enabled);
        $this->assertEmpty($user->autopay_token);
    }

    public function test_repayment_due_today_is_not_marked_overdue(): void
    {
        $user = $this->createUserWithCredit(balance: 0, outstanding: 50, creditUsed: 50);
        $lease = $this->createVisibleLease($user, totalAmount: 50);
        $repayment = $this->createRepayment($lease, $user, 50, now()->toDateString());

        $repayment->refresh();

        $this->assertFalse($repayment->is_overdue);
        $this->assertFalse(
            Repayment::query()
                ->whereKey($repayment->id)
                ->overdue()
                ->exists()
        );
    }

    private function createUserWithCredit(float $balance, float $outstanding, float $creditUsed): User
    {
        $user = User::factory()->create();

        Wallet::create([
            'user_id' => $user->id,
            'balance' => $balance,
            'outstanding_balance' => $outstanding,
            'total_credit_used' => $creditUsed,
            'total_repayments' => 0,
            'currency' => 'ZAR',
        ]);

        CreditLimit::create([
            'user_id' => $user->id,
            'limit' => max($creditUsed, 1000),
            'used' => $creditUsed,
            'review_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    private function createVisibleLease(User $user, float $totalAmount): Lease
    {
        $station = FuelStation::create([
            'name' => 'Regression Test Station',
            'company' => 'Bwiser',
            'license_number' => 'LIC-' . uniqid(),
            'address' => '1 Test Street',
            'city' => 'Johannesburg',
            'country' => 'South Africa',
            'contact_person' => 'Test Owner',
            'contact_phone' => '27110000000',
            'contact_email' => 'station@example.com',
            'status' => 'active',
        ]);

        $lease = Lease::create([
            'user_id' => $user->id,
            'principal_amount' => $totalAmount,
            'interest_rate' => 0,
            'interest_amount' => 0,
            'total_amount' => $totalAmount,
            'term_days' => 30,
            'daily_repayment' => $totalAmount,
            'status' => 'active',
            'issued_at' => now(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        FuelVoucher::create([
            'user_id' => $user->id,
            'fuel_station_id' => $station->id,
            'lease_id' => $lease->id,
            'amount' => $totalAmount,
            'liters' => 10,
            'fuel_type' => 'petrol',
            'status' => 'redeemed',
            'issued_at' => now()->subDay(),
            'redeemed_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        return $lease;
    }

    private function createRepayment(Lease $lease, User $user, float $amount, string $dueDate): Repayment
    {
        return Repayment::create([
            'lease_id' => $lease->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'due_date' => $dueDate,
            'status' => 'pending',
        ]);
    }
}
