<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeviceDataControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_polar_device_endpoint_is_not_shadowed_by_bridge_route(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'patient@example.com',
            'role' => 'patient',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/devices/polar/data', [
                'heart_rate' => 92,
                'rr_interval' => 0.82,
                'hrv' => 48,
                'device_id' => 'polar-test-device',
                'battery_level' => 94,
                'signal_quality' => 100,
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'message' => 'Polar data queued for processing',
            ]);

        Queue::assertPushed(\App\Jobs\ProcessHeartRateJob::class);
    }
}
