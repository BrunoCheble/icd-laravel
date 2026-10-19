{{-- The "⋯" button of a visitor: opens the actions sheet (visitors::visitors.partials.actions, included once on the
     page). $visitor, $icon (optional, default "fa-ellipsis") --}}
@php
    $whatsapp = \App\Helpers\WhatsAppHelper::url($visitor->phone_number);
    $visitorActions = [
        'name' => $visitor->name,
        'firstName' => \Illuminate\Support\Str::before(trim($visitor->name), ' '),
        'phone' => $visitor->phone_number,
        'sibling' => $visitor->gender === 'M' ? 'irmão' : 'irmã',
        'status' => $visitor->status,
        'details' => array_filter([
            __('Visit') => $visitor->created_at_formatted,
            __('Gender') => $visitor->gender_name,
            __('Group') => $visitor->group ? $visitor->group_name : null,
            __('City') => $visitor->city,
            __('Invited by') => $visitor->invited_by_name,
            __('Registered by') => $visitor->created_by,
            __('Observation') => $visitor->observation,
        ]),
        'whatsapp' => $whatsapp,
        'edit' => route('visitors.edit', $visitor),
        'activate' => route('visitors.activate', $visitor->id),
        'destroy' => route('visitors.destroy', $visitor),
    ];
@endphp
<button type="button" class="m-btn is-ghost is-icon" aria-label="{{ __('Actions') }}: {{ $visitor->name }}"
    x-data @click="$dispatch('visitor-actions', @js($visitorActions))"><i class="fa-solid {{ $icon ?? 'fa-ellipsis' }}"></i></button>
