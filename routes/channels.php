<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ChatThread;
use App\Models\FuelStation;

Broadcast::channel('chat.thread.{threadId}', function ($user, $threadId) {
    $thread = ChatThread::find($threadId);
    if (!$thread) {
        return false;
    }

    if ($user->hasAnyRole(['super_admin', 'admin', 'employee'])) {
        return true;
    }

    return (int) $thread->owner_id === (int) $user->id;
});

Broadcast::channel('admin.vouchers.monitor', function ($user) {
    return $user->hasAnyRole(['super_admin', 'admin', 'employee']);
});

Broadcast::channel('merchant.station.{stationId}', function ($user, $stationId) {
    if ($user->hasAnyRole(['super_admin', 'admin', 'employee'])) {
        return true;
    }

    if (!$user->hasRole('merchant')) {
        return false;
    }

    return FuelStation::where('id', $stationId)
        ->where('owner_id', $user->id)
        ->exists();
});
