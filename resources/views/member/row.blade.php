<!-- member row from table with -->
<tr>

    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        <img class="h-8 w-8 rounded-full" src="{{ $member->url_photo }}" alt="">
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->full_name }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->document_number }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->contact }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->city }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->age }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
        {{ $member->marital_status_name }}
    </td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 gap-4 flex">
        <a href="{{ route('members.edit', $member->id) }}" title="Edit" class="text-indigo-600 hover:text-indigo-900">
            <i class="fa fa-edit"></i>
        </a>

        <a x-data="" title="Info" class="text-indigo-600 hover:text-indigo-900" href="#"
            x-on:click.prevent="
                        selectedMember = {
                            url_photo: '{{ $member->url_photo }}',
                            full_name: '{{ $member->full_name }}',
                            document_number: '{{ $member->document_number }}',
                            contact: '{{ $member->contact }}',
                            full_address: '{{ $member->full_address }}',
                            age: '{{ $member->age }}',
                            gender_name: '{{ $member->gender_name }}',
                            marital_status_name: '{{ $member->marital_status_name }}',
                            spouse: {{ $member->spouse ? '{ url_photo: \'' . $member->spouse->url_photo . '\', full_name: \'' . $member->spouse->full_name . '\' }' : 'null' }},
                            date_joined: '{{ $member->date_joined }}',
                            baptism_date: '{{ $member->baptism_date }}',
                            father: {{ $member->father ? '{ url_photo: \'' . $member->father->url_photo . '\', first_and_last_name: \'' . $member->father->first_and_last_name . '\' }' : 'null' }},
                            mother: {{ $member->mother ? '{ url_photo: \'' . $member->mother->url_photo . '\', first_and_last_name: \'' . $member->mother->first_and_last_name . '\' }' : 'null' }},
                            notes: '{{ $member->notes }}',
                            created_at: '{{ $member->created_at_formatted }}',
                            updated_at: '{{ $member->updated_at_formatted }}'
                        };
                        $dispatch('open-modal', 'modal-info');
                    ">
            <i class="fas fa-info-circle"></i>
        </a>
        <!-- if has phone_number show link message whatsapp -->
        @if ($member->phone_number)
            <a href="https://wa.me/{{ $member->phone_number }}" title="Message" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                <i class="fab fa-whatsapp"></i>
            </a>
        @endif
    </td>
</tr>
