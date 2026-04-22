<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeaveController extends Controller
{
    public function balances(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = (int) ($request->query('year', now()->year));
        $types = LeaveType::query()->where('is_active', true)->orderBy('display_order')->get();

        $balances = $types->map(function (LeaveType $type) use ($user, $year) {
            $eligible = (float) (LeaveAllocation::query()
                ->where('user_id', $user->id)
                ->where('leave_type_id', $type->id)
                ->where('year', $year)
                ->value('allocated_days') ?? $type->default_days);

            $taken = (float) LeaveRequest::query()
                ->where('user_id', $user->id)
                ->where('leave_type_id', $type->id)
                ->whereYear('start_date', $year)
                ->where('status', 'approved')
                ->sum('days_count');

            return [
                'leave_type' => $type->code,
                'eligible' => $eligible,
                'taken' => $taken,
                'balance' => max(0, round($eligible - $taken, 1)),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'balances' => $balances,
            'year' => $year,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $myOnly = (int) $request->query('my', 0) === 1 || $user->role !== 'admin';
        $status = trim((string) $request->query('status', 'all'));
        $limit = min(max((int) $request->query('limit', 20), 1), 100);

        $rows = LeaveRequest::query()
            ->with(['user', 'leaveType', 'decider'])
            ->when($myOnly, fn ($query) => $query->where('user_id', $user->id))
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (LeaveRequest $leave) => $this->transformLeaveRequest($leave))
            ->values();

        return response()->json([
            'success' => true,
            'rows' => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = Validator::make($request->all(), [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_type' => ['required', 'string', 'exists:leave_types,code'],
            'part_day' => ['nullable', 'string', 'in:full,half_am,half_pm'],
            'person_to_relief' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string'],
        ])->validate();

        $leaveType = LeaveType::query()->where('code', $data['leave_type'])->firstOrFail();
        $daysCount = $this->calculateDaysCount($data['start_date'], $data['end_date'], $data['part_day'] ?? 'full');

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'part_day' => $data['part_day'] ?? 'full',
            'days_count' => $daysCount,
            'person_to_relief' => $data['person_to_relief'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave application submitted successfully.',
            'row' => $this->transformLeaveRequest($leave->fresh(['user', 'leaveType', 'decider'])),
        ], 201);
    }

    public function updateStatus(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $user = $request->user();
        abort_if($user->role !== 'admin', 403, 'Forbidden');

        $data = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:approved,rejected'],
            'comment' => ['nullable', 'string'],
        ])->validate();

        $leaveRequest->forceFill([
            'status' => $data['status'],
            'admin_comment' => $data['comment'] ?? null,
            'decided_by_user_id' => $user->id,
            'decided_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Leave request updated successfully.',
            'row' => $this->transformLeaveRequest($leaveRequest->fresh(['user', 'leaveType', 'decider'])),
        ]);
    }

    public function allocations(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->role !== 'admin', 403, 'Forbidden');

        $year = (int) ($request->query('year', now()->year));
        $types = LeaveType::query()->where('is_active', true)->orderBy('display_order')->pluck('code')->values();
        $users = User::query()->orderBy('first_name')->orderBy('last_name')->get();

        $rows = $users->map(function (User $member) use ($year, $types) {
            $alloc = [];
            foreach ($types as $typeCode) {
                $type = LeaveType::query()->where('code', $typeCode)->first();
                $alloc[$typeCode] = (float) (LeaveAllocation::query()
                    ->where('user_id', $member->id)
                    ->where('leave_type_id', $type->id)
                    ->where('year', $year)
                    ->value('allocated_days') ?? $type->default_days);
            }

            return [
                'user_id' => $member->id,
                'name' => $member->display_name,
                'email' => $member->email,
                'alloc' => $alloc,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'year' => $year,
            'types' => $types,
            'rows' => $rows,
        ]);
    }

    public function saveAllocation(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->role !== 'admin', 403, 'Forbidden');

        $data = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'alloc' => ['required', 'array'],
        ])->validate();

        $types = LeaveType::query()->where('is_active', true)->get()->keyBy('code');

        DB::transaction(function () use ($data, $types) {
            foreach ($data['alloc'] as $typeCode => $days) {
                $type = $types->get($typeCode);
                if (! $type) {
                    continue;
                }

                LeaveAllocation::query()->updateOrCreate(
                    [
                        'user_id' => $data['user_id'],
                        'leave_type_id' => $type->id,
                        'year' => $data['year'],
                    ],
                    [
                        'allocated_days' => max(0, (float) $days),
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Leave allocation saved successfully.',
        ]);
    }

    public function seedDefaults(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->role !== 'admin', 403, 'Forbidden');

        $year = (int) ($request->query('year', now()->year));
        $types = LeaveType::query()->where('is_active', true)->get();
        $users = User::query()->get();

        DB::transaction(function () use ($year, $types, $users) {
            foreach ($users as $member) {
                foreach ($types as $type) {
                    LeaveAllocation::query()->firstOrCreate(
                        [
                            'user_id' => $member->id,
                            'leave_type_id' => $type->id,
                            'year' => $year,
                        ],
                        [
                            'allocated_days' => $type->default_days,
                        ]
                    );
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Default leave allocations seeded successfully.',
            'year' => $year,
        ]);
    }

    private function calculateDaysCount(string $startDate, string $endDate, string $partDay): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $days = (float) $start->diffInDays($end) + 1;

        if ($partDay !== 'full' && $start->equalTo($end)) {
            return 0.5;
        }

        return round($days, 1);
    }

    private function transformLeaveRequest(LeaveRequest $leave): array
    {
        return [
            'id' => $leave->id,
            'user_id' => $leave->user_id,
            'user_name' => $leave->user?->display_name,
            'leave_type' => $leave->leaveType?->code,
            'leave_type_name' => $leave->leaveType?->name,
            'start_date' => optional($leave->start_date)->toDateString(),
            'end_date' => optional($leave->end_date)->toDateString(),
            'part_day' => $leave->part_day,
            'days_count' => (float) $leave->days_count,
            'person_to_relief' => $leave->person_to_relief,
            'reason' => $leave->reason,
            'status' => $leave->status,
            'admin_comment' => $leave->admin_comment,
            'decided_at' => optional($leave->decided_at)->toDateTimeString(),
            'created_at' => optional($leave->created_at)->toDateTimeString(),
        ];
    }
}
