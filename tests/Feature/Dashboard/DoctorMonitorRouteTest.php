<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorMonitorRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_open_monitoring_view_from_doctor_dashboard(): void
    {
        $doctor = User::factory()->create([
            'role' => 'doctor',
            'name' => 'د. أحمد',
        ]);

        $response = $this->actingAs($doctor)
            ->get('/doctor/monitor');

        $response->assertOk()
            ->assertSee('لوحة الطبيب')
            ->assertSee('نظام مراقبة المرضى');
    }
}
