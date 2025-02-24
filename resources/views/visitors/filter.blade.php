<div x-data="{ showModal: false, selectedAttribute: '{{ request('attribute') ?? '' }}', searchOptions: {
    group: {
        'kid': '{{ __("Kid") }}',
        'teen': '{{ __("Teen") }}',
        'youth': '{{ __("Youth") }}',
        'adult': '{{ __("Adult") }}',
        'couple': '{{ __("Couple") }}'
    },
    gender: {
        'M': '{{ __("Male") }}',
        'F': '{{ __("Female") }}'
    }
} }">
    <button
        type="button"
        class="block rounded-md bg-blue-500 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        @click="showModal = true">
        <i class="fa fa-filter"></i>
        {{ __('Filter') }}
    </button>

    <!-- Modal -->
    <div
        x-show="showModal"
        class="fixed inset-0 z-50 bg-gray-900 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg p-6 space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold">{{ __('Filter') }}</h2>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600"
                    @click="showModal = false">
                    &times;
                </button>
            </div>

            <form action="{{ route('visitors.index') }}" method="GET" class="space-y-4">

                <div>
                    <x-input-label for="attribute" :value="__('Filter by')" />
                    <x-dropdown-select
                        id="attribute"
                        name="attribute"
                        x-model="selectedAttribute"
                        :selected="request('attribute')"
                        placeholder="{{ __('Select filter') }}"
                        :options="$availableAttributes"
                        class="mt-1 w-full" />
                </div>
                <div>
                    <x-input-label for="search" :value="__('Search')" />
                    <template x-if="searchOptions[selectedAttribute]">
                        <select
                            id="search"
                            name="search"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <template x-for="[key, value] in Object.entries(searchOptions[selectedAttribute])" :key="key">
                                <option :value="key" x-text="value"></option>
                            </template>
                        </select>
                    </template>
                    <template x-if="!searchOptions[selectedAttribute]">
                        <x-text-input
                            id="search"
                            name="search"
                            type="text"
                            value="{{ request('search') }}"
                            class="mt-1 w-full" />
                    </template>
                </div>
                <div>
                    <x-input-label for="sort" :value="__('Sort by')" />
                    <x-dropdown-select
                        id="sort"
                        name="sort"
                        :selected="request('sort')"
                        placeholder="{{ __('Select order') }}"
                        :options="$availableAttributes"
                        class="mt-1 w-full" />
                </div>
                <div>
                    <x-input-label for="order" :value="__('Order by')" />
                    <x-dropdown-select
                        id="order"
                        name="order"
                        :selected="request('order')"
                        placeholder="{{ __('Select order') }}"
                        :options="[
                            'asc' => __('Ascending'),
                            'desc' => __('Descending'),
                        ]"
                        class="mt-1 w-full" />
                </div>
                <div class="flex justify-end">
                    <!-- clear filters button -->
                    <a href="{{ route('visitors.index') }}"
                        class="text-sm text-gray-600 hover:text-gray-900 mr-4 py-2">
                        {{ __('Clear Filters') }}
                    </a>
                    <x-primary-button>
                        {{ __('Apply Filters') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
