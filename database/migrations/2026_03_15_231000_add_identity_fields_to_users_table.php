<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 120)->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 120)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('last_name');
            }
        });

        // Best-effort backfill for existing users so provider integrations can use per-user identity.
        if (!Schema::hasColumn('users', 'first_name') || !Schema::hasColumn('users', 'last_name') || !Schema::hasColumn('users', 'date_of_birth')) {
            return;
        }

        DB::table('users')
            ->select(['id', 'name', 'id_number', 'first_name', 'last_name', 'date_of_birth'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $first = trim((string) ($row->first_name ?? ''));
                    $last = trim((string) ($row->last_name ?? ''));
                    $dob = $row->date_of_birth ? (string) $row->date_of_birth : '';

                    $name = trim((string) ($row->name ?? ''));
                    if (($first === '' || $last === '') && $name !== '') {
                        $parts = preg_split('/\s+/', $name) ?: [];
                        $first = $first !== '' ? $first : (string) ($parts[0] ?? '');
                        $last = $last !== '' ? $last : (count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '');
                    }

                    if ($dob === '') {
                        $idNumber = preg_replace('/\D+/', '', (string) ($row->id_number ?? '')) ?: '';
                        $parsed = $this->parseSouthAfricanIdDob($idNumber);
                        if ($parsed) {
                            $dob = $parsed;
                        }
                    }

                    $update = [];
                    if ($first !== '' && trim((string) ($row->first_name ?? '')) === '') {
                        $update['first_name'] = Str::limit($first, 120, '');
                    }
                    if ($last !== '' && trim((string) ($row->last_name ?? '')) === '') {
                        $update['last_name'] = Str::limit($last, 120, '');
                    }
                    if ($dob !== '' && empty($row->date_of_birth)) {
                        $update['date_of_birth'] = $dob;
                    }

                    if ($update !== []) {
                        DB::table('users')->where('id', (int) $row->id)->update($update);
                    }
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['date_of_birth', 'last_name', 'first_name'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
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

        // Sanity: must not be in future.
        if ($dob->isFuture()) {
            return null;
        }

        return $dob->toDateString();
    }
};

