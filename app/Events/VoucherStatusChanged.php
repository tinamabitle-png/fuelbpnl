<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoucherStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.vouchers.monitor')];

        if (!empty($this->payload['station_id'])) {
            $channels[] = new PrivateChannel('merchant.station.' . $this->payload['station_id']);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'voucher.status.changed';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}

