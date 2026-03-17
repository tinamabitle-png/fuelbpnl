<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationIdentityFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_registration_page_does_not_show_full_name_field(): void
    {
        $res = $this->get('/register/driver');
        $res->assertOk();

        $res->assertSee('First Name');
        $res->assertSee('Last Name');
        $res->assertDontSee('Full Name');
    }

    public function test_merchant_registration_page_does_not_show_full_name_field(): void
    {
        $res = $this->get('/register/merchant');
        $res->assertOk();

        $res->assertSee('First Name');
        $res->assertSee('Last Name');
        $res->assertDontSee('Full Name');
    }

    public function test_google_complete_page_does_not_show_full_name_field(): void
    {
        $res = $this
            ->withSession([
                'google_oauth_pending' => [
                    'sub' => 'test-sub',
                    'email' => 'g@example.com',
                    'name' => 'Google User',
                ],
            ])
            ->get('/auth/google/complete');

        $res->assertOk();
        $res->assertSee('First Name');
        $res->assertSee('Last Name');
        $res->assertDontSee('Full Name');
    }

    public function test_driver_can_register_with_first_and_last_name(): void
    {
        Storage::fake('public');
        Role::create(['name' => 'driver']);

        $payload = [
            'first_name' => 'Alex',
            'last_name' => 'Smith',
            'gender' => 'male',
            // SA ID YYMMDD... -> 2001-01-01
            'id_number' => '0101015009087',
            'date_of_birth' => '2001-01-01',
            'phone' => '+27721234567',
            'email' => 'alex@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'home_address' => '1 Main Road, Suburb',
            'city' => 'Johannesburg',
            'country' => 'South Africa',
            'latitude' => '-26.2041',
            'longitude' => '28.0473',
            'driver_platform' => 'uber',
            'driver_platform_other' => '',
            'id_document' => UploadedFile::fake()->image('id.png'),
            'driver_license_document' => UploadedFile::fake()->image('license.png'),
            'vehicle_license_document' => UploadedFile::fake()->image('vehicle.png'),
        ];

        $res = $this->post('/register/driver', $payload);
        $res->assertRedirect('/login');

        $user = User::query()->where('email', 'alex@example.com')->first();
        $this->assertNotNull($user);

        $this->assertSame('Alex Smith', $user->name);
        $this->assertSame('Alex', $user->first_name);
        $this->assertSame('Smith', $user->last_name);
        $this->assertSame('male', $user->gender);
        $this->assertSame('2001-01-01', optional($user->date_of_birth)->toDateString());
    }
}
