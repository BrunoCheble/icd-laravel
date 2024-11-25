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

        <x-dropdown-link x-data=""
            x-on:click.prevent="
                selectedMember = {
                    url_photo: '{{ $member->url_photo }}',
                    full_name: '{{ $member->full_name }}',
                    document_number: '{{ $member->document_number }}',
                    contact: '{{ $member->contact }}',
                    full_address: '{{ $member->full_address }}',
                    age: '{{ $member->age }}',
                    gender_name: '{{ $member->gender_name }}',
                    marital_status_name: '{{ $member->marital_status_name }}',
                    spouse: {{ $member->spouse ? '{ url_photo: \''.$member->spouse->url_photo.'\', full_name: \''.$member->spouse->full_name.'\' }' : 'null' }},
                    date_joined: '{{ $member->date_joined }}',
                    baptism_date: '{{ $member->baptism_date }}',
                    father: {{ $member->father ? '{ url_photo: \''.$member->father->url_photo.'\', first_and_last_name: \''.$member->father->first_and_last_name.'\' }' : 'null' }},
                    mother: {{ $member->mother ? '{ url_photo: \''.$member->mother->url_photo.'\', first_and_last_name: \''.$member->mother->first_and_last_name.'\' }' : 'null' }},
                    notes: '{{ $member->notes }}',
                    created_at: '{{ $member->created_at_formatted }}',
                    updated_at: '{{ $member->updated_at_formatted }}'
                };
                $dispatch('open-modal', 'modal-info');
            ">
            {{ __('Details') }}
        </x-dropdown-link>

        <x-dropdown-link :href="route('members.edit', $member->id)">
            {{ __('Edit') }}
        </x-dropdown-link>

        <x-dropdown-link class="font-bold rounded" target="_blank" :href="'https://wa.me/351'.$member->phone_number.'?text=A paz, '.($member->gender == 'M' ? 'irmão ' : 'irmã ').' '.$member->first_name.'. Tudo bem ?'">
            {{ __('Whatsapp') }}
        </x-dropdown-link>

        @if ($member->isActive() || $member->isPending())
            <x-dropdown-link class="text-red-600 font-bold rounded cursor-pointer"
                x-on:click.prevent="$dispatch('open-modal', 'modal-delete-{{ $member->id }}')">
                {{ $member->isActive() ? __('Inactive') : __('Delete') }}
            </x-dropdown-link>
        @endif

        @if (!$member->isActive())
            <x-dropdown-link class="text-green-600 font-bold rounded" :href="route('members.activate', $member->id)">
                {{ __('Active') }}
            </x-dropdown-link>
        @endif

    </x-slot>
</x-dropdown>
