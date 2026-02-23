<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditTrailService
{
    public static function record(
        string $action,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null,
        ?int $actorId = null
    ): void {
        try {
            $request = app()->bound('request') ? request() : null;

            AuditLog::create([
                'user_id' => $actorId ?? auth()->id(),
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->id,
                'old_values' => empty($oldValues) ? null : $oldValues,
                'new_values' => empty($newValues) ? null : $newValues,
                'description' => $description,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

