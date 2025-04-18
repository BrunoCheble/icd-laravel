<tr class="even:bg-gray-50">
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->id }}</td>
    <td style="width: 300px;" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->description }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->category?->name }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 {{ $financial->isDebit ? 'text-red-500' : 'text-green-500' }}">{{ $financial->amount_formatted }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->date_formatted }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->type_name }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->member?->first_and_last_name_and_document }}</td>
    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->ministry?->name }}</td>
    <td style="width: 150px;" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $financial->processed_date_formatted }}</td>

    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
        @include('financial-movements.menu.index')
    </td>
</tr>
