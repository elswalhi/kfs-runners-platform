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

        $response = $this->get('/event');
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

    public function test_google_drive_folder_id_extractor(): void
    {
        $id1 = \App\Services\GoogleDriveService::extractFolderId('https://drive.google.com/drive/folders/1-Wje_0tqu3YtWpJkQ_FgloPwwNxx8_sN');
        $this->assertEquals('1-Wje_0tqu3YtWpJkQ_FgloPwwNxx8_sN', $id1);

        $id2 = \App\Services\GoogleDriveService::extractFolderId('https://drive.google.com/drive/u/0/folders/1-Wje_0tqu3YtWpJkQ_FgloPwwNxx8_sN?usp=sharing');
        $this->assertEquals('1-Wje_0tqu3YtWpJkQ_FgloPwwNxx8_sN', $id2);

        $id3 = \App\Services\GoogleDriveService::extractFolderId('1-Wje_0tqu3YtWpJkQ_FgloPwwNxx8_sN');
        $this->assertEquals('1-Wje_0tqu3YtWpJkQ_FgloPwwNxx8_sN', $id3);
    }

    public function test_event_photo_renders_external_google_drive_url(): void
    {
        $event = \App\Models\Event::create([
            'title' => 'Test Event 2026',
            'slug' => 'test-event-2026',
            'event_year' => 2026,
            'drive_url' => 'https://drive.google.com/drive/folders/1-Wje_0tqu3YtWpJkQ_FgloPwwNxx8_sN',
            'is_published' => true,
        ]);

        $photo = $event->photos()->create([
            'path' => 'https://lh3.googleusercontent.com/d/1F3Y7lXkqGW2LfYSyeCCZ0qLiuszDDmSi',
            'alt' => 'IMG_6483.JPG',
            'is_cover' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($photo->isExternalUrl());
        $this->assertEquals('https://lh3.googleusercontent.com/d/1F3Y7lXkqGW2LfYSyeCCZ0qLiuszDDmSi', $photo->publicUrl());
        $this->assertEquals('https://lh3.googleusercontent.com/d/1F3Y7lXkqGW2LfYSyeCCZ0qLiuszDDmSi', $photo->adminUrl());

        $response = $this->get('/events/' . $event->slug);
        $response->assertStatus(200);
        $response->assertSee('https://lh3.googleusercontent.com/d/1F3Y7lXkqGW2LfYSyeCCZ0qLiuszDDmSi');
        $response->assertSee('https://drive.google.com/drive/folders/1-Wje_0tqu3YtWpJkQ_FgloPwwNxx8_sN');
    }

    public function test_multi_runner_registration_stores_document_for_each_runner(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $race = \App\Models\Race::create([
            'name' => '10 KM Race',
            'slug' => '10k-doc-test',
            'distance_label' => '10 KM',
            'distance_km' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        \App\Models\RaceKitPrice::create([
            'race_id' => $race->id,
            'kit_type' => 'basic',
            'price' => 200,
            'currency' => 'EGP',
            'is_active' => true,
        ]);

        $promo = \App\Models\PromoCode::create([
            'code' => 'FREE100DOC',
            'discount_type' => 'percentage',
            'discount_value' => 100,
            'is_active' => true,
        ]);

        $file1 = \Illuminate\Http\UploadedFile::fake()->create('runner1_id.jpg', 120, 'image/jpeg');
        $file2 = \Illuminate\Http\UploadedFile::fake()->create('runner2_id.png', 180, 'image/png');

        $response = $this->post('/api/registrations', [
            'buyerFirstName' => 'Tarek',
            'buyerLastName' => 'Ali',
            'buyerEmail' => 'tarek@example.com',
            'buyerPhone' => '01011112222',
            'promoCode' => 'FREE100DOC',
            'terms' => '1',
            'runners' => [
                [
                    'firstName' => 'Tarek',
                    'lastName' => 'Ali',
                    'email' => 'tarek@example.com',
                    'phone' => '01011112222',
                    'birthDate' => '1995-05-15',
                    'gender' => 'male',
                    'idNumber' => '29505151234567',
                    'race' => '10k-doc-test',
                    'kit' => 'basic',
                    'idFile' => $file1,
                ],
                [
                    'firstName' => 'Ahmed',
                    'lastName' => 'Ali',
                    'email' => 'ahmed@example.com',
                    'phone' => '01033334444',
                    'birthDate' => '1998-08-20',
                    'gender' => 'male',
                    'idNumber' => '29808201234567',
                    'race' => '10k-doc-test',
                    'kit' => 'basic',
                    'idFile' => $file2,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $reg1 = \App\Models\Registration::where('first_name', 'Tarek')->first();
        $this->assertNotNull($reg1);
        $this->assertNotNull($reg1->document_path);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($reg1->document_path);

        $reg2 = \App\Models\Registration::where('first_name', 'Ahmed')->first();
        $this->assertNotNull($reg2);
        $this->assertNotNull($reg2->document_path);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($reg2->document_path);
    }

    public function test_site_theme_defaults_to_teal_gold_and_can_toggle_via_query_param(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('data-theme="teal-gold"', false);
        $response->assertSee('theme-teal-gold', false);
        $response->assertSee('kfs-theme-palette.css', false);

        // Toggle to original via query param
        $responseOriginal = $this->get('/?theme=original');
        $responseOriginal->assertStatus(200);
        $responseOriginal->assertSee('data-theme="original"', false);
        $responseOriginal->assertSee('theme-original', false);
    }

    public function test_admin_can_update_site_theme(): void
    {
        $admin = \App\Models\User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/event-settings')
            ->post('/admin/event-settings', [
                'race_date' => '2026-11-20',
                'race_time' => '07:00',
                'countdown_enabled' => '1',
                'site_theme' => 'original',
            ]);

        $response->assertRedirect('/admin/event-settings');
        $this->assertSame('original', \App\Models\AppSetting::get('site_theme'));
    }
}
