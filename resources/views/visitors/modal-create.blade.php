<x-modal name="modal-create-visitor" :show="false" focusable>
    <div class="p-3">

        <form action="{{ route('visitors.store') }}" method="POST">
            @csrf

            <dl class="divide-y divide-gray-100">

                <div class="px-2 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Name') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            class="mt-1 w-full"
                            required />
                    </dd>
                </div>

                <div class="px-2 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Gender') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <x-dropdown-select
                            id="gender"
                            name="gender"
                            :selected="old('gender')"
                            placeholder="{{ __('Select gender') }}"
                            :options="['M' => __('Male'), 'F' => __('Female')]"
                            class="mt-1 w-full" />
                    </dd>
                </div>

                <div class="px-2 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Phone Number') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <x-text-input
                            id="phone_number"
                            name="phone_number"
                            type="text"
                            value="{{ old('phone_number') }}"
                            class="mt-1 w-full"
                            required />
                    </dd>
                </div>

                <div class="px-2 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('City') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <x-text-input
                            id="city"
                            name="city"
                            type="text"
                            value="{{ old('city') }}"
                            class="mt-1 w-full"
                            required />
                    </dd>
                </div>

                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Group') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <x-dropdown-select
                            id="group"
                            name="group"
                            :selected="old('group')"
                            placeholder="{{ __('Select group') }}"
                            :options="$visitorGroupOptions"
                            class="mt-1 w-full" />
                    </dd>
                </div>

                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Invited By') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <x-text-input
                            id="invited_by"
                            name="invited_by"
                            type="text"
                            value="{{ old('invited_by') }}"
                            class="mt-1 w-full" />
                    </dd>
                </div>

                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Created By') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <x-text-input
                            id="created_by"
                            name="created_by"
                            type="text"
                            value="{{ old('created_by') }}"
                            class="mt-1 w-full" />
                    </dd>
                </div>

                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Observation') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <textarea name="observation" class="border-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm w-full mt-1" rows="3">{{ old('observation') }}</textarea>
                    </dd>
                </div>

            </dl>

            <div class="mt-4 text-right">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
