<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="dark"
    data-legacy-api-base-url="{{ rtrim(config('workpulse.legacy_api_base_url'), '/') }}"
    data-local-api-base-url="{{ url('/app-api') }}"
    data-page="{{ $page ?? '' }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'CastPulse Laravel' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <script>
            (() => {
                const storageKey = 'workpulse-theme';
                const savedTheme = localStorage.getItem(storageKey);
                const theme = savedTheme === 'light' || savedTheme === 'dark' ? savedTheme : 'dark';
                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-page="{{ $page ?? '' }}">
        <a href="#app-main-content" class="wp-skip-link">Skip to main content</a>
        <div class="wp-auth-page">
            <div class="wp-auth-shell">
                <div
                    data-global-feedback
                    class="hidden rounded-3xl border px-4 py-4 text-sm"
                    role="status"
                    aria-live="polite"
                ></div>

                <header class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500 text-base font-semibold text-white shadow-[0_10px_24px_rgba(53,100,222,0.28)]">
                            WP
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-ink-400">Weststar Engineering</p>
                            <p class="mt-1 text-lg font-semibold tracking-tight text-white">CastPulse Access</p>
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
                        <a href="{{ route('login') }}" class="wp-btn-ghost">Sign In</a>
                    </div>
                </header>

                <main id="app-main-content" class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                    <section class="wp-panel p-8 sm:p-10">
                        {{ $slot }}
                    </section>

                    <aside class="wp-panel-soft p-8 sm:p-10">
                        <p class="wp-label">Frontend Migration Workspace</p>
                        <h1 class="wp-page-title mt-3">Corporate access flow rebuilt in Laravel.</h1>
                        <p class="wp-section-copy mt-4 max-w-xl">
                            This auth workspace now routes through Laravel while Auth2 remains the source of truth for credentials, resets, and account status.
                        </p>

                        <div class="mt-8 grid gap-4">
                            <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                                <p class="wp-label">Connected APIs</p>
                                <p class="mt-3 text-base font-semibold text-white">Laravel auth gateway + Auth2 login, session, reset request, token check, reset complete, logout</p>
                            </div>
                            <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                                <p class="wp-label">Current Theme</p>
                                <p class="mt-3 text-base font-semibold text-white">Dark corporate auth shell</p>
                                <p class="wp-helper mt-2">Aligned with the Laravel migration workspace while keeping feedback and validation consistent.</p>
                            </div>
                        </div>
                    </aside>
                </main>
            </div>
        </div>
    </body>
</html>
