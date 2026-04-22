<?php

namespace Tests\Feature;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_fetch_leave_balances(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->getJson('/app-api/leave/balances?year=2026')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'balances');
    }

    public function test_employee_can_create_and_list_own_leave_requests(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->postJson('/app-api/leave/requests', [
                'start_date' => '2026-04-01',
                'end_date' => '2026-04-02',
                'leave_type' => 'annual',
                'part_day' => 'full',
                'person_to_relief' => 'Teammate',
                'reason' => 'Family matter',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('row.status', 'pending');

        $this->actingAs($user)
            ->getJson('/app-api/leave/requests?my=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'rows');
    }

    public function test_admin_can_approve_leave_request(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $admin = User::factory()->create(['role' => 'admin']);

        $leaveRequestId = $this->actingAs($employee)
            ->postJson('/app-api/leave/requests', [
                'start_date' => '2026-04-10',
                'end_date' => '2026-04-10',
                'leave_type' => 'sick',
                'part_day' => 'half_am',
                'reason' => 'Clinic visit',
            ])
            ->json('row.id');

        $this->actingAs($admin)
            ->postJson("/app-api/leave/requests/{$leaveRequestId}/status", [
                'status' => 'approved',
                'comment' => 'Approved.',
            ])
            ->assertOk()
            ->assertJsonPath('row.status', 'approved');
    }

    public function test_admin_can_seed_and_manage_leave_allocations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        $annual = LeaveType::query()->where('code', 'annual')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/app-api/leave/allocations/seed-defaults?year=2026')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($admin)
            ->postJson('/app-api/leave/allocations', [
                'user_id' => $employee->id,
                'year' => 2026,
                'alloc' => [
                    'annual' => 18,
                    'sick' => 10,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('leave_allocations', [
            'user_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'year' => 2026,
            'allocated_days' => 18,
        ]);

        $this->actingAs($admin)
            ->getJson('/app-api/leave/allocations?year=2026')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
