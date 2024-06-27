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

<body class="font-sans antialiased">
    <div class="min-h-screen bg-white">
        <div class="flex justify-center flex-col items-center shadow-lg mx-auto">
            <div class="relative w-40 h-40 mt-10 rounded-full shadow-lg overflow-hidden border-8 border-red-700">
                <img class="absolute top-0 left-0 w-full h-full object-cover"
                    src="{{ $member->url_photo }}" alt="Bonnie image" />
            </div>

            <h1 class="text-4xl font-bold w-full text-center">{{ $member->first_and_last_name }}</h1>

            <div class="bg-green-900 w-full text-center py-2">
                <h3 class="text-1xl text-white font-bold">MEMBRO DA IGREJA</h3>
            </div>

        </div>
    </div>
</body>

</html>
