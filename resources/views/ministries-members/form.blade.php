<form method="POST" action="{{ route('ministries-members.save', $ministry->id) }}"  role="form" enctype="multipart/form-data">
    @csrf

    <div>
        <x-input-label for="member_id" :value="__('Select member')" />
        <x-dropdown-select
            :options="$allMembers"
            name="member_id"
        />
    </div>

    <div>
        <x-input-label for="role" :value="__('Role')" />
        <x-text-input
            id="role"
            name="role"
            type="text"
            value="{{ request('role') }}"
            class="mt-1 w-full" />
    </div>
    <div>
        <x-input-label for="active" :value="__('Active')" />
        <x-dropdown-select
            id="active"
            name="active"
            :selected="request('active')"
            :options="['1' => __('Yes'), '0' => __('No')]"
            class="mt-1 w-full" />
    </div>

    <x-primary-button>
        {{ __('Save') }}
    </x-primary-button>
</form>
