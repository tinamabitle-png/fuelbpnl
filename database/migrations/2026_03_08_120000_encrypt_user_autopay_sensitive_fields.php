<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY autopay_token TEXT NULL');
            DB::statement('ALTER TABLE users MODIFY autopay_email TEXT NULL');
            DB::statement('ALTER TABLE users MODIFY autopay_customer_code TEXT NULL');
            DB::statement('ALTER TABLE users MODIFY autopay_details LONGTEXT NULL');
        }

        DB::table('users')
            ->select(['id', 'autopay_token', 'autopay_email', 'autopay_customer_code', 'autopay_details'])
            ->where(function ($q) {
                $q->whereNotNull('autopay_token')
                    ->orWhereNotNull('autopay_email')
                    ->orWhereNotNull('autopay_customer_code')
                    ->orWhereNotNull('autopay_details');
            })
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    $updates = [];

                    $token = $this->encryptIfNeeded($user->autopay_token);
                    if ($token !== $user->autopay_token) {
                        $updates['autopay_token'] = $token;
                    }

                    $email = $this->encryptIfNeeded($user->autopay_email);
                    if ($email !== $user->autopay_email) {
                        $updates['autopay_email'] = $email;
                    }

                    $customerCode = $this->encryptIfNeeded($user->autopay_customer_code);
                    if ($customerCode !== $user->autopay_customer_code) {
                        $updates['autopay_customer_code'] = $customerCode;
                    }

                    $details = $this->encryptIfNeeded($user->autopay_details);
                    if ($details !== $user->autopay_details) {
                        $updates['autopay_details'] = $details;
                    }

                    if ($updates !== []) {
                        DB::table('users')->where('id', $user->id)->update($updates);
                    }
                }
            }, 'id');
    }

    public function down(): void
    {
        // Intentionally non-reversible to avoid writing decrypted payment data back to disk.
    }

    private function encryptIfNeeded(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return (string) $value;
        }

        if ($this->isAlreadyEncrypted($raw)) {
            return $raw;
        }

        return Crypt::encryptString($raw);
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
};

