<?php

namespace App\Console\Commands;

use App\Models\FuelStation;
use App\Models\TaplessApiPartner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CreateTaplessApiPartner extends Command
{
    protected $signature = 'tapless:partner-create
        {name : Partner display name}
        {--slug= : Partner slug}
        {--stations= : Comma-separated station IDs}
        {--webhook-url= : Optional webhook endpoint}
        {--allow-ip=* : Allowed source IPs}';

    protected $description = 'Create tapless API partner credentials for an aggregator integration.';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $slug = trim((string) ($this->option('slug') ?: Str::slug($name)));

        if ($name === '' || $slug === '') {
            $this->error('Both partner name and slug are required.');

            return self::FAILURE;
        }

        if (TaplessApiPartner::query()->where('slug', $slug)->exists()) {
            $this->error("A tapless API partner with slug [{$slug}] already exists.");

            return self::FAILURE;
        }

        $publicKey = 'bw_ptnr_' . Str::lower(Str::random(24));
        $secret = 'bw_secret_' . Str::random(48);
        $webhookSecret = 'bw_webhook_' . Str::random(48);

        $partner = TaplessApiPartner::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'public_key' => $publicKey,
            'secret_encrypted' => Crypt::encryptString($secret),
            'webhook_url' => $this->option('webhook-url') ?: null,
            'webhook_secret_encrypted' => Crypt::encryptString($webhookSecret),
            'allowed_ips' => array_values(array_filter((array) $this->option('allow-ip'))),
            'meta' => [
                'created_via' => 'artisan',
            ],
        ]);

        $stationIds = collect(explode(',', (string) $this->option('stations')))
            ->map(fn ($value) => (int) trim($value))
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values();

        if ($stationIds->isNotEmpty()) {
            $validStationIds = FuelStation::query()
                ->whereIn('id', $stationIds)
                ->pluck('id')
                ->all();
            $partner->stations()->sync($validStationIds);
        }

        $this->info('Tapless API partner created successfully.');
        $this->line('Partner ID: ' . $partner->id);
        $this->line('Slug: ' . $partner->slug);
        $this->line('Public key: ' . $publicKey);
        $this->line('Secret: ' . $secret);
        $this->line('Webhook secret: ' . $webhookSecret);

        return self::SUCCESS;
    }
}
