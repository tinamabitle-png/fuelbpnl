<?php

namespace App\Http\Controllers\Bnpl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class MrdScrapeController extends Controller
{
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

    public function refresh(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $lat = (string) $validated['lat'];
        $lng = (string) $validated['lng'];
        $url = 'https://www.mrdfood.com/';

        $scraperDir = base_path('tools/scrapers/mrd');
        $storageOutDir = storage_path('app/scrapes/mrd');
        @mkdir($storageOutDir, 0755, true);
        $outFile = 'catalog_user_' . $user->id . '.json';

        // Keep this bounded for an interactive request.
        $p = new Process([
            $this->nodeBinary(),
            'scrape_catalog.mjs',
            '--url',
            $url,
            '--out-dir',
            $storageOutDir,
            '--out-file',
            $outFile,
            '--max-items',
            '16',
            '--scroll-passes',
            '2',
            '--scroll-wait-ms',
            '900',
            '--max-w',
            '2000',
            '--lat',
            $lat,
            '--lng',
            $lng,
        ], $scraperDir, null, null, 300);

        try {
            $p->run();
        } catch (ProcessTimedOutException $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Mr D scrape timed out. Try again or choose a different city.',
            ], 504);
        }

        if (!$p->isSuccessful()) {
            return response()->json([
                'ok' => false,
                'message' => 'Mr D scrape failed.',
                'details' => trim($p->getErrorOutput()),
            ], 500);
        }

        $path = $storageOutDir . DIRECTORY_SEPARATOR . $outFile;
        if (!is_file($path)) {
            return response()->json([
                'ok' => false,
                'message' => 'No catalog output found.',
            ], 500);
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded) || !is_array($decoded['products'] ?? null)) {
            return response()->json([
                'ok' => false,
                'message' => 'Catalog output invalid.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'count' => count($decoded['products']),
        ]);
    }
}
