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

<body
    style="background: #333"
    class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-start items-center pt-6 sm:pt-0">
        <div class="mt-6">
            <x-application-logo class="w-40 h-40 fill-current text-gray-500" />
        </div>

        {{ $slot }}

        <style>
/*
            @keyframes wave-effect {
                0% {
                    box-shadow: 0 0 5px 2px rgba(255, 165, 0, 0.2),
                        inset 0 0 5px rgba(255, 223, 0, 0.5);
                }

                50% {
                    box-shadow: 0 0 10px 4px rgba(255, 165, 0, 0.5),
                        inset 0 0 10px rgba(255, 223, 0, 0.7);
                }

                100% {
                    box-shadow: 0 0 5px 2px rgba(255, 165, 0, 0.2),
                        inset 0 0 5px rgba(255, 223, 0, 0.5);
                }
            }
            .visitor {
                position: relative;
                padding: 2rem;
                border-radius: 10px;
                border: 2px solid rgba(255, 165, 0, 0.5);
                animation: wave-effect 2s infinite ease-in-out;
                transition: all 0.3s ease-in-out;
                background-color: #fff;
            }


            .visitor:hover {
                border-color: rgba(255, 165, 0, 1);
            }*/

            .visitor {
                position: relative;
                padding: 2rem;
                color: #fff;
            }
            .visitor strong {
                display: block;
            }

        </style>
    </div>
</body>

</html>
