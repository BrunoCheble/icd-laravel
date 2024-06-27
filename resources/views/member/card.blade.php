<div class="w-full {{ $member->isInactived() ? 'bg-red-100' : ($member->isPending() ? 'bg-yellow-100' : 'bg-white') }} border border-gray-200 rounded-lg shadow">
    <div class="flex justify-end px-4 pt-4">
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
                            created_at: '{{ $member->created_at_formatted }}'
                        };
                        $dispatch('open-modal', 'modal-info');
                    ">
                    {{ __('Details') }}
                </x-dropdown-link>

                <x-dropdown-link :href="route('members.edit', $member->id)">
                    {{ __('Edit') }}
                </x-dropdown-link>

                @if (!$member->isInactived())
                    <x-dropdown-link class="bg-red-600 hover:bg-red-700 text-white font-bold rounded"
                        x-on:click.prevent="$dispatch('open-modal', 'modal-delete-{{ $member->id }}')">
                        {{ __('Inactive') }}
                    </x-dropdown-link>
                @endif

            </x-slot>
        </x-dropdown>
    </div>
    <div x-data="" class="flex flex-col items-center pb-10">
        <form x-ref="fileForm" class="hidden" method="POST" action="{{ route('members.uploadPhoto', $member->id) }}"
            role="form" enctype="multipart/form-data">
            {{ method_field('PATCH') }}
            @csrf
            <x-text-input x-ref="fileInput" @change="$refs.fileForm.submit()" id="photo" type="file"
                name="photo" />
        </form>
        <div class="relative w-24 h-24 mb-3 rounded-full shadow-lg overflow-hidden">
            <img @click="$refs.fileInput.click()"
                class="absolute top-0 left-0 w-full h-full object-cover cursor-pointer hover:opacity-75"
                src="{{ $member->url_photo }}" alt="Bonnie image" />
        </div>

        <h5 class="mb-1 text-xl font-medium text-gray-900">{{ $member->first_and_last_name }}</h5>
        <span class="text-sm text-gray-500">
            {{ $member->phone_number }}
        </span>

    </div>
</div>


<x-modal name="modal-delete-{{ $member->id }}" :show="false" focusable>
    <form method="post" action="{{ route('members.destroy', $member->id) }}" class="p-6">
        @csrf
        @method('delete')

        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Are you sure you want to inactive this member?') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ $member->full_name }}
        </p>

        <div class="mt-6 flex justify-end">
            <x-secondary-button x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{ __('Inactive Member') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
