@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Users</h1>
            <p class="text-muted mb-0">Manage accounts and roles, and deactivate or restore access.</p>
        </div>
        @can('manage-users')
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Add User
            </a>
        @endcan
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label small">Search</label>
                    <input id="search" name="search" type="search" class="form-control"
                           placeholder="Name or email" value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <label for="role" class="form-label small">Role</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">All roles</option>
                        <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ $role === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="staff" {{ $role === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="user" {{ $role === 'user' ? 'selected' : '' }}>User</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label small">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All accounts</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Date added</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $member)
                        <tr>
                            <td>
                                <i class="bi bi-person-circle text-success"></i>
                                <span class="fw-semibold">{{ $member->name }}</span>
                                @if ($member->id === auth()->id())
                                    <span class="badge bg-success ms-1">You</span>
                                @endif
                            </td>
                            <td>{{ $member->email }}</td>
                            <td>
                                <span class="badge {{ $member->isAdmin() ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($member->role) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $member->isActive() ? 'bg-success' : 'badge-inactive' }}">
                                    {{ ucfirst($member->status) }}
                                </span>
                            </td>
                            <td>{{ $member->created_at?->format('M d, Y') ?? '—' }}</td>
                            <td>
                                @can('manage-users')
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('users.edit', $member) }}" class="btn btn-outline-primary"
                                           title="Edit" aria-label="Edit user">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if ($member->id !== auth()->id())
                                            @if ($member->isActive())
                                                <form method="POST" action="{{ route('users.destroy', $member) }}"
                                                      onsubmit="return confirm('Deactivate {{ addslashes($member->name) }}? They will no longer be able to log in.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger"
                                                            title="Deactivate" aria-label="Deactivate user">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('users.restore', $member) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-outline-success"
                                                            title="Restore" aria-label="Restore user">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No users found.
                                @can('manage-users')
                                    <a href="{{ route('users.create') }}">Add the first one</a>.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
