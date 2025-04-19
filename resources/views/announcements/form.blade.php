<div class="space-y-6">

    <div>
        <x-input-label for="member_id" :value="__('Member')" />
        <x-autocomplete-select
            :options="$members"
            selected="{{ old('member_id', $announcement?->member_id) }}"
            name="member_id"
        />
        <x-input-error class="mt-2" :messages="$errors->get('member_id')" />
    </div>

    <div>
        <x-input-label for="type" :value="__('Type')" />
        <x-autocomplete-select
            :options="$announcementOptions"
            selected="{{ old('type', $announcement?->type) }}"
            name="type"
        />
        <x-input-error class="mt-2" :messages="$errors->get('type')" />
    </div>

    <div>
        <x-input-label for="title" :value="__('Title')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $announcement?->title)" />
        <x-input-error class="mt-2" :messages="$errors->get('title')" />
    </div>

    <div>
        <x-input-label for="description" :value="__('Description')" />
        <textarea id="description" name="description" x-model="description" rows="8"
            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $announcement?->description) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div>
        <x-input-label for="link" :value="__('Link')" />
        <x-text-input id="link" class="block mt-1 w-full" type="text" name="link" x-model="link" :value="old('link', $announcement?->link)"
            autocomplete="link" />
        <x-input-error class="mt-2" :messages="$errors->get('link')" />
    </div>

    <div>
        <x-input-label for="contact" :value="__('Telephone Number')" />
        <x-text-input id="contact" class="block mt-1 w-full" type="text" name="contact" x-model="contact" :value="old('contact', $announcement?->contact)"
            autocomplete="contact" />
        <x-input-error class="mt-2" :messages="$errors->get('contact')" />
    </div>

    <div>
        @if ($announcement?->image_path)
            <a href="{{ asset($announcement->image_path) }}" target="_blank">Download File</a>
        @endif
        <x-input-label for="image_path" :value="__('Upload File')" />
        <input id="image_path" name="image_path" type="file" accept=".pdf,image/*" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" />
        <x-input-error class="mt-2" :messages="$errors->get('image_path')" />
    </div>

    <div>
        <x-input-label for="is_approved" :value="__('Is Approved')" />
        <x-dropdown-select :options="['1' => __('Yes'), '0' => __('No')]" selected="{{ old('is_approved', $announcement?->is_approved) }}" name="is_approved" />
        <x-input-error class="mt-2" :messages="$errors->get('is_approved')" />
    </div>

    <div>
        <x-input-label for="expires_at" :value="__('Expires At')" />
        <x-text-input id="expires_at" name="expires_at" type="date" class="mt-1 block w-full"
            :value="old('expires_at', $announcement?->expires_at)" autocomplete="expires_at" />
        <x-input-error class="mt-2" :messages="$errors->get('expires_at')" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>Submit</x-primary-button>
    </div>
</div>
