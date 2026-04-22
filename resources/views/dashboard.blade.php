<x-layouts.app title="Dashboard - WorkPulse Laravel" page="dashboard">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label" data-dashboard-month>{{ $heroMonth }}</p>
                <h1 class="wp-page-title mt-3">{{ $heroTitle }}</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">{{ $heroCopy }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="secondary">Custom Range</x-ui.button>
                <x-ui.button>Check Out</x-ui.button>
            </div>
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-4" data-dashboard-date="{{ $selectedDate }}">
        @foreach ($summary as $item)
            <section class="wp-panel p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="wp-label">{{ $item['label'] }}</p>
                        <p class="mt-5 text-4xl font-semibold tracking-tight text-white"
                           @if ($loop->index === 0) data-summary-checkin @endif
                           @if ($loop->index === 1) data-summary-checkout @endif
                           @if ($loop->index === 2) data-summary-break @endif
                           @if ($loop->index === 3) data-summary-days @endif
                        >{{ $item['value'] }}</p>
                        <p class="wp-helper mt-4">{{ $item['note'] }}</p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-white/8 bg-white/8 text-lg text-brand-100">
                        {{ $item['icon'] }}
                    </span>
                </div>
            </section>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
        <section class="wp-panel p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="wp-section-title">Today Attendance</h2>
                    <p class="wp-section-copy mt-2">{{ \Illuminate\Support\Carbon::parse($selectedDate)->format('l, F j') }}</p>
                </div>
                <x-ui.button variant="ghost">View Calendar</x-ui.button>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                @foreach ($week as $day)
                    <button type="button" class="wp-tab {{ !empty($day['active']) ? 'wp-tab-active' : '' }}">{{ $day['label'] }}</button>
                @endforeach
            </div>

            <div class="mt-8 space-y-4" data-dashboard-timeline>
                @foreach ($timeline as $event)
                    <article class="flex items-center justify-between gap-4 rounded-3xl border border-white/8 bg-white/[0.03] px-5 py-4">
                        <div class="flex items-center gap-4">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-white/8 bg-white/8 text-lg text-brand-100">
                                {{ $event['icon'] }}
                            </span>
                            <div>
                                <p class="text-base font-semibold text-white">{{ $event['title'] }}</p>
                                <p class="wp-helper mt-1">{{ $event['source'] }}</p>
                            </div>
                        </div>
                        <p class="text-2xl font-semibold tracking-tight text-white">{{ $event['time'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-8 grid gap-4 lg:grid-cols-3">
                @foreach ($stats as $stat)
                    <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                        <p class="wp-label">{{ $stat['label'] }}</p>
                        <p class="mt-4 text-2xl font-semibold tracking-tight text-white"
                           @if ($loop->index === 1) data-stat-actual @endif
                        >{{ $stat['value'] }}</p>
                        <p class="wp-helper mt-3">{{ $stat['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="space-y-5">
            <section class="wp-panel p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="wp-section-title">Recent Activity</h2>
                        <p class="wp-section-copy mt-2">Automatic logs from the past 24 hours</p>
                    </div>
                    <x-ui.button variant="ghost">View All</x-ui.button>
                </div>

                <div class="mt-6 space-y-4" data-dashboard-recent>
                    @foreach ($recent as $item)
                        <article class="flex items-start justify-between gap-4 rounded-3xl border border-white/8 bg-white/[0.03] px-4 py-4">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/8 bg-white/8 text-base text-brand-100">
                                    {{ $item['icon'] }}
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-white">{{ $item['title'] }}</p>
                                    <p class="wp-helper mt-1">{{ $item['time'] }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-white">{{ $item['status'] }}</p>
                                <p class="wp-helper mt-1">{{ $item['source'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="wp-panel p-6">
                <p class="wp-section-title">Team Attendance Rate</p>
                <div class="wp-progress-bar mt-5" aria-hidden="true">
                    <div class="wp-progress-fill" data-team-rate></div>
                </div>
                <div class="mt-4 flex items-center justify-between gap-3">
                    <p class="text-3xl font-semibold tracking-tight text-white" data-team-rate-text>{{ $teamRate }}%</p>
                    <x-ui.badge variant="success">On Schedule</x-ui.badge>
                </div>
                <p class="wp-section-copy mt-3">{{ $teamRateSummary }}</p>
            </section>

            <section class="wp-panel p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="wp-section-title">Leave Summary</p>
                        <p class="wp-section-copy mt-2">Current balance snapshot from the employee leave workspace.</p>
                    </div>
                    <x-ui.badge variant="info">{{ $leaveSummary['nextType'] }}</x-ui.badge>
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-4">
                        <p class="wp-label">Remaining</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-white">{{ $leaveSummary['remaining'] }}</p>
                    </div>
                    <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-4">
                        <p class="wp-label">Pending</p>
                        <p class="mt-3 text-2xl font-semibold tracking-tight text-white">{{ $leaveSummary['pending'] }}</p>
                    </div>
                </div>
            </section>
        </aside>
    </section>

    <section class="wp-panel p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <p class="text-sm leading-6 text-ink-200">
                Need to adjust a time entry? Submit a correction request before 10:00 pm.
            </p>
            <x-ui.button>Submit Correction</x-ui.button>
        </div>
    </section>
</x-layouts.app>
