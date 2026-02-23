<?php

namespace Database\Seeders;

use App\Models\FuelStation;
use App\Models\Settlement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RetailBrandPayoutEngineSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'brand' => 'Shell',
                'station_name' => 'Shell Sandton Drive',
                'license' => 'SA-SHELL-001',
                'city' => 'Johannesburg',
                'address' => 'Sandton Drive, Sandton, Johannesburg',
                'contact_person' => 'Lebo Nkosi',
                'contact_phone' => '27110111001',
                'contact_email' => 'sandton@shell.co.za',
                'bank_name' => 'Standard Bank',
                'bank_code' => '051001',
                'account_name' => 'Shell Sandton Ops',
                'account_number' => '10234567890',
                'branch_code' => '051001',
                'amount' => 128450.75,
                'voucher_count' => 38,
                'settlement_ref' => 'SA-BRAND-SHELL-0001',
            ],
            [
                'brand' => 'BP',
                'station_name' => 'BP Midrand Hub',
                'license' => 'SA-BP-001',
                'city' => 'Midrand',
                'address' => 'Old Pretoria Road, Midrand',
                'contact_person' => 'Sipho Dlamini',
                'contact_phone' => '27110111002',
                'contact_email' => 'midrand@bp.co.za',
                'bank_name' => 'Nedbank',
                'bank_code' => '198765',
                'account_name' => 'BP Midrand Hub',
                'account_number' => '22345678901',
                'branch_code' => '198765',
                'amount' => 96420.40,
                'voucher_count' => 27,
                'settlement_ref' => 'SA-BRAND-BP-0002',
            ],
            [
                'brand' => 'Engen',
                'station_name' => 'Engen Durban North',
                'license' => 'SA-ENGEN-001',
                'city' => 'Durban',
                'address' => 'Umhlanga Rocks Drive, Durban North',
                'contact_person' => 'Nandi Mthembu',
                'contact_phone' => '27110111003',
                'contact_email' => 'durban-north@engen.co.za',
                'bank_name' => 'Absa',
                'bank_code' => '632005',
                'account_name' => 'Engen Durban North',
                'account_number' => '33456789012',
                'branch_code' => '632005',
                'amount' => 112980.00,
                'voucher_count' => 31,
                'settlement_ref' => 'SA-BRAND-ENGEN-0003',
            ],
            [
                'brand' => 'Sasol',
                'station_name' => 'Sasol Pretoria East',
                'license' => 'SA-SASOL-001',
                'city' => 'Pretoria',
                'address' => 'Atterbury Road, Pretoria East',
                'contact_person' => 'Kabelo Moagi',
                'contact_phone' => '27110111004',
                'contact_email' => 'pretoria-east@sasol.co.za',
                'bank_name' => 'FNB',
                'bank_code' => '250655',
                'account_name' => 'Sasol Pretoria East',
                'account_number' => '44567890123',
                'branch_code' => '250655',
                'amount' => 87560.20,
                'voucher_count' => 24,
                'settlement_ref' => 'SA-BRAND-SASOL-0004',
            ],
            [
                'brand' => 'TotalEnergies',
                'station_name' => 'TotalEnergies Cape Town CBD',
                'license' => 'SA-TOTAL-001',
                'city' => 'Cape Town',
                'address' => 'Buitengracht Street, Cape Town CBD',
                'contact_person' => 'Anele Jacobs',
                'contact_phone' => '27110111005',
                'contact_email' => 'cpt-cbd@totalenergies.co.za',
                'bank_name' => 'Capitec',
                'bank_code' => '470010',
                'account_name' => 'TotalEnergies CPT CBD',
                'account_number' => '55678901234',
                'branch_code' => '470010',
                'amount' => 104315.95,
                'voucher_count' => 29,
                'settlement_ref' => 'SA-BRAND-TOTAL-0005',
            ],
        ];

        foreach ($rows as $index => $row) {
            $station = FuelStation::updateOrCreate(
                ['license_number' => $row['license']],
                [
                    'name' => $row['station_name'],
                    'company' => $row['brand'],
                    'address' => $row['address'],
                    'city' => $row['city'],
                    'country' => 'South Africa',
                    'contact_person' => $row['contact_person'],
                    'contact_phone' => $row['contact_phone'],
                    'contact_email' => $row['contact_email'],
                    'status' => 'active',
                    'payout_method' => 'bank_transfer',
                    'payout_bank_name' => $row['bank_name'],
                    'payout_bank_code' => $row['bank_code'],
                    'payout_account_name' => $row['account_name'],
                    'payout_account_number' => $row['account_number'],
                    'payout_branch_code' => $row['branch_code'],
                    'payout_reference' => 'BWISER-' . strtoupper($row['brand']),
                    'payout_email' => $row['contact_email'],
                ]
            );

            Settlement::updateOrCreate(
                ['reference' => $row['settlement_ref']],
                [
                    'fuel_station_id' => $station->id,
                    'amount' => $row['amount'],
                    'voucher_count' => $row['voucher_count'],
                    'status' => 'pending',
                    'settlement_date' => Carbon::today()->subDays($index),
                    'payment_method' => 'bank_transfer',
                    'notes' => 'Seeded SA retail brand payout test record.',
                ]
            );
        }

        $this->command->info('Retail brand payout seed complete: 5 South African pending settlements created/updated.');
    }
}

