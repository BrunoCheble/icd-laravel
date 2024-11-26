@php 
$menu = $menu ?? ['all']; 
@endphp

@include('member.menu.modal-info')
@include('member.menu.modal-whatsapp')
@include('member.menu.modal-delete')

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

        @if (array_intersect($menu, ['details', 'all']))
            <x-dropdown-link class="cursor-pointer" x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'modal-info-{{ $member->id }}');">
                <i class="fas fa-info-circle"></i> 
                {{ __('Details') }}
            </x-dropdown-link>
        @endif

        @if (array_intersect($menu, ['edit', 'all']))
            <x-dropdown-link class="cursor-pointer" :href="route('members.edit', $member->id)">
                <i class="fas fa-edit"></i> 
                {{ __('Edit') }}
            </x-dropdown-link>
        @endif

        @if (array_intersect($menu, ['whatsapp', 'all']))
            <x-dropdown-link class="cursor-pointer" x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'modal-whatsapp-{{ $member->id }}');">
                <i class="fas fa-message"></i> 
                {{ __('Whatsapp') }}
            </x-dropdown-link>
        @endif

        @if (array_intersect($menu, ['active', 'all']) && !$member->isActive())
            <x-dropdown-link class="text-green-600 rounded" :href="route('members.activate', $member->id)">
                <i class="fas fa-check"></i> 
                {{ __('Active') }}
            </x-dropdown-link>
        @endif

        @if (array_intersect($menu, ['delete', 'all']) && ($member->isActive() || $member->isPending()))
            <x-dropdown-link class="text-red-600 rounded cursor-pointer"
                x-on:click.prevent="$dispatch('open-modal', 'modal-delete-{{ $member->id }}')">
                <i class="fas fa-trash"></i> 
                {{ $member->isActive() ? __('Inactive') : __('Delete') }}
            </x-dropdown-link>
        @endif

    </x-slot>
</x-dropdown>
