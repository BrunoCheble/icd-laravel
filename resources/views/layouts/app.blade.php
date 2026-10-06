<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- font awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Phones: a more compact frame (navigation, page title and page spacing), so content starts higher. */
            @media (max-width: 639px) {
                .app-nav-row { height: 3rem; }
                .app-nav-row .app-logo { height: 1.85rem; }
                .app-header > div { padding-top: .6rem; padding-bottom: .6rem; }
                .app-header h2 { font-size: 1rem; line-height: 1.35; }
                .app-main .py-12 { padding-top: .75rem; padding-bottom: .75rem; }
                .app-main .p-4.shadow { padding: .75rem; }
                /* Page action buttons side by side instead of one per line */
                .app-main .mt-4.sm\:ml-16 { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }
                .app-main .mt-4.sm\:ml-16 > * { margin-top: 0 !important; }
                .app-main .mt-4.sm\:ml-16 > a.block, .app-main .mt-4.sm\:ml-16 > button.block { display: inline-flex; align-items: center; gap: .35rem; width: auto; }
                /* Lower table rows */
                .app-main td.py-4 { padding-top: .6rem; padding-bottom: .6rem; }
                .app-main th.py-3, .app-main th.py-3\.5 { padding-top: .5rem; padding-bottom: .5rem; }
                .app-main .mt-8 { margin-top: 1rem; }
                .app-main .mt-6 { margin-top: .75rem; }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="app-header bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="app-main">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
