<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $wallet?->name)" autocomplete="name" placeholder="Name" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="active" :value="__('Active')" />
        <x-dropdown-select
            :options="['1' => __('Yes'), '0' => __('No')]"
            selected="{{ old('active', $wallet?->active) }}"
            name="active"
        />
        <x-input-error class="mt-2" :messages="$errors->get('active')" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Submit</x-primary-button>
    </div>
</div>
