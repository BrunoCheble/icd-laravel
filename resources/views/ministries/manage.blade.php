@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Manage Members for {{ $ministry->name }}</h1>

    <h3>Associated Members</h3>
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
                        <form action="{{ route('ministries.removeMember', [$ministry->id, $member->id]) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Remove</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Add Member</h3>
    <form action="{{ route('ministries.save', $ministry->id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="member_id">Select Member</label>
            <select name="member_id" id="member_id" class="form-control">
                <option value="">Select a member</option>
                @foreach($allMembers as $member)
                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <input type="text" name="role" id="role" class="form-control" placeholder="Enter role">
        </div>

        <div class="form-group">
            <label for="active">Active</label>
            <select name="active" id="active" class="form-control">
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save Member</button>
    </form>

    @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
    @endif
</div>
@endsection
