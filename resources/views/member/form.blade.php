<div class="space-y-6">

    <div>
        <x-input-label for="first_name" :value="__('First Name')" />
        <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $member?->first_name)"
            autocomplete="first_name" placeholder="First Name" />
        <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
    </div>
    <div>
        <x-input-label for="middle_name" :value="__('Middle Name')" />
        <x-text-input id="middle_name" name="middle_name" type="text" class="mt-1 block w-full" :value="old('middle_name', $member?->middle_name)"
            autocomplete="middle_name" placeholder="Middle Name" />
        <x-input-error class="mt-2" :messages="$errors->get('middle_name')" />
    </div>
    <div>
        <x-input-label for="last_name" :value="__('Last Name')" />
        <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $member?->last_name)"
            autocomplete="last_name" placeholder="Last Name" />
        <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
    </div>
    <div>
        <x-input-label for="document_number" :value="__('Document Number')" />
        <x-text-input id="document_number" name="document_number" type="text" class="mt-1 block w-full" :value="old('document_number', $member?->document_number)"
            autocomplete="document_number" placeholder="Document Number" />
        <x-input-error class="mt-2" :messages="$errors->get('document_number')" />
    </div>
    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" name="email" type="text" class="mt-1 block w-full" :value="old('email', $member?->email)"
            autocomplete="email" placeholder="Email" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>
    <div>
        <x-input-label for="phone_number" :value="__('Phone Number')" />
        <x-text-input id="phone_number" name="phone_number" type="text" class="mt-1 block w-full" :value="old('phone_number', $member?->phone_number)"
            autocomplete="phone_number" placeholder="Phone Number" />
        <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
    </div>
    <div>
        <x-input-label for="address" :value="__('Address')" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $member?->address)"
            autocomplete="address" placeholder="Address" />
        <x-input-error class="mt-2" :messages="$errors->get('address')" />
    </div>
    <div>
        <x-input-label for="city" :value="__('City')" />
        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $member?->city)"
            autocomplete="city" placeholder="City" />
        <x-input-error class="mt-2" :messages="$errors->get('city')" />
    </div>
    <div>
        <x-input-label for="zip_code" :value="__('Zip Code')" />
        <x-text-input id="zip_code" name="zip_code" type="text" class="mt-1 block w-full" :value="old('zip_code', $member?->zip_code)"
            autocomplete="zip_code" placeholder="Zip Code" />
        <x-input-error class="mt-2" :messages="$errors->get('zip_code')" />
    </div>
    <div>
        <x-input-label for="date_of_birth" :value="__('Date Of Birth')" />
        <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full"
            :value="old('date_of_birth', $member?->date_of_birth)" autocomplete="date_of_birth" placeholder="Date Of Birth" />
        <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
    </div>

    <div>
        <x-input-label for="spouse_id" :value="__('Spouse')" />
        <x-dropdown-select
            :options="$spouses"
            selected="{{ old('spouse_id', $member?->spouse_id) }}"
            placeholder="Choose an option" name="spouse_id"
        />
        <x-input-error class="mt-2" :messages="$errors->get('spouse_id')" />
    </div>

    <div>
        <x-input-label for="father_id" :value="__('Father')" />
        <x-dropdown-select
            :options="$fathers"
            selected="{{ old('father_id', $member?->father_id) }}"
            placeholder="Choose an option" name="father_id"
        />
        <x-input-error class="mt-2" :messages="$errors->get('father_id')" />
    </div>

    <div>
        <x-input-label for="mother_id" :value="__('Mother')" />
        <x-dropdown-select
            :options="$mothers"
            selected="{{ old('mother_id', $member?->mother_id) }}"
            placeholder="Choose an option" name="mother_id"
        />
        <x-input-error class="mt-2" :messages="$errors->get('mother_id')" />
    </div>

    <div>
        <x-input-label for="gender" :value="__('Gender')" />
        <x-dropdown-select
            :options="$genders"
            selected="{{ old('gender', $member?->gender) }}"
            placeholder="Choose an option" name="gender"
        />
        <x-input-error class="mt-2" :messages="$errors->get('gender')" />
    </div>
    <div>
        <x-input-label for="marital_status" :value="__('Marital Status')" />
        <x-dropdown-select
            :options="$maritalStatus"
            selected="{{ old('marital_status', $member?->marital_status) }}"
            placeholder="Choose an option" name="marital_status"
        />
        <x-input-error class="mt-2" :messages="$errors->get('marital_status')" />
    </div>
    <div>
        <x-input-label for="date_joined" :value="__('Date Joined')" />
        <x-text-input id="date_joined" name="date_joined" type="date" class="mt-1 block w-full"
            :value="old('date_joined', $member?->date_joined)" autocomplete="date_joined" placeholder="Date Joined" />
        <x-input-error class="mt-2" :messages="$errors->get('date_joined')" />
    </div>
    <div>
        <x-input-label for="baptism_date" :value="__('Baptism Date')" />
        <x-text-input id="baptism_date" name="baptism_date" type="date" class="mt-1 block w-full"
            :value="old('baptism_date', $member?->baptism_date)" autocomplete="baptism_date" placeholder="Baptism Date" />
        <x-input-error class="mt-2" :messages="$errors->get('baptism_date')" />
    </div>

    <div>
        <x-input-label for="membership_status" :value="__('Status')" />
        <x-dropdown-select
            :options="$membershipStatus"
            selected="{{ old('membership_status', $member?->membership_status) }}"
            name="membership_status"
        />
        <x-input-error class="mt-2" :messages="$errors->get('gender')" />
    </div>

    <div>
        <x-input-label for="notes" :value="__('Notes')" />
        <x-text-input id="notes" name="notes" type="text" class="mt-1 block w-full" :value="old('notes', $member?->notes)"
            autocomplete="notes" placeholder="Notes" />
        <x-input-error class="mt-2" :messages="$errors->get('notes')" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Submit</x-primary-button>
    </div>
</div>
