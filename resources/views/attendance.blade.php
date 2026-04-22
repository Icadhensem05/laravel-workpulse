<x-layouts.app title="Attendance - WorkPulse Laravel" page="attendance" :attendance-date="$selectedDate">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">{{ $heroEyebrow }}</p>
                <h1 class="wp-page-title mt-3">{{ $heroTitle }}</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">{{ $heroCopy }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="secondary" data-attendance-range-open>Select Range</x-ui.button>
                <x-ui.button data-attendance-export>Export CSV</x-ui.button>
            </div>
        </div>
    </section>

    <x-ui.table-shell title="Records" copy="Recent attendance entries in the current range.">
        <x-slot:toolbar>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="secondary" data-attendance-prev aria-label="Previous day" title="Previous day">←</x-ui.button>
                <x-ui.date-field data-attendance-date-input value="{{ $selectedDate }}" class="min-w-44" />
                <x-ui.button variant="secondary" data-attendance-next aria-label="Next day" title="Next day">→</x-ui.button>
                <x-ui.button variant="ghost" data-attendance-today>Today</x-ui.button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="wp-tab wp-tab-active" data-attendance-range="today">Today</button>
                <button type="button" class="wp-tab" data-attendance-range="7d">Last 7 days</button>
                <button type="button" class="wp-tab" data-attendance-range="30d">Last 30 days</button>
                <button type="button" class="wp-tab" data-attendance-range-open>Custom</button>
            </div>
        </x-slot:toolbar>

        <thead>
            <tr>
                <th>Date</th>
                <th>Check In</th>
                <th>Break</th>
                <th>Check Out</th>
                <th>Total</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody data-attendance-rows>
            @foreach ($records as $record)
                <tr>
                    <td>{{ $record['date'] }}</td>
                    <td>{{ $record['check_in'] }}</td>
                    <td>{{ $record['break'] }}</td>
                    <td>{{ $record['check_out'] }}</td>
                    <td class="wp-table-col-num">{{ $record['total'] }}</td>
                    <td>
                        <x-ui.badge :variant="$record['status_variant']">{{ $record['status'] }}</x-ui.badge>
                    </td>
                    <td class="wp-table-col-action">
                        <x-ui.button variant="secondary" data-attendance-edit data-date="{{ $record['date'] }}">Edit</x-ui.button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-ui.table-shell>

    <section class="grid gap-5 xl:grid-cols-[1fr_0.82fr]">
        <section class="wp-panel p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="wp-section-title">Filters</h2>
                    <p class="wp-section-copy mt-2">Live filter controls backed by the Laravel attendance API.</p>
                </div>
                <x-ui.badge variant="info">Live API</x-ui.badge>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <x-ui.search-bar data-attendance-search class="md:col-span-2" placeholder="Search by date or status" />
                <x-ui.select
                    data-attendance-filter
                    label="Event Filter"
                    :options="['all' => 'All events', 'ci' => 'Check In', 'co' => 'Check Out', 'br' => 'Break']"
                    selected="all"
                />
                <x-ui.select
                    data-attendance-sort
                    label="Sort"
                    :options="['desc' => 'Newest first', 'asc' => 'Oldest first']"
                    selected="desc"
                />
            </div>
        </section>

        <section class="wp-panel p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="wp-section-title">Quick Actions</h2>
                    <p class="wp-section-copy mt-2">Check current state, trigger the next attendance event, or make corrections.</p>
                </div>
                <x-ui.badge variant="neutral" data-attendance-next-badge>Loading</x-ui.badge>
            </div>

            <div class="mt-6 space-y-4">
                <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                    <p class="wp-label">Current Attendance State</p>
                    <p class="mt-3 text-base text-ink-200" data-attendance-status-copy>Checking current attendance status...</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-ui.button data-attendance-event>Run Next Action</x-ui.button>
                        <x-ui.button variant="secondary" data-attendance-refresh>Status Refresh</x-ui.button>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                    <p class="wp-label">Corrections</p>
                    <p class="mt-3 text-base text-ink-200">Use the same modals to adjust a daily record or switch into a custom range view.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <x-ui.button variant="ghost" data-attendance-range-open>Custom Range</x-ui.button>
                        <x-ui.button variant="secondary" data-attendance-edit-open>Edit Selected Day</x-ui.button>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <section class="wp-panel hidden p-6" data-attendance-admin-panel>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="wp-label">Admin</p>
                <h2 class="wp-section-title mt-2">Daily Attendance Admin View</h2>
                <p class="wp-section-copy mt-2">Review and update all active users for a selected date.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.date-field data-attendance-admin-date value="{{ $selectedDate }}" class="min-w-44" />
                <x-ui.button variant="secondary" data-attendance-admin-refresh>Refresh</x-ui.button>
                <x-ui.button data-attendance-admin-export>Export Daily</x-ui.button>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="wp-table wp-table-stack">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Work</th>
                        <th>Break</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-attendance-admin-rows>
                    <tr><td colspan="8" class="py-8 text-center text-sm text-ink-400">Admin daily view is loading.</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <x-ui.modal-shell id="attendanceEditModal" title="Edit Attendance" copy="Update the daily check-in, break minutes, and check-out values.">
        <div class="space-y-4">
            <section class="wp-form-section">
                <div>
                    <h4 class="wp-form-section-title">Daily Record</h4>
                    <p class="wp-form-section-copy">Adjust recorded check-in, break, and check-out values for the selected day.</p>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <x-ui.date-field data-attendance-edit-date label="Date" value="{{ $selectedDate }}" />
                    <div></div>
                    <x-ui.input data-attendance-edit-checkin label="Check In" type="time" value="08:52" />
                    <x-ui.input data-attendance-edit-checkout label="Check Out" type="time" value="12:59" />
                    <x-ui.input data-attendance-edit-break label="Break (minutes)" type="number" value="30" min="0" max="600" />
                </div>
            </section>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Cancel</x-ui.button>
            <x-ui.button data-attendance-edit-save>Save</x-ui.button>
        </div>
    </x-ui.modal-shell>

    <x-ui.modal-shell id="attendanceRangeModal" title="Select Range" copy="Switch the listing to a custom start and end date.">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.date-field data-attendance-range-start label="Start" value="2026-03-20" />
            <x-ui.date-field data-attendance-range-end label="End" value="{{ $selectedDate }}" />
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Cancel</x-ui.button>
            <x-ui.button data-attendance-range-apply>Apply</x-ui.button>
        </div>
    </x-ui.modal-shell>

    <x-ui.modal-shell id="attendanceAdminEditModal" title="Admin Update Attendance" copy="Admin can update another user's daily attendance record directly.">
        <div class="space-y-4">
            <x-ui.input data-attendance-admin-user type="hidden" value="" />
            <section class="wp-form-section">
                <div>
                    <h4 class="wp-form-section-title">Admin Attendance Override</h4>
                    <p class="wp-form-section-copy">Update another employee's daily attendance record directly.</p>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <x-ui.date-field data-attendance-admin-edit-date label="Date" value="{{ $selectedDate }}" />
                    <x-ui.select data-attendance-admin-status label="Status" :options="['on_time' => 'On Time', 'late' => 'Late', 'absent' => 'Absent', 'leave' => 'Leave', 'weekend' => 'Weekend']" selected="on_time" />
                    <x-ui.input data-attendance-admin-checkin label="Check In" type="time" value="08:52" />
                    <x-ui.input data-attendance-admin-checkout label="Check Out" type="time" value="17:30" />
                    <x-ui.input data-attendance-admin-break label="Break (minutes)" type="number" value="30" min="0" max="600" />
                </div>
            </section>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Cancel</x-ui.button>
            <x-ui.button data-attendance-admin-save>Save</x-ui.button>
        </div>
    </x-ui.modal-shell>
</x-layouts.app>
