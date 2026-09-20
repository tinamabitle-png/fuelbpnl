<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_route_is_hidden_when_disabled(): void
    {
        config(['services.google.enabled' => false]);

        $this->get(route('auth.google.redirect'))->assertNotFound();
    }

    public function test_active_investor_can_log_in_with_verified_google_account(): void
    {
        $this->configureGoogle();

        $user = User::factory()->create([
            'email' => 'investor@example.com',
            'google_sub' => null,
            'status' => 'active',
        ]);
        Role::create(['name' => 'investor', 'guard_name' => 'web']);
        $user->assignRole('investor');

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['id_token' => 'verified-id-token']),
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'google-client-id',
                'iss' => 'https://accounts.google.com',
                'exp' => now()->addMinutes(10)->timestamp,
                'sub' => 'google-investor-123',
                'email' => 'investor@example.com',
                'email_verified' => true,
                'name' => 'Investor User',
            ]),
        ]);

        $response = $this
            ->withSession(['google_oauth_state' => 'expected-state'])
            ->get(route('auth.google.callback', [
                'code' => 'authorization-code',
                'state' => 'expected-state',
            ]));

        $response->assertRedirect(route('investor.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-investor-123', $user->fresh()->google_sub);
    }

    public function test_google_callback_rejects_token_from_an_invalid_issuer(): void
    {
        $this->configureGoogle();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['id_token' => 'untrusted-id-token']),
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'google-client-id',
                'iss' => 'https://example.com',
                'exp' => now()->addMinutes(10)->timestamp,
                'sub' => 'untrusted-subject',
                'email' => 'person@example.com',
                'email_verified' => true,
            ]),
        ]);

        $response = $this
            ->withSession(['google_oauth_state' => 'expected-state'])
            ->get(route('auth.google.callback', [
                'code' => 'authorization-code',
                'state' => 'expected-state',
            ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function configureGoogle(): void
    {
        config([
            'services.google.enabled' => true,
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://bwiser.test/auth/google/callback',
        ]);
    }
}
