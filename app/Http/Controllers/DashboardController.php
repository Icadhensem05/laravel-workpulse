<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEntry;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $selectedDate = now()->toDateString();
        $parsedDate = Carbon::parse($selectedDate);
        $entry = $user
            ? AttendanceEntry::query()->where('user_id', $user->id)->whereDate('attendance_date', $selectedDate)->first()
            : null;

        $monthStart = $parsedDate->copy()->startOfMonth()->toDateString();
        $monthEnd = $parsedDate->copy()->endOfMonth()->toDateString();
        $workingDays = $user
            ? AttendanceEntry::query()
                ->where('user_id', $user->id)
                ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                ->distinct('attendance_date')
                ->count('attendance_date')
            : 0;
        $avgBreak = $user
            ? (int) round((float) AttendanceEntry::query()
                ->where('user_id', $user->id)
                ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                ->avg('break_minutes'))
            : 0;

        $summary = [
            [
                'label' => 'Check In',
                'value' => $entry?->check_in_at ? Carbon::createFromFormat('H:i:s', $entry->check_in_at)->format('h:i A') : '-',
                'note' => $entry?->status === 'late' ? 'Marked as late' : 'Marked as on time',
                'icon' => 'IN',
            ],
            [
                'label' => 'Check Out',
                'value' => $entry?->check_out_at ? Carbon::createFromFormat('H:i:s', $entry->check_out_at)->format('h:i A') : '-',
                'note' => 'Latest activity recorded',
                'icon' => 'OUT',
            ],
            [
                'label' => 'Break Time',
                'value' => $avgBreak.'m',
                'note' => 'Average this month',
                'icon' => 'BR',
            ],
            [
                'label' => 'Total Days',
                'value' => (string) $workingDays,
                'note' => 'Working days this month',
                'icon' => 'DY',
            ],
        ];

        $weekStart = $parsedDate->copy()->startOfWeek(Carbon::MONDAY);
        $week = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $parsedDate) {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'label' => $date->format('j D'),
                'active' => $date->isSameDay($parsedDate),
            ];
        })->all();

        $timeline = collect([
            $entry?->check_in_at ? ['title' => 'Check In', 'source' => 'laravel', 'time' => Carbon::createFromFormat('H:i:s', $entry->check_in_at)->format('h:i A'), 'icon' => 'IN'] : null,
            $entry?->check_out_at ? ['title' => 'Check Out', 'source' => 'laravel', 'time' => Carbon::createFromFormat('H:i:s', $entry->check_out_at)->format('h:i A'), 'icon' => 'OUT'] : null,
        ])->filter()->values()->all();

        $totalMinutes = (int) ($entry?->total_minutes ?? 0);
        $stats = [
            ['label' => 'Expected Hours', 'value' => '08h 00m', 'note' => 'Standard shift'],
            ['label' => 'Actual Hours', 'value' => sprintf('%02dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60), 'note' => 'Updated from selected date'],
            ['label' => 'Overtime Eligible', 'value' => $totalMinutes > 480 ? 'Yes' : 'No', 'note' => 'Requires manager approval'],
        ];

        $recentRows = $user
            ? AttendanceEntry::query()->where('user_id', $user->id)->latest('attendance_date')->limit(3)->get()
            : collect();
        $recent = $recentRows->flatMap(function (AttendanceEntry $row) {
            $date = optional($row->attendance_date)->format('M j');

            return collect([
                $row->check_out_at ? ['title' => 'Check Out', 'time' => $date.', '.Carbon::createFromFormat('H:i:s', $row->check_out_at)->format('h:i A'), 'status' => 'Logged', 'source' => 'laravel', 'icon' => 'OUT'] : null,
                $row->check_in_at ? ['title' => 'Check In', 'time' => $date.', '.Carbon::createFromFormat('H:i:s', $row->check_in_at)->format('h:i A'), 'status' => 'Logged', 'source' => 'laravel', 'icon' => 'IN'] : null,
            ])->filter();
        })->take(4)->values()->all();

        $remainingAnnual = 0;
        $pendingLeave = 0;
        if ($user) {
            $allocatedAnnual = (float) LeaveAllocation::query()
                ->where('user_id', $user->id)
                ->where('year', now()->year)
                ->whereHas('leaveType', fn ($query) => $query->where('code', 'annual'))
                ->value('allocated_days');
            $takenAnnual = (float) LeaveRequest::query()
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereYear('start_date', now()->year)
                ->whereHas('leaveType', fn ($query) => $query->where('code', 'annual'))
                ->sum('days_count');
            $remainingAnnual = max(0, $allocatedAnnual - $takenAnnual);
            $pendingLeave = LeaveRequest::query()->where('user_id', $user->id)->where('status', 'pending')->count();
        }

        return view('dashboard', [
            'heroMonth' => strtoupper($parsedDate->format('F Y')),
            'heroTitle' => 'Today is '.$parsedDate->format('l, F j'),
            'heroCopy' => 'Keep a pulse on check-ins, break time, and working days with a single dashboard built for the web.',
            'selectedDate' => $selectedDate,
            'summary' => $summary,
            'week' => $week,
            'timeline' => $timeline,
            'stats' => $stats,
            'recent' => $recent,
            'teamRate' => 0,
            'teamRateSummary' => 'Live local metrics are loaded from the Laravel attendance dataset.',
            'leaveSummary' => [
                'remaining' => number_format($remainingAnnual, 1).'d',
                'pending' => $pendingLeave.' request'.($pendingLeave === 1 ? '' : 's'),
                'nextType' => 'Annual Leave',
            ],
        ]);
    }
}
