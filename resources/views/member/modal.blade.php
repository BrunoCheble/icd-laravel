<div class="flex items-center align-center">
    <div class="w-1/3 px-4">
        <img class="w-full h-auto rounded-lg shadow-lg" :src="selectedMember.url_photo" alt="Member Photo" />
    </div>
    <div class="w-2/3 p-3">

        <dl class="divide-y divide-gray-100">
            <div class="px-2 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Full Name') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0" x-text="selectedMember.full_name">
                </dd>
            </div>
            <div class="px-2 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Contact') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0" x-text="selectedMember.contact">
                </dd>
            </div>
            <div class="px-2 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Address') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                    x-text="selectedMember.full_address">
                </dd>
            </div>
            <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Age') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0" x-text="selectedMember.age"></dd>
            </div>
            <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Gender') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                    x-text="selectedMember.gender_name">
                </dd>
            </div>
            <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Marital Status') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                    x-text="selectedMember.marital_status_name"></dd>
            </div>
            <template x-if="selectedMember.spouse">
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Spouse') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <div class="flex align-center items-center">
                            <div class="relative w-12 h-12 mr-3 rounded-full shadow-lg overflow-hidden">
                                <img class="absolute top-0 left-0 w-full h-full object-cover"
                                    :src="selectedMember.spouse.url_photo" alt="Spouse Photo" />
                            </div>
                            <div x-text="selectedMember.spouse.full_name"></div>
                        </div>
                    </dd>
                </div>
            </template>
            <template x-if="selectedMember.baptism_date">
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Baptism Date') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                        x-text="selectedMember.baptism_date">
                    </dd>
                </div>
            </template>
            <template x-if="selectedMember.father">
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Father') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <div class="flex align-center items-center">
                            <div class="relative w-12 h-12 mr-3 rounded-full shadow-lg overflow-hidden">
                                <img class="absolute top-0 left-0 w-full h-full object-cover"
                                    :src="selectedMember.father.url_photo" alt="Father Photo" />
                            </div>
                            <div x-text="selectedMember.father.first_and_last_name"></div>
                        </div>
                    </dd>
                </div>
            </template>
            <template x-if="selectedMember.mother">
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Mother') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <div class="flex align-center items-center">
                            <div class="relative w-12 h-12 mr-3 rounded-full shadow-lg overflow-hidden">
                                <img class="absolute top-0 left-0 w-full h-full object-cover"
                                    :src="selectedMember.mother.url_photo" alt="Mother Photo" />
                            </div>
                            <div x-text="selectedMember.mother.first_and_last_name"></div>
                        </div>
                    </dd>
                </div>
            </template>
            <template x-if="selectedMember.notes">
                <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                    <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Notes') }}</dt>
                    <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                        x-text="selectedMember.notes">
                    </dd>
                </div>
            </template>
            <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Created At') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                    x-text="selectedMember.created_at">
                </dd>
            </div>
            <div class="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm font-medium leading-6 text-gray-900">{{ __('Updated At') }}</dt>
                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0"
                    x-text="selectedMember.updated_at">
                </dd>
            </div>
        </dl>
    </div>
</div>
