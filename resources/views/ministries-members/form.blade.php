<form method="POST" action="{{ route('ministries-members.store', $ministry->id) }}" role="form"
    enctype="multipart/form-data" class="mb-4">
    @csrf

    <x-text-input id="ministry_id" name="ministry_id" type="hidden" value="{{ $ministry->id }}" />

    <x-text-input id="active" name="active" type="hidden" value="1" />

    <div class="flex gap-4">

        <div class="flex-1">
            <x-input-label for="member_id" :value="__('Member')" />
            <x-autocomplete-select :options="$members" value="{{ request('member_id') }}" name="member_id" />
            <x-input-error class="mt-2" :messages="$errors->get('member_id')" />
        </div>

        <div>
            <x-input-label for="role" :value="__('Role')" />
            <x-text-input id="role" name="role" type="text" value="{{ request('role') }}"
                class="mt-1 w-full" />
        </div>

        <x-primary-button>
            {{ __('Add Member') }}
        </x-primary-button>
    </div>
</form>
