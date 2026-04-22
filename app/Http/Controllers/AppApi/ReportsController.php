<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEntry;
use App\Models\Claim;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $month = (string) $request->query('month', now()->format('Y-m'));
        [$year, $monthNumber] = array_pad(explode('-', $month), 2, null);

        return response()->json([
            'success' => true,
            'summary' => [
                'attendance_total' => AttendanceEntry::query()->whereYear('attendance_date', $year)->whereMonth('attendance_date', $monthNumber)->count(),
                'on_time_total' => AttendanceEntry::query()->whereYear('attendance_date', $year)->whereMonth('attendance_date', $monthNumber)->where('status', 'on_time')->count(),
                'avg_break_minutes' => round((float) AttendanceEntry::query()->whereYear('attendance_date', $year)->whereMonth('attendance_date', $monthNumber)->avg('break_minutes'), 2),
                'working_days' => AttendanceEntry::query()->whereYear('attendance_date', $year)->whereMonth('attendance_date', $monthNumber)->distinct('attendance_date')->count('attendance_date'),
                'leave_total' => LeaveRequest::query()->whereYear('start_date', $year)->whereMonth('start_date', $monthNumber)->count(),
                'claim_total' => Claim::query()->where('claim_month', $month)->count(),
                'claim_amount_total' => (float) Claim::query()->where('claim_month', $month)->sum('grand_total'),
            ],
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        $date = (string) $request->query('date', now()->toDateString());

        $rows = AttendanceEntry::query()
            ->with('user')
            ->whereDate('attendance_date', $date)
            ->orderBy('attendance_date')
            ->get()
            ->map(fn (AttendanceEntry $entry) => [
                'user_name' => $entry->user?->display_name,
                'employee_code' => $entry->user?->employee_code,
                'date' => optional($entry->attendance_date)->toDateString(),
                'check_in_at' => $entry->check_in_at ? substr($entry->check_in_at, 0, 5) : null,
                'check_out_at' => $entry->check_out_at ? substr($entry->check_out_at, 0, 5) : null,
                'break_minutes' => (int) $entry->break_minutes,
                'total_minutes' => (int) $entry->total_minutes,
                'status' => $entry->status,
            ])->values();

        return response()->json([
            'success' => true,
            'rows' => $rows,
            'date' => $date,
        ]);
    }

    public function leaves(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'all');

        $rows = LeaveRequest::query()
            ->with(['user', 'leaveType'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->get()
            ->map(fn (LeaveRequest $leave) => [
                'user_name' => $leave->user?->display_name,
                'leave_type' => $leave->leaveType?->code,
                'start_date' => optional($leave->start_date)->toDateString(),
                'end_date' => optional($leave->end_date)->toDateString(),
                'days_count' => (float) $leave->days_count,
                'status' => $leave->status,
            ])->values();

        return response()->json([
            'success' => true,
            'rows' => $rows,
        ]);
    }

    public function claims(Request $request): JsonResponse
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        $rows = Claim::query()
            ->where('claim_month', $month)
            ->latest('id')
            ->get()
            ->map(fn (Claim $claim) => [
                'claim_no' => $claim->claim_no,
                'employee_name' => $claim->employee_name,
                'department' => $claim->department,
                'status' => $claim->status,
                'grand_total' => (float) $claim->grand_total,
                'balance_claim' => (float) $claim->balance_claim,
            ])->values();

        return response()->json([
            'success' => true,
            'rows' => $rows,
            'month' => $month,
        ]);
    }
}
