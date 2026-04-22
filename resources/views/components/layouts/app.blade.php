<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="dark"
    data-legacy-api-base-url="{{ rtrim(config('workpulse.legacy_api_base_url'), '/') }}"
    data-local-api-base-url="{{ url('/app-api') }}"
    data-page="{{ $page ?? '' }}"
    data-attendance-date="{{ $attendanceDate ?? '' }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'WorkPulse Laravel' }}</title>

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
        <div class="wp-page">
            <div class="wp-container">
                <div class="wp-shell">
                    @include('partials.sidebar')

                    <div class="wp-main">
                        @include('partials.topbar')

                        <main id="app-main-content" class="mt-6 space-y-6">
                            <div
                                data-global-feedback
                                class="hidden rounded-3xl border px-4 py-4 text-sm"
                                role="status"
                                aria-live="polite"
                            ></div>
                            {{ $slot }}
                        </main>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
