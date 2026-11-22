<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v=5" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">
                <i class="bi bi-cpu"></i> ByteStock
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}"
                           href="{{ route('inventory.index') }}">
                            <i class="bi bi-clipboard-data"></i> Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
                           href="{{ route('products.index') }}">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}"
                           href="{{ route('purchase-orders.index') }}">
                            <i class="bi bi-cart-plus"></i> Purchase Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"
                           href="{{ route('suppliers.index') }}">
                            <i class="bi bi-truck"></i> Suppliers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('supplier-performance.*') ? 'active' : '' }}"
                           href="{{ route('supplier-performance.index') }}">
                            <i class="bi bi-bar-chart-line"></i> Supplier Performance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}"
                           href="{{ route('sales-orders.index') }}">
                            <i class="bi bi-cart-check"></i> Sales Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}"
                           href="{{ route('receipts.index') }}">
                            <i class="bi bi-receipt"></i> Receipts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                           href="{{ route('categories.index') }}">
                            <i class="bi bi-tags"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('brands.*') ? 'active' : '' }}"
                           href="{{ route('brands.index') }}">
                            <i class="bi bi-patch-check"></i> Brands
                        </a>
                    </li>
                    @can('manage-users')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                               href="{{ route('users.index') }}">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>
                    @endcan
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}"
                           href="{{ route('activity-logs.index') }}">
                            <i class="bi bi-clock-history"></i> Activity Log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}"
                           href="{{ route('transactions.index') }}">
                            <i class="bi bi-arrow-left-right"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                           href="{{ route('reports.index') }}">
                            <i class="bi bi-file-earmark-bar-graph"></i> Reports
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item dropdown me-lg-2">
                        <a class="nav-link position-relative" href="#" id="alertsMenu" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                            <i class="bi bi-bell fs-5"></i>
                            @if ($unreadAlertsCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger alert-count">
                                    {{ $unreadAlertsCount }}
                                    <span class="visually-hidden">unread alerts</span>
                                </span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="alertsMenu">
                            <li class="d-flex justify-content-between align-items-center px-3 py-2">
                                <span class="fw-semibold small">Notifications</span>
                                @if ($unreadAlertsCount > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-link btn-sm p-0 small" type="submit">Mark all read</button>
                                    </form>
                                @endif
                            </li>
                            <li><hr class="dropdown-divider my-0"></li>
                            @forelse ($unreadAlerts as $alert)
                                <li>
                                    <a class="dropdown-item py-2" href="{{ $alert->data['url'] ?? route('products.index') }}">
                                        <i class="bi {{ $alert->data['icon'] ?? 'bi-exclamation-triangle' }} text-{{ $alert->data['color'] ?? 'warning' }}"></i>
                                        <span class="d-block small fw-semibold">{{ $alert->data['title'] ?? 'Notification' }}</span>
                                        @if (! empty($alert->data['message']))
                                            <span class="d-block text-muted small">{{ $alert->data['message'] }}</span>
                                        @endif
                                        <span class="d-block text-muted small">{{ $alert->created_at?->diffForHumans() }}</span>
                                    </a>
                                </li>
                            @empty
                                <li><span class="dropdown-item-text small text-muted">No unread notifications.</span></li>
                            @endforelse
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                            <span class="badge bg-light text-dark ms-1">{{ ucfirst(auth()->user()->role) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->email }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">
                                        <i class="bi bi-box-arrow-right"></i> Log out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container-fluid py-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-octagon"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> Please check the highlighted fields.
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="app-footer text-center py-3">
        <span>ByteStock Inventory Management System &middot; Computer Parts Shop</span>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
