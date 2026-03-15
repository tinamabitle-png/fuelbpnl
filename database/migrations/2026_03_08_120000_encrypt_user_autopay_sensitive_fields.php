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
            $this->dropAutopayCheckConstraintsForMySql();
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

    private function dropAutopayCheckConstraintsForMySql(): void
    {
        $database = (string) DB::getDatabaseName();
        $isMariaDb = $this->isMariaDb();

        $constraints = DB::select(
            <<<'SQL'
            SELECT tc.CONSTRAINT_NAME AS constraint_name, cc.CHECK_CLAUSE AS check_clause
            FROM information_schema.TABLE_CONSTRAINTS tc
            JOIN information_schema.CHECK_CONSTRAINTS cc
              ON tc.CONSTRAINT_NAME = cc.CONSTRAINT_NAME
             AND tc.CONSTRAINT_SCHEMA = cc.CONSTRAINT_SCHEMA
            WHERE tc.CONSTRAINT_SCHEMA = ?
              AND tc.TABLE_NAME = 'users'
              AND tc.CONSTRAINT_TYPE = 'CHECK'
            SQL,
            [$database]
        );

        foreach ($constraints as $constraint) {
            $name = (string) ($constraint->constraint_name ?? '');
            $clause = strtolower((string) ($constraint->check_clause ?? ''));

            if ($name === '') {
                continue;
            }

            if (
                str_contains($clause, 'autopay_token') ||
                str_contains($clause, 'autopay_email') ||
                str_contains($clause, 'autopay_customer_code') ||
                str_contains($clause, 'autopay_details')
            ) {
                $escaped = str_replace('`', '``', $name);
                $this->tryDropAutopayCheckConstraint($escaped, $isMariaDb);
            }
        }
    }

    private function tryDropAutopayCheckConstraint(string $constraintName, bool $isMariaDb): void
    {
        $statements = $isMariaDb
            ? [
                // MariaDB commonly uses DROP CONSTRAINT for CHECK constraints.
                "ALTER TABLE users DROP CONSTRAINT `{$constraintName}`",
                // Some MariaDB variants accept DROP CHECK.
                "ALTER TABLE users DROP CHECK `{$constraintName}`",
            ]
            : [
                // MySQL 8 uses DROP CHECK.
                "ALTER TABLE users DROP CHECK `{$constraintName}`",
                // Fallback if a server expects DROP CONSTRAINT.
                "ALTER TABLE users DROP CONSTRAINT `{$constraintName}`",
            ];

        foreach ($statements as $sql) {
            try {
                DB::statement($sql);
                return;
            } catch (\Illuminate\Database\QueryException $e) {
                // MariaDB returns errno 1091 when the constraint doesn't exist.
                $errno = (int) ($e->errorInfo[1] ?? 0);
                // MariaDB may also return errno 1064 when a DROP syntax isn't supported.
                if (in_array($errno, [1091, 1064], true)) {
                    continue;
                }
                throw $e;
            }
        }
    }

    private function isMariaDb(): bool
    {
        try {
            $row = DB::selectOne('SELECT VERSION() AS v');
            $version = strtolower((string) ($row->v ?? ''));
            return str_contains($version, 'mariadb');
        } catch (\Throwable) {
            return false;
        }
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
