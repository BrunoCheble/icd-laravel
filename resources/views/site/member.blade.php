<x-guest-layout>
    @if (session('success'))
        <div class="alert alert-success">
            <img class="block w-auto" src="{{ asset('img/success.jpg') }}" alt="">
        </div>
        <div class="mt-4 flex justify-center">
            <a href="{{ route('site.member') }}"
                class="bg-primary hover:bg-[#d8881c] text-white font-bold py-2 px-4 rounded">
                REGISTAR NOVO MEMBRO
            </a>
        </div>
    @else
        <div x-data="formData()" x-init="init()">
            <!-- Steps Navigation -->
            <div class="mb-8">
                <ul class="flex justify-center items-center text-small">
                    <template x-for="n in 3" :key="n">
                        <li class="flex items-center mx-2">
                            <button type="button" @click="setStep(n)" :class="{ 'text-primary': step >= n }"
                                class="flex items-center focus:outline-none text-xs">
                                <span :class="step >= n ? 'bg-primary text-white' : 'bg-gray-200 text-black'"
                                    class="w-6 h-6 flex items-center justify-center rounded-full mr-2 transition-colors duration-300 ease-in-out"
                                    x-text="n"></span>
                                <span class="hidden md:block">
                                    <template x-if="n === 1">@lang('Personal Info')</template>
                                    <template x-if="n === 2">@lang('Address')</template>
                                    <template x-if="n === 3">@lang('Confirm')</template>
                                </span>
                            </button>
                            <span x-show="n < 3" class="w-8 border-t"
                                :class="step > n ? 'border-primary' : 'border-gray-300'"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('member.register') }}" @submit.prevent="validateAndSubmit"
                x-ref="form" role="form" enctype="multipart/form-data">
                @csrf
                <!-- Step 1: Personal Info -->
                <div x-show="step === 1">
                    <div>
                        <x-input-label for="full_name" :value="__('Full Name')" />
                        <x-text-input :value="old('full_name')" id="full_name" class="block mt-1 w-full" type="text"
                            name="full_name" x-model="full_name" autofocus autocomplete="full_name" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.full_name"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="gender" :value="__('Gender')" />
                        <x-dropdown-select :options="[__('Male') => __('Male'), __('Female') => __('Female')]" selected="{{ old('gender') }}" x-model="gender"
                            placeholder="" name="gender" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.gender"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="birthdate" :value="__('Date of Birth')" />
                        <x-text-input :value="old('birthdate')" id="birthdate" class="block mt-1 w-full" type="date"
                            name="birthdate" x-model="birthdate" autocomplete="birthdate" placeholder="" />
                        <x-input-error class="mt-2" :messages="$errors->get('birthdate')" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.birthdate"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="document_number" :value="__('NIF')" />

                        <x-text-input :value="old('document_number')" id="document_number" class="block mt-1 w-full" type="text"
                            name="document_number" x-model="document_number" autocomplete="document_number"
                            x-ref="document_number" @change="checkDocumentNumber" />

                        <x-input-error class="mt-2" :messages="$errors->get('document_number')" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.document_number"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="marital_status" :value="__('Marital Status')" />
                        <x-dropdown-select :options="[
                            __('Single') => __('Single'),
                            __('Married') => __('Married'),
                            __('Divorced') => __('Divorced'),
                            __('Widowed') => __('Widowed'),
                        ]" selected="{{ old('marital_status') }}"
                            x-model="marital_status" placeholder="" name="marital_status" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.marital_status"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="phone_number" :value="__('Phone Number')" />
                        <x-text-input :value="old('phone_number')" id="phone_number" class="block mt-1 w-full" type="text"
                            name="phone_number" x-model="phone_number" autocomplete="phone_number"
                            x-ref="phone_number" />
                        <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.phone_number"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input :value="old('email')" id="email" class="block mt-1 w-full" type="email"
                            name="email" x-model="email" autocomplete="email" />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.email"></p>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <x-primary-button type="button" @click="setStep(2)" class="bg-primary hover:bg-[#d8881c]">
                            {{ __('Next') }}
                        </x-primary-button>
                    </div>
                </div>

                <!-- Step 2: Address -->
                <div x-show="step === 2">
                    <div class="mt-4">
                        <x-input-label for="zip_code" :value="__('Postal Code')" />
                        <x-text-input :value="old('zip_code')" id="zip_code" class="block mt-1 w-full" type="text"
                            name="zip_code" x-model="zip_code" @change="fetchAddressData" autocomplete="zip_code"
                            x-ref="zip_code" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.zip_code"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="city" :value="__('City')" />
                        <x-text-input :value="old('city')" id="city" class="block mt-1 w-full" type="text"
                            name="city" x-model="city" autocomplete="city" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.city"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="address" :value="__('Address')" />
                        <x-text-input :value="old('address')" id="address" class="block mt-1 w-full" type="text"
                            name="address" x-model="address" autocomplete="address" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.address"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="address_number" :value="__('Address Number')" />
                        <x-text-input :value="old('address_number')" id="address_number" class="block mt-1 w-full" type="text"
                            name="address_number" x-model="address_number" autocomplete="address_number" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.address_number"></p>
                    </div>
                    <div class="mt-4 flex justify-between">
                        <x-secondary-button type="button" @click="setStep(1)"
                            class="bg-gray-300 hover:bg-gray-400 text-black">
                            {{ __('Back') }}
                        </x-secondary-button>
                        <x-primary-button type="button" @click="setStep(3)" class="bg-primary hover:bg-[#d8881c]">
                            {{ __('Next') }}
                        </x-primary-button>
                    </div>
                </div>

                <!-- Step 3: Confirm -->
                <div x-show="step === 3">
                    <div class="mt-4">
                        <h2 class="text-lg font-medium">{{ __('Please confirm your details before submitting:') }}</h2>
                        <ul class="mt-4 text-gray-700">
                            <li class="flex justify-between"><strong>{{ __('Full Name') }}:</strong> <span
                                    x-text="full_name"></span></li>
                            <li class="flex justify-between"><strong>{{ __('Phone Number') }}:</strong> <span
                                    x-text="phone_number"></span></li>
                            <li class="flex justify-between"><strong>{{ __('Gender') }}:</strong> <span
                                    x-text="gender"></span></li>
                            <li class="flex justify-between"><strong>{{ __('Marital Status') }}:</strong> <span
                                    x-text="marital_status"></span></li>
                            <li class="flex justify-between"><strong>{{ __('NIF') }}:</strong> <span
                                    x-text="document_number"></span></li>
                            <li class="flex justify-between"><strong>{{ __('Date of Birth') }}:</strong> <span
                                    x-text="formatDate(birthdate)"></span></li>
                            <li class="flex justify-between"><strong>{{ __('Postal Code') }}:</strong> <span
                                    x-text="zip_code"></span></li>
                            <li class="flex justify-between"><strong>{{ __('City') }}:</strong> <span
                                    x-text="city"></span></li>
                            <li class="flex justify-between"><strong>{{ __('Address') }}:</strong> <span
                                    x-text="address"></span></li>
                            <li class="flex justify-between"><strong>{{ __('Address Number') }}:</strong> <span
                                    x-text="address_number"></span></li>
                        </ul>
                    </div>
                    <div class="mt-4 flex justify-between">
                        <x-secondary-button type="button" @click="setStep(2)"
                            class="bg-gray-300 hover:bg-gray-400 text-black">
                            {{ __('Back') }}
                        </x-secondary-button>
                        <x-primary-button type="submit" class="bg-primary hover:bg-[#d8881c]">
                            {{ __('Register') }}
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    @endif
    <style>
        .error-message {
            margin-top: 0.25rem;
            color: #e53e3e;
            /* Vermelho */
            font-size: 0.75rem;
            /* Menor tamanho de fonte */
        }

        body {
            /* background com 50% do tamanho menor com opacidade na imagem */
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
                step: 1,
                full_name: '',
                gender: '',
                marital_status: '',
                phone_number: '',
                email: '',
                document_number: '',
                birthdate: '',
                zip_code: '',
                city: '',
                address: '',
                address_number: '',
                errors: {
                    full_name: '',
                    gender: '',
                    marital_status: '',
                    phone_number: '',
                    email: '',
                    document_number: '',
                    birthdate: '',
                    zip_code: '',
                    city: '',
                    address: '',
                    address_number: '',
                },
                checkDocumentNumber() {
                    fetch(`{{ route('member.checkDocumentNumber', ':document_number') }}`.replace(':document_number', this.document_number))
                    .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                this.errors.document_number = 'Este número de documento já está registrado.';
                            } else {
                                this.errors.document_number = '';
                            }
                        })
                        .catch(error => {
                            this.errors.document_number = 'Erro ao validar o documento. Tente novamente mais tarde.';
                        });
                },
                validateAndSubmit() {
                    if (this.isFormValid()) {

                        this.$refs.form.submit();
                    }
                },
                validateFullName(name) {
                    return name.includes(' ') && name.length >= 5;
                },
                isFormValid() {
                    this.errors.full_name = this.validateFullName(this.full_name) ? '' : 'Campo obrigatório';
                    this.errors.phone_number = this.phone_number && this.phone_number.length === 9 ? '' :
                        'Campo obrigatório';
                    this.errors.document_number = this.document_number && this.document_number.length === 9 && this.errors
                        .document_number === '' ? '' :
                        'Campo obrigatório';
                    this.errors.birthdate = this.birthdate && this.birthdate.length === 10 ? '' : 'Campo obrigatório';
                    this.errors.zip_code = this.zip_code && this.zip_code.length === 8 ? '' : 'Campo obrigatório';
                    this.errors.city = this.city ? '' : 'Campo obrigatório';
                    this.errors.address = this.address ? '' : 'Campo obrigatório';
                    this.errors.address_number = this.address_number ? '' : 'Campo obrigatório';
                    this.errors.marital_status = this.marital_status ? '' : 'Campo obrigatório';
                    this.errors.gender = this.gender ? '' : 'Campo obrigatório';
                    this.errors.email = this.email && this.email.includes('@') ? '' : 'Campo obrigatório';

                    return Object.values(this.errors).every(error => error === '');
                },
                setStep(step) {
                    if (step === 3 && !this.isFormValid()) {
                        return;
                    }
                    this.step = step;
                },
                formatDate(date) {
                    if (!date) return '';
                    return new Date(date).toLocaleDateString('pt-BR');
                },
                fetchAddressData() {
                    if (this.zip_code.length !== 8) {
                        return;
                    }

                    fetch(`https://json.geoapi.pt/cp/${this.zip_code}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data["Localidade"] !== undefined) {
                                this.city = data["Localidade"];
                                this.address = data["partes"][0]["Artéria"];
                            } else {
                                console.error('Failed to fetch address data');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching address data:', error);
                        });
                },
                init() {
                    IMask(this.$refs.zip_code, {
                        mask: '0000-000'
                    });
                    IMask(this.$refs.document_number, {
                        mask: '000000000'
                    });
                    IMask(this.$refs.phone_number, {
                        mask: '000000000'
                    });
                },
            }
        }
    </script>



</x-guest-layout>
