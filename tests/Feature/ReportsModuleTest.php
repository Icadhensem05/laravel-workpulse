<?php

namespace Tests\Feature;

use App\Models\AttendanceEntry;
use App\Models\Claim;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_endpoints_return_summary_datasets(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $leaveType = LeaveType::query()->where('code', 'annual')->firstOrFail();

        AttendanceEntry::query()->create([
            'user_id' => $user->id,
            'attendance_date' => '2026-03-27',
            'check_in_at' => '08:30:00',
            'check_out_at' => '17:30:00',
            'status' => 'present',
            'updated_by_user_id' => $user->id,
        ]);

        LeaveRequest::query()->create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-03-15',
            'end_date' => '2026-03-15',
            'part_day' => 'full',
            'days_count' => 1,
            'status' => 'approved',
        ]);

        Claim::query()->create([
            'claim_no' => 'CLM-202603-00001',
            'employee_user_id' => $user->id,
            'company_name' => 'Weststar Engineering',
            'employee_name' => $user->display_name,
            'claim_month' => '2026-03',
            'claim_date' => '2026-03-27',
            'total_travelling' => 10,
            'total_transportation' => 0,
            'total_accommodation' => 0,
            'total_travelling_allowance' => 0,
            'total_entertainment' => 0,
            'total_miscellaneous' => 0,
            'advance_amount' => 0,
            'grand_total' => 10,
            'balance_claim' => 10,
            'status' => 'approved',
        ]);

        $this->actingAs($user)->getJson('/app-api/reports/summary?month=2026-03')->assertOk()->assertJsonPath('success', true);
        $this->actingAs($user)->getJson('/app-api/reports/attendance?date=2026-03-27')->assertOk()->assertJsonPath('success', true);
        $this->actingAs($user)->getJson('/app-api/reports/leave?status=all')->assertOk()->assertJsonPath('success', true);
        $this->actingAs($user)->getJson('/app-api/reports/claims?month=2026-03')->assertOk()->assertJsonPath('success', true);
    }
}
