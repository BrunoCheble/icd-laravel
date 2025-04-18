<x-modal name="modal-delete-{{ $financial->id }}" :show="false" focusable>
    <form method="post" action="{{ route('financial-movements.destroy', $financial->id) }}" class="p-6">
        @csrf
        @method('delete')

        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Are you sure you want to delete this transaction?') }}
        </h2>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Close') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{  __('Delete') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
