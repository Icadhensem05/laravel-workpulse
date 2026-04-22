<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = Carbon::parse($request->query('date', now()->toDateString()))->toDateString();
        $monthStart = Carbon::parse($date)->startOfMonth()->toDateString();
        $monthEnd = Carbon::parse($date)->endOfMonth()->toDateString();

        $todayEntry = AttendanceEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date)
            ->first();

        $recentEntries = AttendanceEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->latest('attendance_date')
            ->limit(7)
            ->get();

        $workingDaysMtd = AttendanceEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->count();

        $teamEntries = AttendanceEntry::query()
            ->whereDate('attendance_date', $date)
            ->get();

        $teamTotal = max(1, $teamEntries->count());
        $teamOnTime = $teamEntries->where('status', 'on_time')->count();

        $timeline = collect([
            $todayEntry?->check_in_at ? [
                'kind' => 'check_in',
                'at' => $date.' '.$todayEntry->check_in_at,
                'source' => 'laravel',
            ] : null,
            ($todayEntry?->break_minutes ?? 0) > 0 ? [
                'kind' => 'break',
                'at' => $date.' '.$todayEntry->check_in_at,
                'source' => 'laravel',
            ] : null,
            $todayEntry?->check_out_at ? [
                'kind' => 'check_out',
                'at' => $date.' '.$todayEntry->check_out_at,
                'source' => 'laravel',
            ] : null,
        ])->filter()->values();

        $recent = $recentEntries->flatMap(function (AttendanceEntry $entry) {
            return collect([
                $entry->check_out_at ? [
                    'event_type' => 'check_out',
                    'occurred_at' => optional($entry->attendance_date)->toDateString().' '.$entry->check_out_at,
                    'source' => 'laravel',
                ] : null,
                $entry->check_in_at ? [
                    'event_type' => 'check_in',
                    'occurred_at' => optional($entry->attendance_date)->toDateString().' '.$entry->check_in_at,
                    'source' => 'laravel',
                ] : null,
            ])->filter();
        })->take(6)->values();

        return response()->json([
            'success' => true,
            'today' => [
                'check_in' => $todayEntry?->check_in_at ? $date.' '.$todayEntry->check_in_at : null,
                'check_out' => $todayEntry?->check_out_at ? $date.' '.$todayEntry->check_out_at : null,
                'break_seconds' => (int) ($todayEntry?->break_minutes ?? 0) * 60,
                'work_seconds' => (int) ($todayEntry?->total_minutes ?? 0) * 60,
            ],
            'working_days_mtd' => $workingDaysMtd,
            'team_rate' => [
                'percent' => (int) round(($teamOnTime / $teamTotal) * 100),
                'on_time' => $teamOnTime,
                'total' => $teamEntries->count(),
            ],
            'timeline' => $timeline,
            'recent' => $recent,
        ]);
    }
}
