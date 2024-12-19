<tr class="{{ $visitor->isInactive() ? 'bg-red-100' : 'bg-white' }}">
    <!-- Name -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitor->name }}</span>
    </td>

    <!-- Gender -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitor->gender }}</span>
    </td>

    <!-- Phone Number -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitor->phone_number }}</span>
    </td>

    <!-- City -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitor->city }}</span>
    </td>

    <!-- Group -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitorGroupOptions[$visitor->group] ?? $visitor->group }}</span>
    </td>

    <!-- Invited By -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitor->invited_by }}</span>
    </td>

    <!-- Created By -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitor->created_by }}</span>
    </td>

    <!-- Created At -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitor->created_at_formatted }}</span>
    </td>

    <!-- Updated At -->
    <td class="px-6 py-4 whitespace-nowrap">
        <span>{{ $visitor->updated_at_formatted }}</span>
    </td>

    <!-- Actions -->
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 gap-4 flex">
        @include('visitors.menu.index')
    </td>
</tr>
