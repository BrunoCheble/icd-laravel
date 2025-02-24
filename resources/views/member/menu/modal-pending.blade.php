<x-modal name="modal-pending-{{ $member->id }}" :show="false" focusable>
    <div class="relative p-6 flex items-center space-x-4">
        <!-- Foto do Membro -->
        <img class="w-12 h-12 rounded-full object-cover border-2 border-gray-300"
             src="{{ $member->url_photo }}" alt="Member Photo" />

        <!-- Nome do Membro com Título -->
        <div>
            <h2 class="text-xl font-semibold text-gray-900">
                {{ $member->full_name }}
            </h2>
            <h3 class="text-lg font-medium text-gray-700">
                Tlm: {{ $member->phone_number}}
            </h3>
        </div>

        <!-- Botão de Fechar -->
        <button x-on:click="$dispatch('close')"
            class="absolute top-4 right-4 text-gray-600 hover:text-gray-900 focus:outline-none">
            <i class="fas fa-times w-6 h-6"></i>
        </button>
    </div>

    <div class="p-6">
        <h4 class="text-lg font-medium text-gray-700">
            {{ __('Pending Informations') }}:
        </h4>
        <ul class="space-y-4">
            @foreach($member?->pendingInformations as $information)
                <li class="text-gray-700">
                    {{ $information }};
                </li>
            @endforeach
        </ul>
        <hr class="my-6 border-gray-200">
        <h3 class="text-lg font-medium text-gray-700">{{ __('Or send your own message:') }}</h3>
        <form action="https://wa.me/351{{ $member->phone_number }}" method="GET" target="_blank" class="mt-4">
            <div class="flex items-center space-x-4">
                <textarea name="text" class="w-full rounded-md border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" rows="10" placeholder="Type your message here...">A paz, {{ $member->gender == 'M' ? 'irmão' : 'irmã' }} {{ $member->first_name }}! Tudo bem ? Para concluir o seu registo como membro da igreja, precisamos de algumas informações adicionais. Por favor, responda às perguntas a seguir:
@foreach($member?->pendingInformations as $information)
- {{ $information }};
@endforeach
                </textarea>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                    {{ __('Send Message') }}
                </button>
            </div>
        </form>
    </div>

</x-modal>
