<x-modal name="modal-whatsapp-{{ $member->id }}" :show="false" focusable>
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Send Whatsapp Message') }}
        </h2>
    </div>
    <ul class="p-6">
        <li>
            <a href="https://wa.me/351{{ $member->phone_number }}?text=Olá, preciso de ajuda!" target="_blank" class="text-blue-500 hover:underline">Olá, preciso de ajuda!</a>
        </li>
    </ul>
</x-modal>
