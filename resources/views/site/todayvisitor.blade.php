<x-guest-layout>
    <div class="mb-8 text-xl text-center text-gray-600">
        {{ __('Today Visitors') }}
    </div>
    <div id="list" class="text-center">
        @foreach($visitors as $visitedBy => $people)
            <div class="mt-4">
                <div><strong>{{ is_numeric($visitedBy) ? ' ' : $visitedBy }}</strong></div>
                {{ $people }}
            </div>
        @endforeach
    </div>
    <style>
        body {
            /* background com 50% do tamanho menor com opacidade na imagem */
            background: url("{{ asset('img/background.jpg') }}") repeat !important;
            background-position: center !important;
            background-attachment: fixed !important;
            background-size: 70% !important;
            background-color: #333 !important;
        }
        #list > div{
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px
        }
        #list > div:last-child{
            border-bottom: none;
        }
    </style>
</x-guest-layout>
