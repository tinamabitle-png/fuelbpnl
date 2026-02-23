<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RepaymentPolicyService
{
    private const DEFAULTS = [
        'autopay_max_retries' => 3,
        'autopay_retry_hours' => 24,
        'autopay_grace_days' => 2,
        'autopay_auto_disable_threshold' => 5,
    ];

    public function get(): array
    {
        $rows = DB::table('settings')
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key')
            ->all();

        return [
            'autopay_max_retries' => max(1, (int) ($rows['autopay_max_retries'] ?? self::DEFAULTS['autopay_max_retries'])),
            'autopay_retry_hours' => max(1, (int) ($rows['autopay_retry_hours'] ?? self::DEFAULTS['autopay_retry_hours'])),
            'autopay_grace_days' => max(0, (int) ($rows['autopay_grace_days'] ?? self::DEFAULTS['autopay_grace_days'])),
            'autopay_auto_disable_threshold' => max(1, (int) ($rows['autopay_auto_disable_threshold'] ?? self::DEFAULTS['autopay_auto_disable_threshold'])),
        ];
    }

    public function update(array $validated): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (!array_key_exists($key, $validated)) {
                continue;
            }
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (string) $validated[$key], 'group' => 'repayments']
            );
        }
    }
}
