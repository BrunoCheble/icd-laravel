<x-modal name="modal-delete-{{ $member->id }}" :show="false" focusable>
    <form method="post" action="{{ route('members.destroy', $member->id) }}" class="p-6">
        @csrf
        @method('delete')

        @if ($member->isPending())
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to delete this member?') }}
            </h2>
        @else
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to inactive this member?') }}
            </h2>
        @endif

        <p class="mt-1 text-sm text-gray-600">
            {{ $member->full_name }}
        </p>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Close') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{ $member->isPending() ? __('Delete') : __('Inactive') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
