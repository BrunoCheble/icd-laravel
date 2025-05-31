<x-guest-layout>
    <div x-data="formData()">
        @if (session('success'))
            <div class="alert alert-success">
                <div class="text-center">
                    <p class="text-gray-700 text-sm">{{ session('success') }}</p>
                </div>
            </div>
            <div class="mt-4 flex justify-center">
                <a href="{{ route('site.prayer') }}"
                    class="bg-primary hover:bg-[#d8881c] text-white font-bold py-2 px-4 rounded">
                    REGISTAR NOVO PEDIDO
                </a>
            </div>
        @else
            <!-- Form -->
            <form method="POST" action="{{ route('prayer.register') }}" x-ref="form" role="form"
                enctype="multipart/form-data">
                @csrf
                <div>
                    <div class="mt-4">
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input :value="old('name')" id="name" class="block mt-1 w-full" type="text"
                            name="name" x-model="name" autocomplete="name" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.name"></p>
                    </div>
                    <div class="mt-4">
                        <x-input-label for="request" :value="__('Request')" />
                        <x-text-input :value="old('request')" id="request" class="block mt-1 w-full" type="text"
                            name="request" x-model="request" autocomplete="request" />
                        <p class="error-message text-red-500 text-xs" x-text="errors.request"></p>
                    </div>
                    <div class="mt-4 flex justify-between">
                        <x-primary-button type="submit" class="bg-primary hover:bg-[#d8881c]">
                            {{ __('Register') }}
                        </x-primary-button>
                    </div>
                </div>
            </form>
        @endif
    </div>
    <style>
        .error-message {
            margin-top: 0.25rem;
            color: #e53e3e;
            font-size: 0.75rem;
        }

        body {
            background: url("{{ asset('img/bg-prayer.jpg') }}") repeat !important;
            background-position: center !important;
            background-attachment: fixed !important;
            background-size: 100% !important;
            background-color: #333 !important;
        }
    </style>
    <script src="https://unpkg.com/imask"></script>
    <script>
        function formData() {
            return {
                name: '',
                request: '',
                errors: {
                    name: '',
                    request: '',
                },
                validateAndSubmit() {
                    if (this.isFormValid()) {
                        this.$refs.form.submit();
                    }
                },
                isFormValid() {
                    this.errors.name = this.name ? '' : 'Campo obrigatório';
                    return Object.values(this.errors).every(error => error === '');
                }
            }
        }
    </script>



</x-guest-layout>
