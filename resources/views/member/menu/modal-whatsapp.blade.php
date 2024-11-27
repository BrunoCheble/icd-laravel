<x-modal name="modal-whatsapp-{{ $member->id }}" :show="false" focusable>
    <div class="relative p-6 flex items-center space-x-4">
        <!-- Foto do Membro -->
        <img class="w-12 h-12 rounded-full object-cover border-2 border-gray-300"
             src="{{ $member->url_photo }}" alt="Member Photo" />

        <!-- Nome do Membro com Título -->
        <div>
            <h2 class="text-xl font-semibold text-gray-900">
                {{ __('Send Whatsapp Message to') }}:
            </h2>
            <h3 class="text-lg font-medium text-gray-700">
                {{ $member->full_name }}
            </h3>
        </div>

        <!-- Botão de Fechar -->
        <button x-on:click="$dispatch('close')"
            class="absolute top-4 right-4 text-gray-600 hover:text-gray-900 focus:outline-none">
            <i class="fas fa-times w-6 h-6"></i>
        </button>
    </div>

    <div class="p-6">
        <ul class="space-y-4">
            <!-- Mensagem de Aniversário -->
            <li class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition duration-200 ease-in-out flex items-center space-x-4">
                <i class="fas fa-gift text-blue-500 w-6 h-6"></i>
                <a href="https://wa.me/351{{ $member->phone_number }}?text=Feliz aniversário, {{ $member->gender == 'M' ? 'irmão' : 'irmã' }} {{ $member->first_name }}! Que o Senhor te abençoe."
                   target="_blank" class="block text-blue-500 font-medium hover:underline">
                    Feliz aniversário, {{ $member->gender == 'M' ? 'irmão' : 'irmã' }} {{ $member->first_name }}!
                </a>
            </li>

            <!-- Mensagem de Oração para Força -->
            <li class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition duration-200 ease-in-out flex items-center space-x-4">
                <i class="fas fa-praying-hands text-blue-500 w-6 h-6"></i>
                <a href="https://wa.me/351{{ $member->phone_number }}?text=Deus te fortaleça, {{ $member->first_name }}! “Tudo posso naquele que me fortalece.” – Filipenses 4:13"
                   target="_blank" class="block text-blue-500 font-medium hover:underline">
                    Deus te fortaleça, {{ $member->first_name }}!
                </a>
            </li>

            <!-- Mensagem de Consolo em tempos difíceis -->
            <li class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition duration-200 ease-in-out flex items-center space-x-4">
                <i class="fas fa-heartbeat text-blue-500 w-6 h-6"></i>
                <a href="https://wa.me/351{{ $member->phone_number }}?text=O Senhor é o meu pastor, nada me faltará. – Salmo 23:1. Estamos orando por você, {{ $member->first_name }}."
                   target="_blank" class="block text-blue-500 font-medium hover:underline">
                    Estamos orando por você, {{ $member->first_name }}.
                </a>
            </li>

            <!-- Mensagem de Saudação e Encorajamento -->
            <li class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition duration-200 ease-in-out flex items-center space-x-4">
                <i class="fas fa-smile-beam text-blue-500 w-6 h-6"></i>
                <a href="https://wa.me/351{{ $member->phone_number }}?text=Que Deus abençoe sua semana, {{ $member->first_name }}! “O Senhor é a minha luz.” – Salmo 27:1"
                   target="_blank" class="block text-blue-500 font-medium hover:underline">
                    Que Deus abençoe sua semana, {{ $member->first_name }}!
                </a>
            </li>
        </ul>

        <hr class="my-6 border-gray-200">
        <h3 class="text-lg font-medium text-gray-700">{{ __('Or send your own message:') }}</h3>
        <form action="https://wa.me/351{{ $member->phone_number }}" method="GET" target="_blank" class="mt-4">
            <div class="flex items-center space-x-4">
                <textarea name="text" class="w-full rounded-md border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Type your message here...">A paz, {{ $member->gender == 'M' ? 'irmão' : 'irmã' }} {{ $member->first_name }}! Tudo bem ?</textarea>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                    {{ __('Send Message') }}
                </button>
            </div>
        </form>
    </div>

</x-modal>
