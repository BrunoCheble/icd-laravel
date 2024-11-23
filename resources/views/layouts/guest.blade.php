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
    style="background: url('https://scontent.fopo5-2.fna.fbcdn.net/v/t39.30808-6/436933766_730594475945626_4815031691494786999_n.jpg?_nc_cat=109&ccb=1-7&_nc_sid=5f2048&_nc_eui2=AeEcIBqn1Q3-rQ-FFsezF48CdBiEXTW6SZB0GIRdNbpJkNQnseZxcls2C8fKDRUjjoUs12jmxKOOGXrMxJPPGHxn&_nc_ohc=dVa2q8vGG5kQ7kNvgHXTLFJ&_nc_ht=scontent.fopo5-2.fna&oh=00_AYCd1UnjVcUR42MmBznCZXXPNWqk6MpK7cMuxSYUy8b0tQ&oe=6674E2A2'); background-size: cover"
    class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div class="mt-6">
            <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
        </div>

        <div id="app"
            class="w-full sm:max-w-md mt-6 mb-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>

        <style>
            @keyframes wave-effect {
                0% {
                    box-shadow: 0 0 5px 2px rgba(255, 165, 0, 0.2),
                        /* Laranja claro */
                        inset 0 0 5px rgba(255, 223, 0, 0.5);
                    /* Amarelo */
                }

                50% {
                    box-shadow: 0 0 10px 4px rgba(255, 165, 0, 0.5),
                        /* Laranja */
                        inset 0 0 10px rgba(255, 223, 0, 0.7);
                    /* Amarelo mais forte */
                }

                100% {
                    box-shadow: 0 0 5px 2px rgba(255, 165, 0, 0.2),
                        /* Laranja claro */
                        inset 0 0 5px rgba(255, 223, 0, 0.5);
                    /* Amarelo */
                }
            }

            #app {
                position: relative;
                padding: 2rem;
                border-radius: 10px;
                border: 2px solid rgba(255, 165, 0, 0.5);
                /* Laranja inicial */
                animation: wave-effect 2s infinite ease-in-out;
                transition: all 0.3s ease-in-out;
            }

            /* Adicionar transição para foco */
            #app:hover {
                border-color: rgba(255, 165, 0, 1);
                /* Laranja mais forte ao passar o mouse */
            }
        </style>
    </div>
</body>

</html>
