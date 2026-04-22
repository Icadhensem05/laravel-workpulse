<header class="wp-topbar">
    <div class="flex items-center gap-3">
        <button
            type="button"
            class="wp-btn-secondary lg:hidden"
            data-sidebar-toggle
            aria-controls="appSidebar"
            aria-expanded="false"
        >
            Menu
        </button>

        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-400">WorkPulse</p>
            <h1 class="mt-1 text-lg font-semibold tracking-tight text-white">Frontend Migration Workspace</h1>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button
            type="button"
            class="wp-header-chip"
            data-theme-toggle
            aria-label="Toggle color theme"
        >
            <span data-theme-toggle-label>Dark Mode</span>
        </button>
        <div class="hidden text-right sm:block">
            <p class="text-sm font-semibold text-white">Muhammad Irsyad</p>
            <p class="text-xs text-ink-400">Employee</p>
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl border border-white/8 bg-white/8 text-sm font-semibold text-white">
            MI
        </div>
        <a href="{{ route('logout') }}" class="wp-btn-secondary">Logout</a>
    </div>
</header>
