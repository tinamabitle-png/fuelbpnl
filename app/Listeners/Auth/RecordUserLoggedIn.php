<?php

namespace App\Listeners\Auth;

use App\Events\Auth\UserLoggedIn;
use App\Services\AuditTrailService;

class RecordUserLoggedIn
{
    public function handle(UserLoggedIn $event): void
    {
        AuditTrailService::record(
            'auth_user_logged_in',
            $event->user,
            [],
            [
                'channel' => $event->channel,
                'roles' => $event->user->getRoleNames()->values()->all(),
            ],
            'User login event recorded',
            $event->user->id
        );
    }
}

