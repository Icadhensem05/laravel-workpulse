@php
    $navItems = [
        ['label' => 'Overview', 'icon' => 'OV', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
        ['label' => 'Attendance', 'icon' => 'AT', 'href' => route('attendance'), 'active' => request()->routeIs('attendance')],
        ['label' => 'Leave', 'icon' => 'LV', 'href' => route('leave'), 'active' => request()->routeIs('leave')],
        ['label' => 'Claims', 'icon' => 'CL', 'href' => route('claims'), 'active' => request()->routeIs('claims')],
        ['label' => 'Assets', 'icon' => 'AS', 'href' => route('assets'), 'active' => request()->routeIs('assets')],
        ['label' => 'Team', 'icon' => 'TM', 'href' => route('team'), 'active' => request()->routeIs('team')],
        ['label' => 'Tasks', 'icon' => 'TK', 'href' => route('tasks'), 'active' => request()->routeIs('tasks')],
        ['label' => 'Report', 'icon' => 'RP', 'href' => route('report'), 'active' => request()->routeIs('report')],
        ['label' => 'Admin', 'icon' => 'AD', 'href' => route('admin'), 'active' => request()->routeIs('admin')],
        ['label' => 'Settings', 'icon' => 'ST', 'href' => route('profile'), 'active' => request()->routeIs('profile')],
    ];
@endphp

<aside id="appSidebar" class="wp-sidebar wp-sidebar-hidden lg:!translate-x-0">
    <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-500 text-sm font-bold text-white shadow-[0_10px_24px_rgba(53,100,222,0.35)]">
            WP
        </div>
        <div>
            <p class="text-lg font-semibold tracking-tight text-white">WorkPulse</p>
            <p class="text-sm text-ink-400">Laravel Frontend</p>
        </div>
    </div>

    <div class="mt-8 rounded-3xl border border-white/8 bg-white/[0.03] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-400">Migration Phase</p>
        <p class="mt-2 text-2xl font-semibold text-white">5</p>
        <p class="mt-1 text-sm leading-6 text-ink-300">Global layout components are now being built in the new Laravel workspace.</p>
    </div>

    <nav class="wp-sidebar-nav" aria-label="Primary">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] ?? '#' }}" class="wp-sidebar-link {{ !empty($item['active']) ? 'wp-sidebar-link-active' : '' }}">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-white/8 bg-white/6 text-[11px] font-semibold tracking-wide">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
