<?php

namespace App\Listeners\Auth;

use App\Events\Auth\UserRegistered;
use App\Services\AuditTrailService;

class RecordUserRegistered
{
    public function handle(UserRegistered $event): void
    {
        AuditTrailService::record(
            'auth_user_registered',
            $event->user,
            [],
            [
                'channel' => $event->channel,
                'roles' => $event->user->getRoleNames()->values()->all(),
            ],
            'User registration event recorded',
            $event->user->id
        );
    }
}

