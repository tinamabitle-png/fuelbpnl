<?php

namespace App\Services\Core;

use App\Services\LedgerService as BaseLedgerService;

class LedgerService
{
    public function __construct(private readonly BaseLedgerService $baseLedgerService)
    {
    }

    public function record(
        string $actor,
        int $walletId,
        float $amount,
        string $type,
        string $description = '',
        array $meta = []
    ) {
        return $this->baseLedgerService->post(
            $walletId,
            $amount,
            'credit',
            [
                'type' => $type,
                'id' => $meta['settlement_id'] ?? null,
                'key' => $meta['idempotency_key'] ?? null,
            ]
        );
    }
}

