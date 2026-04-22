<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_check_in_and_out(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->postJson('/app-api/attendance/event', ['action' => 'check_in'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->postJson('/app-api/attendance/event', ['action' => 'check_out'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->getJson('/app-api/attendance/status')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_employee_can_upsert_own_entry(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->postJson('/app-api/attendance/entries/upsert', [
                'date' => '2026-03-27',
                'check_in_at' => '08:30',
                'check_out_at' => '17:30',
                'break_minutes' => 60,
                'remarks' => 'Updated entry',
            ])
            ->assertOk()
            ->assertJsonPath('entry.total_minutes', 480);
    }

    public function test_admin_can_view_and_update_daily_attendance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($admin)
            ->getJson('/app-api/attendance/admin/daily?date=2026-03-27')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($admin)
            ->postJson('/app-api/attendance/admin/daily', [
                'user_id' => $employee->id,
                'date' => '2026-03-27',
                'check_in_at' => '09:00',
                'check_out_at' => '18:00',
                'break_minutes' => 60,
                'status' => 'present',
            ])
            ->assertOk()
            ->assertJsonPath('entry.total_minutes', 480);
    }
}
