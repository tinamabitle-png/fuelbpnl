<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ScrapeMrdCommand extends Command
{
    protected $signature = 'mrd:scrape
        {--url=https://www.mrdfood.com/ : Page to scrape for a catalog (category pages work best).}
        {--max-items=80 : Max items for the catalog output.}
        {--lat= : Latitude for "use my location" scraping (optional).}
        {--lng= : Longitude for "use my location" scraping (optional).}
        {--no-special : Skip the "Mr D special" image scrape.}
        {--no-catalog : Skip the catalog scrape.}';

    protected $description = 'Scrape Mr D (special image + lightweight catalog) into local JSON assets for the BNPL marketplace template.';

    private function nodeBinary(): string
    {
        $candidates = array_values(array_filter([
            env('NODE_BINARY'),
            '/usr/local/bin/node',
            '/opt/homebrew/bin/node',
            '/usr/bin/node',
            'node',
        ]));

        foreach ($candidates as $c) {
            if ($c === 'node') {
                return $c;
            }
            if (is_file($c) && is_executable($c)) {
                return $c;
            }
        }

        return 'node';
    }

    public function handle(): int
    {
        $scraperDir = base_path('tools/scrapers/mrd');
        $publicImagesDir = public_path('images');
        $storageOutDir = storage_path('app/scrapes/mrd');

        @mkdir($storageOutDir, 0755, true);

        if (!$this->option('no-special')) {
            $this->info('Scraping Mr D special image...');
            $p = new Process([
                $this->nodeBinary(),
                'scrape_special.mjs',
                '--url',
                'https://www.mrdfood.com/',
                '--out-dir',
                base_path('tools/scrapers/mrd/out/special'),
                '--download-dir',
                $publicImagesDir,
                '--max-w',
                '2400',
            ], $scraperDir, null, null, 180);
            $p->run(fn ($type, $buffer) => $this->output->write($buffer));

            if (!$p->isSuccessful()) {
                $this->error('Special scrape failed.');
                return 1;
            }
        }

        if (!$this->option('no-catalog')) {
            $url = (string) $this->option('url');
            $maxItems = (string) $this->option('max-items');
            $lat = trim((string) $this->option('lat'));
            $lng = trim((string) $this->option('lng'));

            $this->info('Scraping Mr D catalog...');
            $outPath = $storageOutDir . DIRECTORY_SEPARATOR . 'catalog.json';
            $cmd = [
                $this->nodeBinary(),
                'scrape_catalog.mjs',
                '--url',
                $url,
                '--out-dir',
                $storageOutDir,
                '--out-file',
                'catalog.json',
                '--max-items',
                $maxItems,
            ];
            if ($lat !== '' && $lng !== '') {
                $cmd[] = '--lat';
                $cmd[] = $lat;
                $cmd[] = '--lng';
                $cmd[] = $lng;
            }

            $p = new Process($cmd, $scraperDir, null, null, 600);
            $p->run(fn ($type, $buffer) => $this->output->write($buffer));

            if (!$p->isSuccessful()) {
                $this->error('Catalog scrape failed.');
                return 1;
            }

            // If we got blocked/geofenced and wrote an empty catalog, remove it so the app falls back
            // to the bundled template JSON.
            $raw = @file_get_contents($outPath);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            $products = is_array($decoded) ? ($decoded['products'] ?? null) : null;
            if (!is_array($products) || count($products) === 0) {
                @unlink($outPath);
                $this->warn('Catalog scrape produced no products; removed catalog.json so the template data is used.');
            }
        }

        $this->info('Done.');
        $this->line('Catalog: ' . storage_path('app/scrapes/mrd/catalog.json'));
        $this->line('Special: ' . public_path('images/mrd-special.jpg') . ' (or .png/.webp)');

        return 0;
    }
}
