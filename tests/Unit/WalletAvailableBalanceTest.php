<?php

namespace Tests\Unit;

use App\Models\FuelStation;
use App\Models\FuelVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletAvailableBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_available_balance_excludes_wallet_funded_active_vouchers(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet()->create([
            'balance' => 1000,
            'outstanding_balance' => 0,
            'currency' => 'ZAR',
        ]);

        $station = FuelStation::create([
            'name' => 'Test Station',
            'company' => 'TestCo',
            'license_number' => 'LIC-' . uniqid(),
            'address' => '1 Test Road',
            'city' => 'Johannesburg',
            'country' => 'South Africa',
            'contact_person' => 'Tester',
            'contact_phone' => '0123456789',
            'status' => 'active',
            'wallet_balance' => 5000,
            'total_settlements' => 0,
        ]);

        FuelVoucher::create([
            'user_id' => $user->id,
            'fuel_station_id' => $station->id,
            'lease_id' => null,
            'funding_source' => 'wallet',
            'amount' => 500,
            'liters' => 2.500,
            'fuel_type' => 'petrol',
            'status' => 'issued',
            'expires_at' => now()->addHours(24),
        ]);

        $wallet = $wallet->fresh();
        $this->assertSame(500.0, (float) $wallet->reserved_voucher_balance);
        $this->assertSame(500.0, (float) $wallet->available_balance);
    }

    public function test_wallet_funded_voucher_debits_wallet_on_redeem_and_releases_reservation(): void
    {
        $user = User::factory()->create();
        $wallet = $user->wallet()->create([
            'balance' => 1000,
            'outstanding_balance' => 0,
            'currency' => 'ZAR',
        ]);

        $station = FuelStation::create([
            'name' => 'Test Station',
            'company' => 'TestCo',
            'license_number' => 'LIC-' . uniqid(),
            'address' => '1 Test Road',
            'city' => 'Johannesburg',
            'country' => 'South Africa',
            'contact_person' => 'Tester',
            'contact_phone' => '0123456789',
            'status' => 'active',
            'wallet_balance' => 5000,
            'total_settlements' => 0,
        ]);

        $voucher = FuelVoucher::create([
            'user_id' => $user->id,
            'fuel_station_id' => $station->id,
            'lease_id' => null,
            'funding_source' => 'wallet',
            'amount' => 500,
            'liters' => 2.500,
            'fuel_type' => 'petrol',
            'status' => 'approved',
            'expires_at' => now()->addHours(24),
            'issued_at' => now()->subMinute(),
        ]);

        $this->assertSame(500.0, (float) $wallet->fresh()->available_balance);

        $voucher->redeem();

        $voucher = $voucher->fresh();
        $this->assertSame('redeemed', $voucher->status);

        $wallet = $wallet->fresh();
        $this->assertSame(500.0, (float) $wallet->balance);
        $this->assertSame(0.0, (float) $wallet->reserved_voucher_balance);
        $this->assertSame(0.0, (float) $wallet->allocated_card_balance);
        $this->assertSame(500.0, (float) $wallet->available_balance);

        $station = $station->fresh();
        $this->assertSame(4500.0, (float) $station->wallet_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 500.00,
            'status' => 'completed',
        ]);
    }
}
