<?php

namespace App\Services\Payment;

use App\Models\FuelStation;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Services\Core\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementService
{
    private $ledgerService;
    
    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }
    
    public function processDailySettlements()
    {
        $settlements = [];
        
        // Get all merchants with pending vouchers
        $merchants = FuelStation::with(['vouchers' => function($query) {
            $query->where('status', 'redeemed')
                  ->whereNull('settlement_id');
        }])->get();
        
        foreach ($merchants as $merchant) {
            $totalAmount = $merchant->vouchers->sum('amount');
            
            if ($totalAmount > 0) {
                $settlement = $this->createSettlement($merchant, $totalAmount);
                $settlements[] = $settlement;
                
                // Mark vouchers as settled
                $merchant->vouchers->each(function($voucher) use ($settlement) {
                    $voucher->update(['settlement_id' => $settlement->id]);
                });
                
                // Record ledger entry
                $this->ledgerService->record(
                    'system',
                    $merchant->wallet->id,
                    $totalAmount,
                    'SETTLEMENT',
                    "Daily settlement for {$merchant->name}",
                    ['settlement_id' => $settlement->id]
                );
                
                // Send notification (in production, queue this)
                $this->sendSettlementNotification($merchant, $settlement);
            }
        }
        
        return $settlements;
    }
    
    private function createSettlement(FuelStation $merchant, float $amount): Settlement
    {
        return DB::transaction(function () use ($merchant, $amount) {
            $settlement = Settlement::create([
                'fuel_station_id' => $merchant->id,
                'amount' => $amount,
                'status' => 'pending',
                'reference' => 'STL' . now()->format('Ymd') . str_pad($merchant->id, 6, '0', STR_PAD_LEFT),
                'settlement_date' => now(),
            ]);
            
            return $settlement;
        });
    }
    
    private function sendSettlementNotification(FuelStation $merchant, Settlement $settlement)
    {
        // In production, integrate with email/SMS service
        Log::info("Settlement processed for {$merchant->name}: {$settlement->amount}");
        
        // Example: Send to queue for async processing
        // SendSettlementNotification::dispatch($merchant, $settlement);
    }
}