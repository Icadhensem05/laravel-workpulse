<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'WorkPulse Laravel' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="wp-page">
            <div class="wp-container">
                <div class="wp-shell">
                    @include('partials.sidebar')

                    <div class="wp-main">
                        @include('partials.topbar')

                        <main class="mt-6 space-y-6">
                            {{ $slot }}
                        </main>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
