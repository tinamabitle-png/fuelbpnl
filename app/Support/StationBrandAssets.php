<?php

namespace App\Support;

final class StationBrandAssets
{
    public static function resolveLogoUrl(?string $stationName, ?string $stationCompany = null): ?string
    {
        $candidate = trim((string) ($stationCompany ?: $stationName ?: ''));
        if ($candidate === '' || strtolower($candidate) === 'n/a') {
            return null;
        }

        $needle = strtolower($candidate);
        $keywordsToFiles = [
            'shell' => 'shell-sa.png',
            'engen' => 'engen.png',
            'astron' => 'astron-energy.png',
            'bp' => 'bp-southern-africa.png',
            'sasol' => 'sasol.png',
            'puma' => 'puma-energy.png',
            'vivo' => 'vivo-energy.png',
            'total' => 'totalenergies.png',
            'petrosa' => 'petrosa.png',
            'eskom' => 'eskom.png',
            'mulilo' => 'mulilo.png',
            'central energy fund' => 'central-energy-fund.png',
            'cef' => 'central-energy-fund.png',
        ];

        foreach ($keywordsToFiles as $keyword => $file) {
            if (!str_contains($needle, $keyword)) {
                continue;
            }

            $appUrl = rtrim((string) config('app.url', 'https://bwiser.co.za'), '/');
            return $appUrl . '/images/brands/' . $file;
        }

        return null;
    }
}

