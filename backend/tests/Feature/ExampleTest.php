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
}
