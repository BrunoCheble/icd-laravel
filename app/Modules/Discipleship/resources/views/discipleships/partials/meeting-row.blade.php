{{-- A meeting in a list: day and time, disciples, place, its state. $meeting, $showMentor --}}
@php
    $at = $meeting->localAt()->locale(app()->getLocale());
    $day = $at->isToday() ? __('Today') : ($at->isTomorrow() ? __('Tomorrow') : ($at->isYesterday() ? __('Yesterday') : \Illuminate\Support\Str::ucfirst(rtrim($at->isoFormat('ddd'), '.')) . ', ' . $at->format('d/m' . ($at->isCurrentYear() ? '' : '/Y'))));
@endphp
<a href="{{ route('discipleships.meetings.show', $meeting) }}" class="dm-row">
    <span @class(['dm-date', 'is-today' => $at->isToday() && $meeting->status === 'scheduled', 'is-overdue' => $meeting->isOverdue()])>
        <strong>{{ $at->format('d') }}</strong><small>{{ \Illuminate\Support\Str::ucfirst(rtrim($at->isoFormat('MMM'), '.')) }}</small>
    </span>
    <span class="dm-text">
        <strong>{{ $meeting->disciplesNames() }}</strong>
        <small>
            {{ $day }} · {{ $at->format('H:i') }}@if ($meeting->location) · {{ $meeting->location }}@endif
            @if ($showMentor ?? false) · {{ __('Mentor') }}: {{ $meeting->mentor?->full_name }}@endif
        </small>
    </span>
    @if ($meeting->status === 'held')
        <span class="m-tag is-ok"><i class="fa-solid fa-check"></i> <span class="m-hide-sm">{{ __('Held') }}</span></span>
    @elseif ($meeting->status === 'not_held')
        <span class="m-tag is-danger"><i class="fa-solid fa-xmark"></i> <span class="m-hide-sm">{{ __('Did not take place') }}</span></span>
    @elseif ($meeting->isOverdue())
        <span class="m-tag is-part"><i class="fa-solid fa-circle-question"></i> <span class="m-hide-sm">{{ __('To mark') }}</span></span>
    @endif
    @if ($meeting->comments_count)
        <span class="m-muted dm-comments"><i class="fa-regular fa-comment"></i> {{ $meeting->comments_count }}</span>
    @endif
</a>
