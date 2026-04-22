<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\ApprovalSetting;
use App\Models\Asset;
use App\Models\AttendanceEntry;
use App\Models\Claim;
use App\Models\ClaimCategory;
use App\Models\ClaimItem;
use App\Models\ClaimPayment;
use App\Models\ClaimStatusLog;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocalWorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $users = $this->seedUsers();
            $leaveTypes = $this->seedLeaveTypes();
            $claimCategories = $this->seedClaimCategories();

            $this->seedSettings();
            $this->seedApprovals($users);
            $this->seedAttendance($users);
            $this->seedLeaveData($users, $leaveTypes);
            $this->seedClaims($users, $claimCategories);
            $this->seedAssets($users);
            $this->seedTeams($users);
            $this->seedTasks($users);
        });
    }

    private function seedUsers(): array
    {
        $primary = User::query()->updateOrCreate(
            ['email' => 'irsyad050505@gmail.com'],
            [
                'auth_user_id' => 20,
                'name' => 'Muhammad Irsyad',
                'first_name' => 'Muhammad',
                'last_name' => 'Irsyad',
                'employee_code' => 'WES-0146',
                'role' => 'admin',
                'status' => 'active',
                'job_title' => 'Super Admin',
                'department' => 'ICT',
                'cost_center' => 'KLHQ',
                'base' => 'Kuala Lumpur',
                'phone' => '0146630395',
                'password' => Hash::make('Password123!'),
            ]
        );

        $manager = User::query()->updateOrCreate(
            ['email' => 'nursyafiqah@weststar.test'],
            [
                'name' => 'Nur Syafiqah',
                'first_name' => 'Nur',
                'last_name' => 'Syafiqah',
                'employee_code' => 'WES-0102',
                'role' => 'admin',
                'status' => 'active',
                'job_title' => 'HR Manager',
                'department' => 'Human Resource',
                'cost_center' => 'KLHQ',
                'base' => 'Kuala Lumpur',
                'phone' => '0122233445',
                'password' => Hash::make('Password123!'),
            ]
        );

        $ops = User::query()->updateOrCreate(
            ['email' => 'ahmad.razi@weststar.test'],
            [
                'name' => 'Ahmad Razi',
                'first_name' => 'Ahmad',
                'last_name' => 'Razi',
                'employee_code' => 'WES-0118',
                'role' => 'employee',
                'status' => 'active',
                'job_title' => 'Operations Executive',
                'department' => 'Operations',
                'cost_center' => 'OPS',
                'base' => 'Shah Alam',
                'phone' => '0125566778',
                'password' => Hash::make('Password123!'),
            ]
        );

        $finance = User::query()->updateOrCreate(
            ['email' => 'faris.najmi@weststar.test'],
            [
                'name' => 'Faris Najmi',
                'first_name' => 'Faris',
                'last_name' => 'Najmi',
                'employee_code' => 'WES-0091',
                'role' => 'employee',
                'status' => 'active',
                'job_title' => 'Finance Analyst',
                'department' => 'Finance',
                'cost_center' => 'FIN',
                'base' => 'Kuala Lumpur',
                'phone' => '0129988776',
                'password' => Hash::make('Password123!'),
            ]
        );

        return compact('primary', 'manager', 'ops', 'finance');
    }

    private function seedLeaveTypes(): array
    {
        $types = [
            ['code' => 'annual', 'name' => 'Annual', 'default_days' => 14, 'display_order' => 1],
            ['code' => 'sick', 'name' => 'Sick', 'default_days' => 14, 'display_order' => 2],
            ['code' => 'emergency', 'name' => 'Emergency', 'default_days' => 3, 'display_order' => 3],
            ['code' => 'unpaid', 'name' => 'Unpaid', 'default_days' => 0, 'display_order' => 4],
        ];

        $result = [];
        foreach ($types as $type) {
            $result[$type['code']] = LeaveType::query()->updateOrCreate(
                ['code' => $type['code']],
                $type + ['is_active' => true]
            );
        }

        return $result;
    }

    private function seedClaimCategories(): array
    {
        $categories = [
            ['code' => 'travelling', 'name' => 'Travelling', 'requires_attachment' => false, 'display_order' => 1],
            ['code' => 'transportation', 'name' => 'Transportation', 'requires_attachment' => true, 'display_order' => 2],
            ['code' => 'accommodation', 'name' => 'Accommodation', 'requires_attachment' => true, 'display_order' => 3],
            ['code' => 'travelling_allowance', 'name' => 'Travelling Allowance', 'requires_attachment' => false, 'display_order' => 4],
            ['code' => 'entertainment', 'name' => 'Entertainment', 'requires_attachment' => true, 'display_order' => 5],
            ['code' => 'miscellaneous', 'name' => 'Miscellaneous', 'requires_attachment' => true, 'display_order' => 6],
        ];

        $result = [];
        foreach ($categories as $category) {
            $result[$category['code']] = ClaimCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                $category + ['is_active' => true]
            );
        }

        return $result;
    }

    private function seedSettings(): void
    {
        $settings = [
            'checkin_reminders' => '1',
            'break_alerts' => '1',
            'weekly_report_email' => '1',
            'overtime_enabled' => '0',
            'start_time' => '08:30',
            'end_time' => '17:30',
        ];

        foreach ($settings as $key => $value) {
            AppSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }
    }

    private function seedApprovals(array $users): void
    {
        $rows = [
            ['module' => 'leave', 'setting_key' => 'level_1_default', 'setting_value' => (string) $users['manager']->id],
            ['module' => 'leave', 'setting_key' => 'level_2_default', 'setting_value' => (string) $users['primary']->id],
            ['module' => 'claims', 'setting_key' => 'finance_checker', 'setting_value' => (string) $users['finance']->id],
            ['module' => 'attendance', 'setting_key' => 'late_cutoff', 'setting_value' => '09:00'],
        ];

        foreach ($rows as $row) {
            ApprovalSetting::query()->updateOrCreate(
                ['module' => $row['module'], 'setting_key' => $row['setting_key']],
                ['setting_value' => $row['setting_value']]
            );
        }
    }

    private function seedAttendance(array $users): void
    {
        $entries = [
            ['user' => $users['primary'], 'date' => now()->subDays(2)->toDateString(), 'in' => '08:41:00', 'out' => '17:38:00', 'break' => 40, 'status' => 'on_time'],
            ['user' => $users['primary'], 'date' => now()->subDay()->toDateString(), 'in' => '08:57:00', 'out' => '17:26:00', 'break' => 35, 'status' => 'late'],
            ['user' => $users['primary'], 'date' => now()->toDateString(), 'in' => '08:36:00', 'out' => null, 'break' => 0, 'status' => 'on_time'],
            ['user' => $users['manager'], 'date' => now()->toDateString(), 'in' => '08:29:00', 'out' => '17:32:00', 'break' => 45, 'status' => 'on_time'],
            ['user' => $users['ops'], 'date' => now()->toDateString(), 'in' => '09:11:00', 'out' => '18:02:00', 'break' => 30, 'status' => 'late'],
            ['user' => $users['finance'], 'date' => now()->toDateString(), 'in' => null, 'out' => null, 'break' => 0, 'status' => 'absent'],
        ];

        foreach ($entries as $entry) {
            $totalMinutes = $entry['in'] && $entry['out']
                ? max(0, Carbon::createFromFormat('H:i:s', $entry['in'])->diffInMinutes(Carbon::createFromFormat('H:i:s', $entry['out'])) - $entry['break'])
                : 0;

            $record = AttendanceEntry::query()
                ->where('user_id', $entry['user']->id)
                ->whereDate('attendance_date', $entry['date'])
                ->first();

            if (! $record) {
                $record = new AttendanceEntry([
                    'user_id' => $entry['user']->id,
                    'attendance_date' => $entry['date'],
                ]);
            }

            $record->fill([
                'check_in_at' => $entry['in'],
                'check_out_at' => $entry['out'],
                'break_minutes' => $entry['break'],
                'total_minutes' => $totalMinutes,
                'status' => $entry['status'],
                'remarks' => $entry['status'] === 'absent' ? 'No clock in recorded.' : null,
                'updated_by_user_id' => $users['primary']->id,
            ]);
            $record->save();
        }
    }

    private function seedLeaveData(array $users, array $leaveTypes): void
    {
        $year = now()->year;
        foreach ($users as $user) {
            foreach ($leaveTypes as $type) {
                LeaveAllocation::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                    ],
                    ['allocated_days' => $type->default_days]
                );
            }
        }

        LeaveRequest::query()->updateOrCreate(
            [
                'user_id' => $users['primary']->id,
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->subDays(5)->toDateString(),
            ],
            [
                'leave_type_id' => $leaveTypes['sick']->id,
                'part_day' => 'full',
                'days_count' => 1,
                'person_to_relief' => $users['manager']->display_name,
                'reason' => 'Clinic follow-up appointment.',
                'status' => 'approved',
                'admin_comment' => 'Approved by HR.',
                'decided_by_user_id' => $users['manager']->id,
                'decided_at' => now()->subDays(6),
            ]
        );

        LeaveRequest::query()->updateOrCreate(
            [
                'user_id' => $users['ops']->id,
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
            ],
            [
                'leave_type_id' => $leaveTypes['annual']->id,
                'part_day' => 'full',
                'days_count' => 2,
                'person_to_relief' => $users['finance']->display_name,
                'reason' => 'Family matters out of town.',
                'status' => 'pending',
            ]
        );
    }

    private function seedClaims(array $users, array $categories): void
    {
        $claim = Claim::query()->updateOrCreate(
            ['claim_no' => 'CLM-'.now()->format('Ym').'-00001'],
            [
                'employee_user_id' => $users['primary']->id,
                'company_name' => 'Weststar Engineering',
                'employee_name' => $users['primary']->display_name,
                'employee_code' => $users['primary']->employee_code,
                'position_title' => $users['primary']->job_title,
                'department' => $users['primary']->department,
                'cost_center' => $users['primary']->cost_center,
                'claim_month' => now()->format('Y-m'),
                'claim_date' => now()->subDays(1)->toDateString(),
                'total_travelling' => 86.40,
                'total_transportation' => 45.00,
                'total_accommodation' => 0.0,
                'total_travelling_allowance' => 0.0,
                'total_entertainment' => 0.0,
                'total_miscellaneous' => 18.50,
                'advance_amount' => 0.0,
                'grand_total' => 149.90,
                'balance_claim' => 149.90,
                'employee_remarks' => 'Client support trip to Shah Alam.',
                'manager_remarks' => 'Looks fine.',
                'finance_remarks' => null,
                'status' => 'pending_finance_verification',
                'submitted_at' => now()->subDay(),
            ]
        );

        ClaimItem::query()->updateOrCreate(
            ['claim_id' => $claim->id, 'line_no' => 1],
            [
                'category_id' => $categories['travelling']->id,
                'item_date' => now()->subDays(2)->toDateString(),
                'from_location' => 'KLHQ',
                'to_location' => 'Shah Alam',
                'purpose' => 'Client support visit',
                'description' => 'Mileage, toll, and parking.',
                'distance_km' => 96,
                'mileage_rate' => 0.9,
                'mileage_amount' => 86.4,
                'toll_amount' => 12.0,
                'parking_amount' => 6.5,
                'rate_amount' => 0.0,
                'quantity_value' => 1,
                'amount' => 86.4,
                'total_amount' => 104.9,
                'remarks' => 'Round trip',
            ]
        );

        ClaimItem::query()->updateOrCreate(
            ['claim_id' => $claim->id, 'line_no' => 2],
            [
                'category_id' => $categories['transportation']->id,
                'item_date' => now()->subDays(2)->toDateString(),
                'description' => 'Fuel top up',
                'distance_km' => 0,
                'mileage_rate' => 0,
                'mileage_amount' => 0,
                'toll_amount' => 0,
                'parking_amount' => 0,
                'rate_amount' => 0,
                'quantity_value' => 1,
                'amount' => 45.0,
                'total_amount' => 45.0,
                'remarks' => 'Receipted',
            ]
        );

        ClaimStatusLog::query()->updateOrCreate(
            ['claim_id' => $claim->id, 'action_name' => 'claim_created'],
            [
                'from_status' => null,
                'to_status' => 'draft',
                'action_by_user_id' => $users['primary']->id,
                'action_role' => 'admin',
                'remarks' => 'Local seed draft.',
            ]
        );

        ClaimStatusLog::query()->updateOrCreate(
            ['claim_id' => $claim->id, 'action_name' => 'claim_submitted'],
            [
                'from_status' => 'draft',
                'to_status' => 'submitted',
                'action_by_user_id' => $users['primary']->id,
                'action_role' => 'admin',
                'remarks' => null,
            ]
        );

        ClaimStatusLog::query()->updateOrCreate(
            ['claim_id' => $claim->id, 'action_name' => 'manager_approve'],
            [
                'from_status' => 'submitted',
                'to_status' => 'pending_finance_verification',
                'action_by_user_id' => $users['manager']->id,
                'action_role' => 'admin',
                'remarks' => 'Approved for finance verification.',
            ]
        );

        ClaimPayment::query()->updateOrCreate(
            ['claim_id' => $claim->id, 'payment_reference' => 'PAY-LOCAL-0001'],
            [
                'payment_date' => now()->toDateString(),
                'payment_method' => 'Bank Transfer',
                'payment_amount' => 149.9,
                'remarks' => 'Prepared for next payout batch.',
                'recorded_by_user_id' => $users['finance']->id,
            ]
        );
    }

    private function seedAssets(array $users): void
    {
        $rows = [
            ['asset_code' => 'VHC-001', 'name' => 'Toyota Hilux', 'category' => 'Vehicle', 'status' => 'assigned', 'assigned_to_user_id' => $users['ops']->id, 'assigned_at' => now()->subDays(15)->toDateString(), 'remarks' => 'Operations pool vehicle'],
            ['asset_code' => 'LTP-014', 'name' => 'Dell Latitude 5440', 'category' => 'Laptop', 'status' => 'assigned', 'assigned_to_user_id' => $users['primary']->id, 'assigned_at' => now()->subDays(40)->toDateString(), 'remarks' => 'Primary work device'],
            ['asset_code' => 'MON-008', 'name' => 'Samsung 27 Monitor', 'category' => 'Monitor', 'status' => 'available', 'assigned_to_user_id' => null, 'assigned_at' => null, 'remarks' => 'Ready in IT store'],
        ];

        foreach ($rows as $row) {
            Asset::query()->updateOrCreate(
                ['asset_code' => $row['asset_code']],
                $row
            );
        }
    }

    private function seedTeams(array $users): void
    {
        $team = Team::query()->updateOrCreate(
            ['name' => 'ICT Core Team'],
            [
                'description' => 'Local Laravel workspace test team.',
                'lead_user_id' => $users['primary']->id,
            ]
        );

        $team->members()->syncWithoutDetaching([
            $users['primary']->id,
            $users['manager']->id,
            $users['finance']->id,
        ]);
    }

    private function seedTasks(array $users): void
    {
        $rows = [
            ['title' => 'Review attendance sync output', 'description' => 'Check local Laravel attendance data against Auth2-backed user list.', 'status' => 'todo', 'priority' => 'high', 'due_date' => now()->addDay()->toDateString(), 'sort_order' => 1, 'created_by_user_id' => $users['primary']->id, 'assigned_to_user_id' => $users['primary']->id],
            ['title' => 'Prepare leave approval summary', 'description' => 'Collect pending leave requests for HR review.', 'status' => 'in_progress', 'priority' => 'medium', 'due_date' => now()->addDays(2)->toDateString(), 'sort_order' => 1, 'created_by_user_id' => $users['manager']->id, 'assigned_to_user_id' => $users['manager']->id],
            ['title' => 'Confirm claim payout batch', 'description' => 'Verify seeded claim for finance test flow.', 'status' => 'done', 'priority' => 'low', 'due_date' => now()->subDay()->toDateString(), 'sort_order' => 1, 'created_by_user_id' => $users['finance']->id, 'assigned_to_user_id' => $users['finance']->id],
        ];

        foreach ($rows as $row) {
            Task::query()->updateOrCreate(
                ['title' => $row['title']],
                $row
            );
        }
    }
}
