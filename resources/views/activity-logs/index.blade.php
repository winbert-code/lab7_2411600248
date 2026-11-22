@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
    @php
        $moduleIcons = [
            'Auth' => 'bi-shield-lock',
            'Product' => 'bi-box-seam',
            'Category' => 'bi-tags',
            'Brand' => 'bi-patch-check',
            'User' => 'bi-people',
        ];
    @endphp

    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Activity Log</h1>
        <p class="text-muted mb-0">Track sign-ins and every inventory change made in ByteStock.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card app-card h-100"><div class="card-body">
                <div class="small text-muted">Total actions</div>
                <div class="h4 fw-bold mb-0">{{ number_format($totalActions) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card app-card h-100"><div class="card-body">
                <div class="small text-muted">Actions today</div>
                <div class="h4 fw-bold mb-0">{{ number_format($todayActions) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card app-card h-100"><div class="card-body">
                <div class="small text-muted">Users recorded</div>
                <div class="h4 fw-bold mb-0">{{ number_format($activeUsers) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card app-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label small">Search</label>
                    <input id="search" name="search" type="search" class="form-control"
                           placeholder="Action, user, or details" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <label for="module" class="form-label small">Module</label>
                    <select id="module" name="module" class="form-select">
                        <option value="">All modules</option>
                        @foreach ($modules as $item)
                            <option value="{{ $item }}" {{ $module === $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="action" class="form-label small">Action</label>
                    <select id="action" name="action" class="form-select">
                        <option value="">All actions</option>
                        @foreach ($actions as $item)
                            <option value="{{ $item }}" {{ $action === $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="user_id" class="form-label small">User</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">All users</option>
                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}" {{ $userId == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
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
                        <th>Time</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>
                                <span class="d-block small fw-semibold">{{ $log->created_at?->format('M d, Y h:i A') }}</span>
                                <span class="small text-muted">{{ $log->created_at?->diffForHumans() }}</span>
                            </td>
                            <td>
                                @if ($log->user)
                                    <span class="fw-semibold"><i class="bi bi-person-circle text-success"></i> {{ $log->user->name }}</span>
                                @else
                                    <span class="text-muted">System</span>
                                @endif
                            </td>
                            <td>
                                <i class="bi {{ $moduleIcons[$log->module] ?? 'bi-activity' }} text-success"></i>
                                {{ $log->module }}
                            </td>
                            <td>
                                <span class="badge {{ $log->badgeClass() }}">{{ $log->action }}</span>
                            </td>
                            <td>
                                @if ($log->subjectUrl())
                                    <a href="{{ $log->subjectUrl() }}" class="text-decoration-none">
                                        {{ \Illuminate\Support\Str::limit($log->description, 110) }}
                                    </a>
                                @else
                                    {{ \Illuminate\Support\Str::limit($log->description, 110) }}
                                @endif
                            </td>
                            <td class="small text-muted" title="{{ $log->user_agent }}">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No activity found. Sign in or update the inventory to see entries here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
