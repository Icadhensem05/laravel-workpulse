<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WorkspacePageController extends Controller
{
    public function leave(): View
    {
        return view('leave', [
            'balances' => [
                ['label' => 'Annual Leave', 'eligible' => '12.0d', 'taken' => '3.0d', 'balance' => '9.0d'],
                ['label' => 'Sick Leave', 'eligible' => '14.0d', 'taken' => '1.0d', 'balance' => '13.0d'],
                ['label' => 'Compassionate', 'eligible' => '3.0d', 'taken' => '0.0d', 'balance' => '3.0d'],
            ],
            'applications' => [
                ['period' => '2026-04-03 → 2026-04-04', 'type' => 'Annual (full)', 'reason' => 'Family trip', 'status' => 'Pending', 'status_variant' => 'warning', 'submitted' => '2026-03-26 10:12', 'decision' => '-'],
                ['period' => '2026-03-18 → 2026-03-18', 'type' => 'Sick', 'reason' => 'Clinic visit', 'status' => 'Approved', 'status_variant' => 'success', 'submitted' => '2026-03-17 21:44', 'decision' => 'Approved by Manager'],
            ],
        ]);
    }

    public function claims(): View
    {
        $claimItems = [
            ['category' => 'Travelling', 'description' => 'Mileage, toll, and parking for site visit', 'amount' => 'RM 697.00'],
            ['category' => 'Accommodation', 'description' => 'Hotel stay for outstation support', 'amount' => 'RM 0.00'],
            ['category' => 'Entertainment', 'description' => 'Refreshment with client', 'amount' => 'RM 0.00'],
        ];

        return view('claims', [
            'claims' => [
                ['no' => 'CLM-202603-00001', 'employee' => 'Muhammad Irsyad', 'month' => '2026-03', 'total' => 'RM 697.00', 'updated' => '2026-03-25 17:12', 'meta' => '1 item / 0 files'],
                ['no' => 'CLM-202603-00002', 'employee' => 'Nur Syafiqah', 'month' => '2026-03', 'total' => 'RM 410.00', 'updated' => '2026-03-24 11:05', 'meta' => '3 items / 2 files'],
            ],
            'claimItems' => $claimItems,
        ]);
    }

    public function profile(): View
    {
        return view('profile_laravel');
    }

    public function tasks(): View
    {
        return view('tasks', [
            'columns' => [
                'todo' => [
                    ['title' => 'Prepare March attendance export', 'priority' => 'High', 'due' => 'Mar 28'],
                    ['title' => 'Draft claims approval memo', 'priority' => 'Medium', 'due' => 'Mar 29'],
                ],
                'in_progress' => [
                    ['title' => 'Laravel dashboard migration', 'priority' => 'High', 'due' => 'Mar 26'],
                ],
                'review' => [
                    ['title' => 'Review leave allocation rules', 'priority' => 'Medium', 'due' => 'Mar 30'],
                ],
                'done' => [
                    ['title' => 'Tailwind foundation setup', 'priority' => 'Done', 'due' => 'Completed'],
                ],
            ],
        ]);
    }

    public function team(): View
    {
        return view('team_laravel', [
            'members' => [
                ['name' => 'Muhammad Irsyad', 'role' => 'Employee', 'email' => 'irsyad050505@gmail.com', 'status' => 'Online', 'note' => 'Checked in at 08:52 AM'],
                ['name' => 'Nur Syafiqah', 'role' => 'HR', 'email' => 'syafiqah@weststar.com', 'status' => 'Offline', 'note' => 'Last event yesterday at 05:44 PM'],
                ['name' => 'Ahmad Razi', 'role' => 'Manager', 'email' => 'razi@weststar.com', 'status' => 'Online', 'note' => 'Approving leave requests'],
            ],
        ]);
    }

    public function assets(): View
    {
        return view('assets_laravel', [
            'assets' => [
                ['name' => 'Toyota Hilux', 'type' => 'Vehicle', 'status' => 'Active', 'status_variant' => 'success', 'assigned' => 'Operations'],
                ['name' => 'Dell Latitude 5440', 'type' => 'Laptop', 'status' => 'In Use', 'status_variant' => 'info', 'assigned' => 'Muhammad Irsyad'],
                ['name' => 'Samsung 27\" Monitor', 'type' => 'Monitor', 'status' => 'Available', 'status_variant' => 'neutral', 'assigned' => 'IT Store'],
            ],
        ]);
    }

    public function reports(): View
    {
        return view('report_laravel', [
            'summary' => [
                ['label' => 'On-time Rate', 'value' => '92%', 'meta' => 'Selected period'],
                ['label' => 'Avg Break', 'value' => '00:27', 'meta' => 'Per workday'],
                ['label' => 'Working Days', 'value' => '21', 'meta' => 'Current month'],
            ],
            'rows' => [
                ['name' => 'Muhammad Irsyad', 'present' => '21', 'late' => '0', 'leave' => '1', 'hours' => '164h'],
                ['name' => 'Nur Syafiqah', 'present' => '19', 'late' => '1', 'leave' => '2', 'hours' => '152h'],
                ['name' => 'Ahmad Razi', 'present' => '20', 'late' => '0', 'leave' => '1', 'hours' => '160h'],
            ],
        ]);
    }
}
