<x-modal name="modal-delete-{{ $visitor->id }}" :show="false" focusable>
    <form method="post" action="{{ route('visitors.destroy', $visitor->id) }}" class="p-6">
        @csrf
        @method('delete')

        @if ($visitor->isInactive())
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to delete this visitor?') }}
            </h2>
        @else
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to inactive this visitor?') }}
            </h2>
        @endif

        <p class="mt-1 text-sm text-gray-600">
            {{ $visitor->name }}
        </p>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Close') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{ $visitor->isInactive() ? __('Delete') : __('Inactive') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
