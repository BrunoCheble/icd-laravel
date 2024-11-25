<x-dropdown align="right" width="48">
    <x-slot name="trigger">
        <button
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
            <div class="ms-1">
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 16 3">
                    <path
                        d="M2 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6.041 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM14 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z" />
                </svg>
            </div>
        </button>
    </x-slot>

    <x-slot name="content">

        <x-dropdown-link :href="route('members.edit', $member->id)">
            {{ __('Edit') }}
        </x-dropdown-link>

        <x-dropdown-link class="font-bold rounded" target="_blank" :href="'https://wa.me/351'.$member->phone_number.'?text=Parabéns, '.($member->gender == 'M' ? 'irmão ' : 'irmã ').' '.$member->first_name.'!'">
            {{ __('Whatsapp') }}
        </x-dropdown-link>

    </x-slot>
</x-dropdown>
