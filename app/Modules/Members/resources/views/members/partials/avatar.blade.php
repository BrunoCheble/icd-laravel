{{-- The member's photo (or initials) in a circle. $member, $class (size, e.g. "w-8 h-8") --}}
<span class="relative inline-block shrink-0 overflow-hidden rounded-full bg-gray-100 shadow {{ $class ?? 'w-10 h-10' }}">
    <img class="absolute inset-0 w-full h-full object-cover" src="{{ $member->url_photo }}" alt="{{ $member->first_and_last_name }}" loading="lazy">
</span>
