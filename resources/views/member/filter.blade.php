<form action="{{ route('members.index') }}" class="sm:flex sm:items-center sm:space-x-4" method="GET">
    <x-dropdown-select id="attribute" name="attribute" :selected="request('attribute')" placeholder="{{ __('Filter by') }}"
        :options="$availableAttributes" />

    <x-text-input id="search" name="search" type="text" placeholder="{{ __('Search') }}" value="{{ request('search') }}" />

    <x-dropdown-select id="membership_status" name="membership_status" :selected="request('membership_status')"
        placeholder="{{ __('Status') }}" :options="$membershipStatus" />

    <div class="">
        <x-primary-button>{{ __('Search') }}</x-primary-button>
    </div>
</form>
