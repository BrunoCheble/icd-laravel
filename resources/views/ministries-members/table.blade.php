<table class="table">
    <thead>
        <tr>
            <th>Member ID</th>
            <th>Name</th>
            <th>Role</th>
            <th>Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($associatedMembers as $member)
            <tr>
                <td>{{ $member->id }}</td>
                <td>{{ $member->name }}</td>
                <td>{{ $member->pivot->role }}</td>
                <td>{{ $member->pivot->active ? 'Yes' : 'No' }}</td>
                <td>
                    <form action="{{ route('ministries-members.removeMember', [$ministry->id, $member->id]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Remove</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
