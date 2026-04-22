<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\AttendanceEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $fallbackDate = Carbon::parse($request->query('date', now()->toDateString()))->toDateString();
        $startDate = Carbon::parse($request->query('start', $fallbackDate))->toDateString();
        $endDate = Carbon::parse($request->query('end', $fallbackDate))->toDateString();
        $sort = strtolower((string) $request->query('sort', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $rows = AttendanceEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', $sort)
            ->get()
            ->map(fn (AttendanceEntry $entry) => $this->transformEntry($entry))
            ->values();

        return response()->json([
            'success' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rows' => $rows,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        $entry = AttendanceEntry::query()->where('user_id', $user->id)->where('attendance_date', $today)->first();
        $nextAction = $entry?->check_in_at && ! $entry?->check_out_at ? 'check_out' : 'check_in';

        return response()->json([
            'success' => true,
            'date' => $today,
            'entry' => $entry ? $this->transformEntry($entry) : null,
            'next_action' => $nextAction,
        ]);
    }

    public function event(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = Validator::make($request->all(), [
            'action' => ['required', 'string', 'in:check_in,check_out'],
        ])->validate();

        $today = now()->toDateString();
        $time = now()->format('H:i:s');

        $entry = AttendanceEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (! $entry) {
            $entry = new AttendanceEntry([
                'user_id' => $user->id,
                'attendance_date' => $today,
                'updated_by_user_id' => $user->id,
            ]);
        }

        if ($data['action'] === 'check_in') {
            $entry->check_in_at = $entry->check_in_at ?: $time;
            $entry->status = 'on_time';
        }

        if ($data['action'] === 'check_out') {
            abort_if(! $entry->check_in_at, 422, 'Check in is required before check out.');
            $entry->check_out_at = $time;
            $entry->total_minutes = $this->computeTotalMinutes($entry->check_in_at, $entry->check_out_at, (int) $entry->break_minutes);
            $entry->status = 'present';
        }

        $entry->updated_by_user_id = $user->id;
        $entry->save();

        return response()->json([
            'success' => true,
            'message' => 'Attendance event recorded successfully.',
            'entry' => $this->transformEntry($entry),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'check_in_at' => ['nullable', 'date_format:H:i'],
            'check_out_at' => ['nullable', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
        ])->validate();

        $entry = AttendanceEntry::query()->firstOrNew([
            'user_id' => $user->id,
            'attendance_date' => $data['date'],
        ]);

        $entry->check_in_at = $data['check_in_at'] ? $data['check_in_at'].':00' : null;
        $entry->check_out_at = $data['check_out_at'] ? $data['check_out_at'].':00' : null;
        $entry->break_minutes = (int) ($data['break_minutes'] ?? 0);
        $entry->total_minutes = $entry->check_in_at && $entry->check_out_at
            ? $this->computeTotalMinutes($entry->check_in_at, $entry->check_out_at, $entry->break_minutes)
            : 0;
        $entry->status = $entry->check_in_at ? ($entry->check_out_at ? 'present' : 'on_time') : 'absent';
        $entry->remarks = $data['remarks'] ?? null;
        $entry->updated_by_user_id = $user->id;
        $entry->save();

        return response()->json([
            'success' => true,
            'message' => 'Attendance entry saved successfully.',
            'entry' => $this->transformEntry($entry),
        ]);
    }

    public function adminList(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');
        $date = Carbon::parse($request->query('date', now()->toDateString()))->toDateString();
        $users = User::query()->orderBy('first_name')->orderBy('last_name')->get();

        $rows = $users->map(function (User $member) use ($date) {
            $entry = AttendanceEntry::query()
                ->where('user_id', $member->id)
                ->where('attendance_date', $date)
                ->first();

            return [
                'user_id' => $member->id,
                'name' => $member->display_name,
                'email' => $member->email,
                'employee_code' => $member->employee_code,
                'department' => $member->department,
                'entry' => $entry ? $this->transformEntry($entry) : [
                    'attendance_date' => $date,
                    'check_in_at' => null,
                    'check_out_at' => null,
                    'break_minutes' => 0,
                    'total_minutes' => 0,
                    'status' => 'absent',
                    'remarks' => null,
                ],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'date' => $date,
            'rows' => $rows,
        ]);
    }

    public function adminUpdate(Request $request): JsonResponse
    {
        abort_if($request->user()->role !== 'admin', 403, 'Forbidden');
        $data = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'check_in_at' => ['nullable', 'date_format:H:i'],
            'check_out_at' => ['nullable', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ])->validate();

        $entry = AttendanceEntry::query()->firstOrNew([
            'user_id' => $data['user_id'],
            'attendance_date' => $data['date'],
        ]);

        $entry->check_in_at = $data['check_in_at'] ? $data['check_in_at'].':00' : null;
        $entry->check_out_at = $data['check_out_at'] ? $data['check_out_at'].':00' : null;
        $entry->break_minutes = (int) ($data['break_minutes'] ?? 0);
        $entry->total_minutes = $entry->check_in_at && $entry->check_out_at
            ? $this->computeTotalMinutes($entry->check_in_at, $entry->check_out_at, $entry->break_minutes)
            : 0;
        $entry->status = $data['status'] ?? ($entry->check_in_at ? ($entry->check_out_at ? 'present' : 'on_time') : 'absent');
        $entry->remarks = $data['remarks'] ?? null;
        $entry->updated_by_user_id = $request->user()->id;
        $entry->save();

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully.',
            'entry' => $this->transformEntry($entry),
        ]);
    }

    private function computeTotalMinutes(string $checkIn, string $checkOut, int $breakMinutes): int
    {
        $start = Carbon::createFromFormat('H:i:s', $checkIn);
        $end = Carbon::createFromFormat('H:i:s', $checkOut);

        return max(0, $start->diffInMinutes($end) - max(0, $breakMinutes));
    }

    private function transformEntry(AttendanceEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'attendance_date' => optional($entry->attendance_date)->toDateString(),
            'check_in_at' => $entry->check_in_at ? substr($entry->check_in_at, 0, 5) : null,
            'check_out_at' => $entry->check_out_at ? substr($entry->check_out_at, 0, 5) : null,
            'break_minutes' => (int) $entry->break_minutes,
            'total_minutes' => (int) $entry->total_minutes,
            'status' => $entry->status,
            'remarks' => $entry->remarks,
        ];
    }
}
