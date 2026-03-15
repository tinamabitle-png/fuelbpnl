<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $hasFirst = Schema::hasColumn('users', 'first_name');
        $hasLast = Schema::hasColumn('users', 'last_name');
        $hasDob = Schema::hasColumn('users', 'date_of_birth');

        if (!$hasFirst && !$hasLast && !$hasDob) {
            return;
        }

        // NOTE: This is a provider-compliance backfill to avoid hard failures when creating virtual cards.
        // We never overwrite existing non-empty identity data.
        $defaultDob = trim((string) config('services.flutterwave.virtual_cards_date_of_birth', ''));
        if ($defaultDob === '') {
            // Fallback to a stable, valid date. Adjust in env if you need a different default.
            $defaultDob = '1990-01-01';
        }
        $defaultDob = $this->sanitizeDob($defaultDob);

        DB::table('users')
            ->select(['id', 'name', 'email', 'phone', 'id_number', 'first_name', 'last_name', 'date_of_birth'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($hasFirst, $hasLast, $hasDob, $defaultDob) {
                foreach ($rows as $row) {
                    $update = [];

                    $existingFirst = trim((string) ($row->first_name ?? ''));
                    $existingLast = trim((string) ($row->last_name ?? ''));
                    $existingDob = $row->date_of_birth ? (string) $row->date_of_birth : '';

                    [$derivedFirst, $derivedLast] = $this->deriveNames(
                        (string) ($row->name ?? ''),
                        (string) ($row->email ?? ''),
                        (string) ($row->phone ?? ''),
                        (int) $row->id
                    );

                    if ($hasFirst && $existingFirst === '' && $derivedFirst !== '') {
                        $update['first_name'] = substr($derivedFirst, 0, 120);
                    }
                    if ($hasLast && $existingLast === '' && $derivedLast !== '') {
                        $update['last_name'] = substr($derivedLast, 0, 120);
                    }

                    if ($hasDob && $existingDob === '') {
                        $idNumber = preg_replace('/\D+/', '', (string) ($row->id_number ?? '')) ?: '';
                        $parsed = $this->parseSouthAfricanIdDob($idNumber);
                        if ($parsed) {
                            $update['date_of_birth'] = $parsed;
                        } elseif ($defaultDob) {
                            $update['date_of_birth'] = $defaultDob;
                        }
                    }

                    if ($update !== []) {
                        DB::table('users')->where('id', (int) $row->id)->update($update);
                    }
                }
            }, 'id');
    }

    public function down(): void
    {
        // Intentionally no-op: do not remove backfilled identity fields.
    }

    /**
     * @return array{0:string,1:string}
     */
    private function deriveNames(string $name, string $email, string $phone, int $userId): array
    {
        $name = trim($name);
        if ($name !== '') {
            $name = preg_replace('/\s+/', ' ', $name) ?? $name;
            $parts = explode(' ', $name);
            $first = trim((string) ($parts[0] ?? ''));
            $last = count($parts) > 1 ? trim(implode(' ', array_slice($parts, 1))) : '';
            if ($first !== '' || $last !== '') {
                return [$first !== '' ? $first : 'Bwiser', $last !== '' ? $last : ('User ' . $userId)];
            }
        }

        $email = trim($email);
        if ($email !== '' && str_contains($email, '@')) {
            $local = explode('@', $email)[0] ?? '';
            $local = preg_replace('/[._\-]+/', ' ', $local) ?? $local;
            $local = preg_replace('/\s+/', ' ', trim($local)) ?? trim($local);
            if ($local !== '') {
                $parts = explode(' ', $local);
                $first = ucfirst(strtolower(trim((string) ($parts[0] ?? ''))));
                $last = count($parts) > 1 ? ucfirst(strtolower(trim(implode(' ', array_slice($parts, 1))))) : '';
                if ($first !== '' || $last !== '') {
                    return [$first !== '' ? $first : 'Bwiser', $last !== '' ? $last : ('User ' . $userId)];
                }
            }
        }

        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits !== '') {
            $last4 = substr($digits, -4);
            $last4 = $last4 !== '' ? $last4 : (string) $userId;
            return ['User', 'Mobile ' . $last4];
        }

        return ['Bwiser', 'User ' . $userId];
    }

    private function sanitizeDob(string $dob): ?string
    {
        $dob = trim($dob);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            return null;
        }

        try {
            $c = Carbon::createFromFormat('Y-m-d', $dob);
        } catch (\Throwable $e) {
            return null;
        }

        if ($c->isFuture()) {
            return null;
        }

        return $c->toDateString();
    }

    private function parseSouthAfricanIdDob(string $idNumber): ?string
    {
        if (!preg_match('/^\d{13}$/', $idNumber)) {
            return null;
        }

        $yy = (int) substr($idNumber, 0, 2);
        $mm = (int) substr($idNumber, 2, 2);
        $dd = (int) substr($idNumber, 4, 2);

        $nowYY = (int) now()->format('y');
        $century = $yy <= $nowYY ? 2000 : 1900;
        $yyyy = $century + $yy;

        try {
            $dob = Carbon::createFromDate($yyyy, $mm, $dd);
        } catch (\Throwable $e) {
            return null;
        }

        if ($dob->isFuture()) {
            return null;
        }

        return $dob->toDateString();
    }
};

