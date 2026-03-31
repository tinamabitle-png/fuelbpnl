<?php

namespace App\Services;

use Illuminate\Support\Arr;

class MrdCatalogService
{
    public function loadCatalog(?string $pathOverride = null): array
    {
        $pathsToTry = array_values(array_filter([
            $pathOverride,
            storage_path('app/scrapes/mrd/catalog.json'),
            base_path('tools/scrapers/mrd/out/catalog.json'),
            base_path('resources/data/mrd_catalog.sample.json'),
        ]));

        foreach ($pathsToTry as $path) {
            if (!is_file($path)) {
                continue;
            }

            $raw = file_get_contents($path);
            if ($raw === false || trim($raw) === '') {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            $categories = array_values(array_filter((array) Arr::get($decoded, 'categories', []), 'is_array'));
            $products = array_values(array_filter((array) Arr::get($decoded, 'products', []), 'is_array'));

            return [
                'source' => (string) Arr::get($decoded, 'source', 'unknown'),
                'fetched_at' => (string) Arr::get($decoded, 'fetched_at', ''),
                'currency' => (string) Arr::get($decoded, 'currency', 'ZAR'),
                'categories' => $categories,
                'products' => $products,
                'path' => $path,
            ];
        }

        return [
            'source' => 'none',
            'fetched_at' => '',
            'currency' => 'ZAR',
            'categories' => [],
            'products' => [],
            'path' => '',
        ];
    }
}

