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
    <body style="background: url('https://scontent.fopo5-2.fna.fbcdn.net/v/t39.30808-6/436933766_730594475945626_4815031691494786999_n.jpg?_nc_cat=109&ccb=1-7&_nc_sid=5f2048&_nc_eui2=AeEcIBqn1Q3-rQ-FFsezF48CdBiEXTW6SZB0GIRdNbpJkNQnseZxcls2C8fKDRUjjoUs12jmxKOOGXrMxJPPGHxn&_nc_ohc=dVa2q8vGG5kQ7kNvgHXTLFJ&_nc_ht=scontent.fopo5-2.fna&oh=00_AYCd1UnjVcUR42MmBznCZXXPNWqk6MpK7cMuxSYUy8b0tQ&oe=6674E2A2'); background-size: cover" class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="mt-6">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </div>

            <div class="w-full sm:max-w-md mt-6 mb-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
