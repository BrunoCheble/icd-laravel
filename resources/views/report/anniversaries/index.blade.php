<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Anniversaries') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <form action="{{ route('report.anniversaries.index') }}" method="GET" class="mb-4">
                    <label for="month" class="block text-sm font-medium text-gray-700">{{ __('Select Month') }}</label>
                    <select name="month" id="month" onchange="this.form.submit()" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                {{ __(\Carbon\Carbon::create()->month($i)->format('F')) }}
                            </option>
                        @endfor
                    </select>
                </form>

                @include('report.anniversaries.list')

            </div>
        </div>
    </div>
</x-app-layout>
