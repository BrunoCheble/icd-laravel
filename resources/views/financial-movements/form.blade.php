<div x-data="" class="space-y-6">

    <div class="flex flex-row gap-4">
        <!-- Date Field -->
        <div>
            <x-input-label for="date" :value="__('Date')" />
            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', $financialMovement?->date)" autocomplete="date" />
            <x-input-error class="mt-2" :messages="$errors->get('date')" />
        </div>

        <!-- Description Field -->
        <div class="flex-1">
            <x-input-label for="description" :value="__('Description')" />
            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $financialMovement?->description)" autocomplete="description" placeholder="Description" />
            <x-input-error class="mt-2" :messages="$errors->get('description')" />
        </div>

        <!-- Amount Field -->
        <div>
            <x-input-label for="amount" :value="__('Amount')" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" class="mt-1 block w-full" :value="old('amount', $financialMovement?->amount)" autocomplete="amount" placeholder="Amount" />
            <x-input-error class="mt-2" :messages="$errors->get('amount')" />
        </div>
    </div>


    <div class="flex flex-row gap-4">

        <!-- Type Field -->
        <div class="flex-1">
            <x-input-label for="type" :value="__('Type')" />
            <x-dropdown-select
                :options="$types"
                selected="{{ old('type', $financialMovement?->type) }}"
                name="type"
            />
            <x-input-error class="mt-2" :messages="$errors->get('type')" />
        </div>

        <!-- Category Field -->
        <div class="flex-1">
            <x-input-label for="category_id" :value="__('Category')" />
            <x-dropdown-select
                :options="$categories"
                selected="{{ old('wallet_id', $financialMovement?->category_id) }}"
                name="category_id"
            />
            <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
        </div>

    </div>

    <div class="flex flex-row gap-4">

        <!-- Member Field -->
        <div class="flex-1">
            <x-input-label for="member_id" :value="__('Member')" />
            <x-autocomplete-select
                :options="$members"
                selected="{{ old('member_id', $financialMovement?->member_id) }}"
                name="member_id"
            />
            <x-input-error class="mt-2" :messages="$errors->get('member_id')" />
        </div>

        <!-- Ministry Field -->
        <div class="flex-1">
            <x-input-label for="ministry_id" :value="__('Ministry')" />
            <x-dropdown-select
                :options="$ministries"
                selected="{{ old('ministry_id', $financialMovement?->ministry_id) }}"
                name="ministry_id"
            />
            <x-input-error class="mt-2" :messages="$errors->get('ministry_id')" />
        </div>
    </div>
    <!-- Wallet Field -->
    @if (count($wallets) > 1)
        <div>
            <x-input-label for="wallet_id" :value="__('Wallet')" />
            <x-dropdown-select
                :options="$wallets"
                selected="{{ old('wallet_id', $financialMovement?->wallet_id) }}"
                name="wallet_id"
            />
            <x-input-error class="mt-2" :messages="$errors->get('wallet_id')" />
        </div>
    @else
        <input type="hidden" name="wallet_id" value="{{ key($wallets) }}">
    @endif




    <div class="flex flex-row gap-4">
        <div class="flex-1">
            <x-input-label for="notes" :value="__('Notes')" />
            <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full" :value="old('notes', $financialMovement?->notes)"
                autocomplete="notes" placeholder="Notes" />
            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
        </div>
        <div>
            <x-input-label for="processed_date" :value="__('Processed Date')" />
            <x-text-input id="processed_date" name="processed_date" type="date" class="mt-1 block w-full" :value="old('date', $financialMovement?->processed_date)" autocomplete="date" />
            <x-input-error class="mt-2" :messages="$errors->get('processed_date')" />
        </div>
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ $buttonText ?? 'Submit' }}</x-primary-button>
    </div>
</div>
