<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FuelStation;
use App\Models\TaplessApiPartner;
use App\Models\TaplessPaymentIntent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TaplessCheckoutController extends Controller
{
    public function index(Request $request): View
    {
        $partners = TaplessApiPartner::query()
            ->withCount(['stations', 'intents'])
            ->with(['stations:id,name,company,city'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stations = FuelStation::query()
            ->orderBy('company')
            ->orderBy('name')
            ->get(['id', 'name', 'company', 'city', 'status']);

        $recentIntents = TaplessPaymentIntent::query()
            ->with(['partner:id,name,public_key', 'station:id,name,company,city'])
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.tapless-checkout.index', [
            'partners' => $partners,
            'stations' => $stations,
            'recentIntents' => $recentIntents,
            'newCredentials' => session('tapless_credentials'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tapless_api_partners,slug'],
            'station_ids' => ['required', 'array', 'min:1'],
            'station_ids.*' => ['integer', 'exists:fuel_stations,id'],
            'webhook_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $name = trim((string) $validated['name']);
        $slug = trim((string) ($validated['slug'] ?? '')) ?: Str::slug($name) . '-' . strtolower(Str::random(5));
        $publicKey = 'bw_pk_' . strtolower(Str::random(32));
        $secret = 'bw_sk_' . Str::random(48);
        $webhookSecret = 'bw_wh_' . Str::random(48);

        $partner = TaplessApiPartner::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'public_key' => $publicKey,
            'secret_encrypted' => Crypt::encryptString($secret),
            'webhook_url' => $validated['webhook_url'] ?? null,
            'webhook_secret_encrypted' => Crypt::encryptString($webhookSecret),
            'allowed_ips' => [],
            'meta' => [
                'created_via' => 'admin_tapless_checkout',
                'created_by_user_id' => auth()->id(),
                'checkout_plugin_enabled' => true,
            ],
        ]);

        $partner->stations()->sync(array_values($validated['station_ids']));

        return redirect()
            ->route('admin.tapless-checkout.index')
            ->with('success', 'Tapless checkout partner approved and keys issued.')
            ->with('tapless_credentials', [
                'partner_name' => $partner->name,
                'public_key' => $publicKey,
                'secret_key' => $secret,
                'webhook_secret' => $webhookSecret,
                'station_ids' => array_values($validated['station_ids']),
            ]);
    }

    public function update(Request $request, TaplessApiPartner $partner): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,suspended'],
            'station_ids' => ['required', 'array', 'min:1'],
            'station_ids.*' => ['integer', 'exists:fuel_stations,id'],
            'webhook_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $partner->update([
            'name' => $validated['name'],
            'status' => $validated['status'],
            'webhook_url' => $validated['webhook_url'] ?? null,
        ]);
        $partner->stations()->sync(array_values($validated['station_ids']));

        return redirect()
            ->route('admin.tapless-checkout.index')
            ->with('success', 'Tapless checkout partner updated.');
    }

    public function rotateSecret(TaplessApiPartner $partner): RedirectResponse
    {
        $secret = 'bw_sk_' . Str::random(48);
        $webhookSecret = 'bw_wh_' . Str::random(48);

        $partner->forceFill([
            'secret_encrypted' => Crypt::encryptString($secret),
            'webhook_secret_encrypted' => Crypt::encryptString($webhookSecret),
        ])->save();

        return redirect()
            ->route('admin.tapless-checkout.index')
            ->with('success', 'Secret keys rotated. Copy them now.')
            ->with('tapless_credentials', [
                'partner_name' => $partner->name,
                'public_key' => $partner->public_key,
                'secret_key' => $secret,
                'webhook_secret' => $webhookSecret,
                'station_ids' => $partner->stations()->pluck('fuel_stations.id')->all(),
            ]);
    }

    public function destroy(TaplessApiPartner $partner): RedirectResponse
    {
        $partner->forceFill(['status' => 'suspended'])->save();

        return redirect()
            ->route('admin.tapless-checkout.index')
            ->with('success', 'Tapless checkout partner suspended.');
    }
}
