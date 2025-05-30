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

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<!-- background 100% -->
<body
    style="background: url('{{ asset('img/bg-prayer.jpg') }}') center center no-repeat !important; background-size: cover !important;"
    class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-end items-center pt-6 sm:pt-0">
        {{ $slot }}

        <style>

            .visitor {
                background-color: rgba(0, 0, 0, 0.8);
                border-radius: 20px;
                position: relative;
                padding: 2rem;
                color: #fff;
            }
            .visitor strong {
                display: block;
                color: rgba(255, 165, 0, 1);
            }

        </style>
    </div>
</body>

</html>
