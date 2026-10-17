{{-- Form of a lesson: new ($lesson null) or editing one. $song, $maxMegabytes; old input after an error. The type is
     chosen first and only its field shows: the YouTube link or the file. --}}
@php
    $editing = $lesson !== null;
    $mine = $editing ? (int) old('lesson_id') === $lesson->id : ! old('lesson_id') && old('title') !== null;
    $value = fn (string $field) => $mine ? old($field) : ($editing ? $lesson->{$field} : null);
    $type = $value('type') ?: 'youtube';
    $accept = [
        'audio' => '.mp3,.m4a,.aac,.wav,.ogg,.oga,.opus,audio/*',
        'pdf'   => '.pdf,application/pdf',
        'image' => '.jpg,.jpeg,.png,.gif,.webp,image/*',
    ];
    $hasFile = $editing && $lesson->file_path;
@endphp
<form method="POST" enctype="multipart/form-data" class="lesson-form" x-data="{ type: @js($type) }"
    action="{{ $editing ? route('songs.lessons.update', [$song, $lesson]) : route('songs.lessons.store', $song) }}">
    @csrf
    @if ($editing)
        @method('PUT')
        <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">
    @endif
    <div class="lf-types" role="radiogroup" aria-label="{{ __('Type') }}">
        @foreach (['youtube' => ['fa-brands fa-youtube', 'YouTube'], 'audio' => ['fa-solid fa-music', __('Audio')], 'pdf' => ['fa-solid fa-file-pdf', 'PDF'], 'image' => ['fa-solid fa-image', __('Image')]] as $key => [$icon, $label])
            <label class="lf-type" :class="{ 'is-on': type === @js($key) }">
                <input type="radio" name="type" value="{{ $key }}" x-model="type"> <i class="{{ $icon }}"></i> {{ $label }}
            </label>
        @endforeach
    </div>
    <div class="lf-row">
        <label class="lf-field">
            <span>{{ __('Instrument') }}</span>
            <input type="text" name="instrument" list="lesson-instruments" required maxlength="50" value="{{ $value('instrument') }}" placeholder="{{ __('E.g. Bass') }}">
        </label>
        <label class="lf-field lf-grow">
            <span>{{ __('Title') }}</span>
            <input type="text" name="title" required maxlength="150" value="{{ $value('title') }}" placeholder="{{ __('E.g. Intro riff') }}">
        </label>
    </div>
    <label class="lf-field" x-show="type === 'youtube'">
        <span>{{ __('YouTube link') }}</span>
        <input type="url" name="video_url" maxlength="255" value="{{ $value('video_url') }}" placeholder="https://www.youtube.com/watch?v=…" :required="type === 'youtube'" :disabled="type !== 'youtube'">
    </label>
    @foreach ($accept as $key => $types)
        <label class="lf-field" x-show="type === @js($key)">
            <span>
                {{ $key === 'audio' ? __('Audio file') : ($key === 'pdf' ? __('PDF file') : __('Image')) }} · {{ __('up to :size MB', ['size' => $maxMegabytes]) }}
                @if ($hasFile && $lesson->type === $key)
                    · {{ __('now: :name (send another to replace it)', ['name' => $lesson->file_name]) }}
                @endif
            </span>
            <input type="file" name="file" accept="{{ $types }}" :disabled="type !== @js($key)"
                @if (! ($hasFile && $lesson->type === $key)) :required="type === @js($key)" @endif>
        </label>
    @endforeach
    <label class="lf-field">
        <span>{{ __('Notes') }}</span>
        <textarea name="notes" rows="2" placeholder="{{ __('Fingering, rhythm, tips…') }}">{{ $value('notes') }}</textarea>
    </label>
    <p class="lf-warn" x-show="@js($editing) && type !== @js($editing ? $lesson->type : null)" x-cloak>{{ __('Changing the type removes what the lesson had (its link or file, and its saved stretches).') }}</p>
    <div class="lf-actions">
        <button type="submit" class="lf-btn is-primary"><i class="fa-solid fa-floppy-disk"></i> {{ __('Save') }}</button>
        <button type="button" class="lf-btn" @click="{{ $editing ? 'editing = false' : 'adding = false' }}">{{ __('Cancel') }}</button>
    </div>
</form>
