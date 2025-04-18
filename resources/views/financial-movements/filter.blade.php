<div x-data="{ showModal: false }">
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

            <form action="{{ route('members.index') }}" method="GET" class="space-y-4">

                <!-- Status -->
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <x-input-label for="membership_status" :value="__('Status')" />
                        <x-dropdown-select
                            id="date_interval"
                            name="date_interval"
                            :selected="request('date_interval')"
                            placeholder="{{ __('Select an interval') }}"
                            :options="[
                                'date' => __('Date range'),
                                'processed_date' => __('Processed date range')
                            ]"
                            class="mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="date_from" :value="__('From')" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="old('date_from', request('date_from'))" autocomplete="date_from" />
                        <x-input-error class="mt-2" :messages="$errors->get('date_from')" />
                    </div>
                    <div>
                        <x-input-label for="date_to" :value="__('To')" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="old('date_to', request('date_to'))" autocomplete="date_to" />
                        <x-input-error class="mt-2" :messages="$errors->get('date_to')" />
                    </div>
                </div>

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
                    <x-text-input
                        id="search"
                        name="search"
                        type="text"
                        value="{{ request('search') }}"
                        class="mt-1 w-full" />
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
                    <a href="{{ route('members.index') }}"
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
