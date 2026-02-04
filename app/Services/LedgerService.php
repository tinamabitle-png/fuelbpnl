<?php
namespace App\Services;

use App\Models\LedgerEntry;

class LedgerService
{
    public function post(int $walletId, float $amount, string $direction, array $meta = [])
    {
        return LedgerEntry::create([
            'wallet_id' => $walletId,
            'amount' => $amount,
            'direction' => $direction,
            'reference_type' => $meta['type'] ?? null,
            'reference_id' => $meta['id'] ?? null,
            'idempotency_key' => $meta['key'] ?? null,
        ]);
    }
}
