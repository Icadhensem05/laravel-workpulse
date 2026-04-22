<x-layouts.app title="Reports - WorkPulse Laravel" page="report">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">Analytics</p>
                <h1 class="wp-page-title mt-3">Reports</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">Generate summaries of attendance, punctuality, and working days with reusable report surfaces.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="secondary" data-report-refresh>Refresh</x-ui.button>
                <x-ui.button data-report-export>Download Report</x-ui.button>
            </div>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-3" data-report-summary>
        @foreach ($summary as $item)
            <x-stat-card :label="$item['label']" :value="$item['value']" :meta="$item['meta']" icon="RP" />
        @endforeach
    </section>

    <x-ui.filter-toolbar>
        <div class="grid flex-1 gap-3 md:grid-cols-3">
            <x-ui.date-field data-report-start value="2026-03-01" />
            <x-ui.date-field data-report-end value="2026-03-26" />
            <x-ui.select :options="['attendance' => 'Attendance', 'punctuality' => 'Punctuality', 'working_days' => 'Working Days']" selected="attendance" />
        </div>
        <div class="flex items-center gap-3">
            <x-ui.button variant="secondary" data-report-refresh>Change Filters</x-ui.button>
            <x-ui.dropdown label="Export" :items="['PDF', 'CSV', 'Excel']" />
        </div>
    </x-ui.filter-toolbar>

    <x-ui.table-shell title="Attendance Overview" copy="Table shell for attendance and punctuality reports.">
        <thead>
            <tr>
                <th>Employee</th>
                <th>Present</th>
                <th>Late</th>
                <th>Leave</th>
                <th>Hours</th>
            </tr>
        </thead>
        <tbody data-report-rows>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td>{{ $row['late'] }}</td>
                    <td>{{ $row['leave'] }}</td>
                    <td>{{ $row['hours'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table-shell>
</x-layouts.app>
