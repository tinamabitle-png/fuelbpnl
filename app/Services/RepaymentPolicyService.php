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
        'enable_default_fees' => 1,
        'enable_default_interest' => 1,
        'default_fee_pay_in_4_weekly' => 95,
        'default_fee_pay_in_4_max_charges' => 3,
        'default_fee_pay_in_3_once' => 125,
        'default_interest_monthly_rate' => 2.0,
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
            'enable_default_fees' => (int) ($rows['enable_default_fees'] ?? self::DEFAULTS['enable_default_fees']) === 1,
            'enable_default_interest' => (int) ($rows['enable_default_interest'] ?? self::DEFAULTS['enable_default_interest']) === 1,
            'default_fee_pay_in_4_weekly' => max(0, (float) ($rows['default_fee_pay_in_4_weekly'] ?? self::DEFAULTS['default_fee_pay_in_4_weekly'])),
            'default_fee_pay_in_4_max_charges' => max(1, (int) ($rows['default_fee_pay_in_4_max_charges'] ?? self::DEFAULTS['default_fee_pay_in_4_max_charges'])),
            'default_fee_pay_in_3_once' => max(0, (float) ($rows['default_fee_pay_in_3_once'] ?? self::DEFAULTS['default_fee_pay_in_3_once'])),
            'default_interest_monthly_rate' => max(0, (float) ($rows['default_interest_monthly_rate'] ?? self::DEFAULTS['default_interest_monthly_rate'])),
        ];
    }

    public function update(array $validated): void
    {
        $booleanKeys = ['enable_default_fees', 'enable_default_interest'];
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (!array_key_exists($key, $validated)) {
                continue;
            }

            $value = in_array($key, $booleanKeys, true)
                ? ((bool) $validated[$key] ? '1' : '0')
                : (string) $validated[$key];

            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'group' => 'repayments']
            );
        }
    }
}
