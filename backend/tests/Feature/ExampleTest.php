<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_registration_page_renders_successfully(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('KFS RUNNERS');
    }

    public function test_homepage_renders_countdown_with_configured_date(): void
    {
        \App\Models\AppSetting::set('race_date', '2026-12-25');
        \App\Models\AppSetting::set('race_time', '08:30');

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('data-target="2026-12-25T08:30:00+02:00"', false);
    }

    public function test_admin_can_update_race_date_and_countdown(): void
    {
        $admin = \App\Models\User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/event-settings', [
            'race_date' => '2026-10-30',
            'race_time' => '06:00',
            'countdown_enabled' => '1',
        ]);

        $response->assertRedirect();
        $this->assertEquals('2026-10-30', \App\Models\AppSetting::get('race_date'));
        $this->assertEquals('06:00', \App\Models\AppSetting::get('race_time'));
        $this->assertTrue((bool) \App\Models\AppSetting::get('countdown_enabled'));
    }

    public function test_runner_qr_code_payload_includes_required_fields_and_tshirt_only_on_full_kit(): void
    {
        $race = \App\Models\Race::create([
            'name' => 'Half Marathon',
            'slug' => 'half-marathon',
            'distance_label' => '21 KM',
            'category_type' => 'individual',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $fullRunner = \App\Models\Registration::create([
            'race_id' => $race->id,
            'registration_code' => 'KFS-FULL-001',
            'first_name' => 'Mohamed',
            'last_name' => 'Salah',
            'email' => 'salah@example.com',
            'phone' => '01012345678',
            'birth_date' => '1995-05-15',
            'gender' => 'male',
            'kit_type' => 'full',
            'shirt_size' => 'XL',
            'payment_status' => 'paid',
            'amount' => 450,
            'currency' => 'EGP',
        ]);

        $fullPayload = \App\Services\QrCodeService::buildRunnerPayload($fullRunner);
        $this->assertStringContainsString('RACE NUMBER: KFS-FULL-001', $fullPayload);
        $this->assertStringContainsString('DISTANCE: 21 KM', $fullPayload);
        $this->assertStringContainsString('KIT: Full Kit', $fullPayload);
        $this->assertStringContainsString('T-SHIRT SIZE: XL', $fullPayload);

        $basicRunner = \App\Models\Registration::create([
            'race_id' => $race->id,
            'registration_code' => 'KFS-BASIC-002',
            'first_name' => 'Ali',
            'last_name' => 'Hassan',
            'email' => 'ali@example.com',
            'phone' => '01098765432',
            'birth_date' => '1998-08-20',
            'gender' => 'male',
            'kit_type' => 'basic',
            'shirt_size' => null,
            'payment_status' => 'paid',
            'amount' => 250,
            'currency' => 'EGP',
        ]);

        $basicPayload = \App\Services\QrCodeService::buildRunnerPayload($basicRunner);
        $this->assertStringContainsString('RACE NUMBER: KFS-BASIC-002', $basicPayload);
        $this->assertStringContainsString('DISTANCE: 21 KM', $basicPayload);
        $this->assertStringContainsString('KIT: Basic Kit', $basicPayload);
        $this->assertStringNotContainsString('T-SHIRT SIZE:', $basicPayload);

        $qrDataUri = \App\Services\QrCodeService::generateRunnerQr($fullRunner);
        $this->assertStringStartsWith('data:image/png;base64,', $qrDataUri);
    }

    public function test_admin_registration_details_page_renders_qr_code(): void
    {
        $admin = \App\Models\User::factory()->create([
            'role' => 'admin',
        ]);

        $race = \App\Models\Race::create([
            'name' => '10K Run',
            'slug' => '10k',
            'distance_label' => '10 KM',
            'category_type' => 'individual',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $runner = \App\Models\Registration::create([
            'race_id' => $race->id,
            'registration_code' => 'KFS-TEST-999',
            'first_name' => 'Karim',
            'last_name' => 'Mahmoud',
            'email' => 'karim@example.com',
            'phone' => '01055556666',
            'birth_date' => '1992-03-10',
            'gender' => 'male',
            'kit_type' => 'full',
            'shirt_size' => 'L',
            'payment_status' => 'paid',
            'amount' => 350,
            'currency' => 'EGP',
        ]);

        $response = $this->actingAs($admin)->get('/admin/registrations/' . $runner->id);
        $response->assertStatus(200);
        $response->assertSee('KFS-TEST-999');
        $response->assertSee('Karim Mahmoud');
        $response->assertSee('10 KM');
        $response->assertSee('data:image/png;base64,');
        $response->assertSee('T-Shirt: L');
    }
}
