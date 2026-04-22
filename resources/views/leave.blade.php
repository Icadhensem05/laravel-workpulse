<x-layouts.app title="Leave - WorkPulse Laravel" page="leave">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">Leave</p>
                <h1 class="wp-page-title mt-3">Leave Summary</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">Check balances, review recent applications, approve requests, and maintain allocations from the Laravel frontend.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="secondary" data-leave-seed-defaults>Seed Defaults</x-ui.button>
                <x-ui.button data-modal-open="leaveApplyModal">Apply Leave</x-ui.button>
            </div>
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-3" data-leave-balances>
        @foreach ($balances as $balance)
            <section class="wp-panel p-6">
                <p class="wp-label">{{ $balance['label'] }}</p>
                <p class="mt-5 text-3xl font-semibold tracking-tight text-white">{{ $balance['balance'] }}</p>
                <div class="mt-5 space-y-2 text-sm text-ink-300">
                    <p>Eligible: {{ $balance['eligible'] }}</p>
                    <p>Taken: {{ $balance['taken'] }}</p>
                </div>
            </section>
        @endforeach
    </section>

    <div class="hidden rounded-3xl border px-4 py-3 text-sm" data-leave-feedback></div>

    <section class="grid gap-5 xl:grid-cols-[1.35fr_1fr]">
        <x-ui.table-shell title="My Leave Applications" copy="Recent requests and their current status.">
            <x-slot:toolbar>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.select data-leave-my-status :options="['all' => 'All statuses', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']" selected="all" />
                </div>
            </x-slot:toolbar>

            <thead>
                <tr>
                    <th>Period</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Decision</th>
                    <th>Form</th>
                </tr>
            </thead>
            <tbody data-leave-rows>
                @foreach ($applications as $application)
                    <tr>
                        <td>{{ $application['period'] }}</td>
                        <td>{{ $application['type'] }}</td>
                        <td>{{ $application['reason'] }}</td>
                        <td><x-ui.badge :variant="$application['status_variant']">{{ $application['status'] }}</x-ui.badge></td>
                        <td class="wp-table-col-num">{{ $application['submitted'] }}</td>
                        <td>{{ $application['decision'] }}</td>
                        <td class="wp-table-col-action"><x-ui.button variant="secondary">Print</x-ui.button></td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table-shell>

        <section class="wp-panel p-6" data-leave-admin-panel>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="wp-section-title">Approval Inbox</h2>
                    <p class="wp-section-copy mt-2">Pending leave requests for admin review.</p>
                </div>
                <x-ui.badge variant="warning" data-leave-admin-badge>Pending</x-ui.badge>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-ui.select data-leave-admin-status :options="['pending' => 'Pending only', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All statuses']" selected="pending" />
                <x-ui.button variant="secondary" data-leave-admin-refresh>Refresh</x-ui.button>
            </div>

            <div class="mt-6 space-y-4" data-leave-admin-rows>
                <p class="wp-helper">Admin leave inbox is loading.</p>
            </div>
        </section>
    </section>

    <section class="wp-panel p-6" data-leave-allocation-panel>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="wp-section-title">Leave Allocation</h2>
                <p class="wp-section-copy mt-2">Maintain yearly balances per user and leave type.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.input data-leave-allocation-year type="number" value="2026" min="2000" max="2100" class="w-32" />
                <x-ui.button variant="secondary" data-leave-allocation-refresh>Refresh</x-ui.button>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="wp-table wp-table-stack">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Annual</th>
                        <th>Sick</th>
                        <th>Compassionate</th>
                        <th>Marriage</th>
                        <th>Paternity</th>
                        <th>Hospital</th>
                        <th>Unpaid</th>
                        <th>Other</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-leave-allocation-rows>
                    <tr><td colspan="11" class="py-8 text-center text-sm text-ink-400">Allocation list is loading.</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <x-ui.modal-shell id="leaveApplyModal" title="Apply Leave" copy="Leave application shell for annual, sick, unpaid, and other leave types.">
        <div class="space-y-4">
            <section class="wp-form-section">
                <div>
                    <h4 class="wp-form-section-title">Leave Period</h4>
                    <p class="wp-form-section-copy">Choose the period, leave type, and coverage for the request.</p>
                </div>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <x-ui.date-field id="leaveStart" label="Start Date" value="2026-04-03" />
                    <x-ui.date-field id="leaveEnd" label="End Date" value="2026-04-04" />
                    <x-ui.select id="leaveType" label="Type" :options="['annual' => 'Annual', 'sick' => 'Sick', 'unpaid' => 'Unpaid', 'other' => 'Other']" selected="annual" />
                    <x-ui.select id="leavePart" label="Part of Day" :options="['full' => 'Full Day', 'am' => 'Morning', 'pm' => 'Evening']" selected="full" />
                    <div class="md:col-span-2">
                        <x-ui.input id="leaveRelief" label="Person to Relief" value="Nur Syafiqah" />
                    </div>
                </div>
            </section>

            <section class="wp-form-section">
                <div>
                    <h4 class="wp-form-section-title">Reason</h4>
                    <p class="wp-form-section-copy">Explain the purpose of the leave request for approvers.</p>
                </div>
                <div class="mt-5">
                    <x-ui.textarea id="leaveReason" label="Reason">Family trip</x-ui.textarea>
                </div>
            </section>
        </div>
        <div class="mt-6 flex flex-wrap gap-3">
            <x-ui.button variant="secondary" data-modal-close>Cancel</x-ui.button>
            <x-ui.button data-leave-submit>Submit</x-ui.button>
        </div>
    </x-ui.modal-shell>
</x-layouts.app>
