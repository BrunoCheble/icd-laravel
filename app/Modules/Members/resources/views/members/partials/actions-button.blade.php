{{-- The "⋯" button of a member: opens the actions sheet (members::members.partials.actions, included once on the page).
     $member, $icon (optional, default "fa-ellipsis") --}}
@php
    $pending = $member->pendingInformations ?? app(\App\Modules\Members\Services\GetMemberPendingInformationService::class)->execute($member);
    $whatsapp = \App\Helpers\WhatsAppHelper::url($member->phone_number);
    $memberActions = [
        'name' => $member->first_and_last_name,
        'firstName' => $member->first_name,
        'phone' => $member->phone_number,
        'sibling' => $member->gender === 'M' ? 'irmão' : 'irmã',
        'status' => $member->membership_status,
        'pending' => array_values($pending),
        'whatsapp' => $whatsapp,
        'show' => route('members.show', $member),
        'edit' => route('members.edit', $member),
        'activate' => route('members.activate', $member->id),
        'destroy' => route('members.destroy', $member),
        'photo' => route('members.uploadPhoto', $member->id),
    ];
@endphp
<button type="button" class="m-btn is-ghost is-icon" aria-label="{{ __('Actions') }}: {{ $member->first_and_last_name }}"
    x-data @click="$dispatch('member-actions', @js($memberActions))"><i class="fa-solid {{ $icon ?? 'fa-ellipsis' }}"></i></button>
