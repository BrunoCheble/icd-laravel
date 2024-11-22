<div class="space-y-6">
    
    <div>
        <x-input-label for="member_id" :value="__('Member Id')"/>
        <x-text-input id="member_id" name="member_id" type="text" class="mt-1 block w-full" :value="old('member_id', $financial?->member_id)" autocomplete="member_id" placeholder="Member Id"/>
        <x-input-error class="mt-2" :messages="$errors->get('member_id')"/>
    </div>
    <div>
        <x-input-label for="description" :value="__('Description')"/>
        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $financial?->description)" autocomplete="description" placeholder="Description"/>
        <x-input-error class="mt-2" :messages="$errors->get('description')"/>
    </div>
    <div>
        <x-input-label for="amount" :value="__('Amount')"/>
        <x-text-input id="amount" name="amount" type="text" class="mt-1 block w-full" :value="old('amount', $financial?->amount)" autocomplete="amount" placeholder="Amount"/>
        <x-input-error class="mt-2" :messages="$errors->get('amount')"/>
    </div>
    <div>
        <x-input-label for="date" :value="__('Date')"/>
        <x-text-input id="date" name="date" type="text" class="mt-1 block w-full" :value="old('date', $financial?->date)" autocomplete="date" placeholder="Date"/>
        <x-input-error class="mt-2" :messages="$errors->get('date')"/>
    </div>
    <div>
        <x-input-label for="type" :value="__('Type')"/>
        <x-text-input id="type" name="type" type="text" class="mt-1 block w-full" :value="old('type', $financial?->type)" autocomplete="type" placeholder="Type"/>
        <x-input-error class="mt-2" :messages="$errors->get('type')"/>
    </div>
    <div>
        <x-input-label for="category" :value="__('Category')"/>
        <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" :value="old('category', $financial?->category)" autocomplete="category" placeholder="Category"/>
        <x-input-error class="mt-2" :messages="$errors->get('category')"/>
    </div>
    <div>
        <x-input-label for="processed_at" :value="__('Processed At')"/>
        <x-text-input id="processed_at" name="processed_at" type="text" class="mt-1 block w-full" :value="old('processed_at', $financial?->processed_at)" autocomplete="processed_at" placeholder="Processed At"/>
        <x-input-error class="mt-2" :messages="$errors->get('processed_at')"/>
    </div>
    <div>
        <x-input-label for="notes" :value="__('Notes')"/>
        <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full" :value="old('notes', $financial?->notes)" autocomplete="notes" placeholder="Notes"/>
        <x-input-error class="mt-2" :messages="$errors->get('notes')"/>
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Submit</x-primary-button>
    </div>
</div>