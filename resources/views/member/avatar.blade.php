<form x-ref="fileForm" class="hidden" method="POST" action="{{ route('members.uploadPhoto', $member->id) }}"
    role="form" enctype="multipart/form-data">
    {{ method_field('PATCH') }}
    @csrf
    <x-text-input x-ref="fileInput" @change="$refs.fileForm.submit()" id="photo" type="file"
        name="photo" />
</form>
<div class="relative {{ $class }} rounded-full shadow-lg overflow-hidden">
    <img @click="$refs.fileInput.click()"
        class="absolute z-10 top-0 left-0 w-full h-full object-cover cursor-pointer hover:opacity-75"
        src="{{ $member->url_photo }}" alt="Member Photo" />
</div>
