<?php

declare(strict_types=1);

use App\Support\ActivityCompatLogger;

if (!function_exists('activity')) {
    /**
     * Compatibility shim for code that expects Spatie's `activity()` helper.
     *
     * This project does not currently require spatie/laravel-activitylog, but several
     * controllers/models call `activity()->performedOn()->causedBy()->withProperties()->log()`.
     * Without a shim those paths crash with "Call to undefined function activity()".
     */
    function activity(): ActivityCompatLogger
    {
        return new ActivityCompatLogger();
    }
}

