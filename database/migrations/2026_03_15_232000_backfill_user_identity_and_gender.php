<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 16)->nullable()->after('date_of_birth');
            }
        });

        $hasFirst = Schema::hasColumn('users', 'first_name');
        $hasLast = Schema::hasColumn('users', 'last_name');
        $hasDob = Schema::hasColumn('users', 'date_of_birth');
        $hasGender = Schema::hasColumn('users', 'gender');

        if (!$hasFirst && !$hasLast && !$hasDob && !$hasGender) {
            return;
        }

        DB::table('users')
            ->select(['id', 'name', 'id_number', 'first_name', 'last_name', 'date_of_birth', 'gender'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($hasFirst, $hasLast, $hasDob, $hasGender) {
                foreach ($rows as $row) {
                    $update = [];

                    $name = trim((string) ($row->name ?? ''));
                    if ($name !== '' && ($hasFirst || $hasLast)) {
                        // Normalize whitespace, then explode by a single space.
                        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
                        $parts = explode(' ', $name);
                        $derivedFirst = trim((string) ($parts[0] ?? ''));
                        $derivedLast = count($parts) > 1 ? trim(implode(' ', array_slice($parts, 1))) : '';

                        if ($hasFirst && trim((string) ($row->first_name ?? '')) === '' && $derivedFirst !== '') {
                            $update['first_name'] = substr($derivedFirst, 0, 120);
                        }
                        if ($hasLast && trim((string) ($row->last_name ?? '')) === '' && $derivedLast !== '') {
                            $update['last_name'] = substr($derivedLast, 0, 120);
                        }
                    }

                    if ($hasDob && empty($row->date_of_birth)) {
                        $idNumber = preg_replace('/\D+/', '', (string) ($row->id_number ?? '')) ?: '';
                        $parsedDob = $this->parseSouthAfricanIdDob($idNumber);
                        if ($parsedDob) {
                            $update['date_of_birth'] = $parsedDob;
                        }
                    }

                    if ($hasGender && trim((string) ($row->gender ?? '')) === '') {
                        $update['gender'] = 'male';
                    }

                    if ($update !== []) {
                        DB::table('users')->where('id', (int) $row->id)->update($update);
                    }
                }
            }, 'id');
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'gender')) {
                $table->dropColumn('gender');
            }
        });
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

