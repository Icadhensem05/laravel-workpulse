<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __invoke(): View
    {
        $selectedDate = now()->toDateString();

        return view('attendance', [
            'heroEyebrow' => 'Attendance',
            'heroTitle' => 'Daily Attendance',
            'heroCopy' => 'Browse and filter your check-ins, breaks, and check-outs with a cleaner Laravel frontend layout.',
            'selectedDate' => $selectedDate,
            'rangeTabs' => ['Today', 'Last 7 days', 'Last 30 days', 'Custom'],
            'records' => [],
        ]);
    }
}
