<x-guest-layout>
    <div x-data="formData()">
        <!-- Form -->
        <form method="GET" action="{{ route('visitor.register') }}" x-ref="form" role="form"
            enctype="multipart/form-data">
            @csrf
            <div>
                <div class="mb-4">
                    <x-input-label for="created_by" :value="__('Registered by')" />
                    <x-text-input id="created_by" class="block mt-1 w-full" type="text"
                        name="created_by" x-model="created_by" autocomplete="created_by" />
                    <p class="error-message text-red-500 text-xs" x-text="errors.created_by"></p>
                </div>
                <hr>
                <div class="mt-4" style="display: flex; gap: 10px">
                    <div style="flex: 1">
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input :value="old('name')" id="name" class="block mt-1 w-full" type="text"
                            name="name" x-model="name" autocomplete="name" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.name"></p>
                    </div>
                    <div>
                        <x-input-label for="gender" :value="__('Gender')" />
                        <x-dropdown-select :options="$genderOptions" placeholder="" :selected="old('gender')" x-model="gender"
                            name="gender" />
                    </div>
                </div>
                <div class="mt-4">
                    <x-input-label for="city" :value="__('City')" />
                    <x-text-input id="city" class="block mt-1 w-full" type="text"
                        name="city" x-model="city" autocomplete="city" />
                    <p class="error-message text-red-500 text-xs" x-text="errors.city"></p>
                </div>
                <div class="mt-4">
                    <x-input-label for="phone_number" :value="__('Phone Number')" />
                    <div class="relative">
                        <x-text-input id="phone_number" name="phone_number" class="block w-full pr-10" type="text" x-model="phone_number"
                            autocomplete="phone_number" x-ref="phone_number" />
                        <button type="button" x-show="phone_number" @click="phone_number = ''"
                            class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 hover:text-gray-600"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="mt-4">
                    <x-input-label for="group" :value="__('Group')" />
                    <x-dropdown-select
                        :options="$visitorGroupOptions"
                        placeholder=""
                        x-model="group"
                        name="group" />
                </div>
                <div class="mt-4">
                    <x-input-label for="invited_by" :value="__('Invited by')" />
                    <div class="flex gap-[10px]">
                        <div style="flex: 1">
                            <x-dropdown-select
                                :options="$invitedByOptions"
                                placeholder=""
                                x-model="invited_by_other"
                                name="invited_by_other" />
                        </div>
                        <div class="relative content-end" x-show="invited_by_other === '{{ $invitedByDefault }}'">
                            <x-text-input id="invited_by" class="block w-full pr-10" type="text"
                            name="invited_by" x-model="invited_by" autocomplete="invited_by" />
                            <button type="button" x-show="invited_by" @click="invited_by = ''"
                                class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 hover:text-gray-600"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                </div>
                <div class="mt-4 flex justify-between">
                    <x-primary-button type="submit" class="bg-primary hover:bg-[#d8881c]">
                        {{ __('Register') }}
                    </x-primary-button>
                </div>
            </div>
        </form>
    </div>
    <style>
        .error-message {
            margin-top: 0.25rem;
            color: #e53e3e;
            font-size: 0.75rem;
        }

        body {
            background: url("{{ asset('img/background.jpg') }}") repeat !important;
            background-position: center !important;
            background-attachment: fixed !important;
            background-size: 70% !important;
            background-color: #333 !important;
        }
    </style>
    <script src="https://unpkg.com/imask"></script>
    <script>
        function formData() {
            return {
                created_by: @json($visitor->created_by ?? ''),
                name: '',
                gender: '',
                invited_by_other: @json($invitedByOther ?? $invitedByDefault),
                phone_number: @json($visitor->phone_number ?? ''),
                invited_by: @json($visitor->invited_by ?? ''),
                group: '',
                city: @json($visitor->city ?? ''),
                observation: '',
                errors: {
                    name: '',
                    gender: '',
                },
                validateAndSubmit() {
                    if (this.isFormValid()) {
                        this.$refs.form.submit();
                    }
                },
                isFormValid() {
                    this.errors.name = this.name ? '' : 'Campo obrigatório';
                    this.errors.gender = this.gender ? '' : 'Campo obrigatório';
                    return Object.values(this.errors).every(error => error === '');
                }
            }
        }
    </script>



</x-guest-layout>
