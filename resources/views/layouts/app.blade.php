<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Authorize.Net Payment Gateway')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background: #f1f5f9;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: #0f172a !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .nav-link {
            font-weight: 500;
            padding: 0.5rem 0.9rem !important;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: #38bdf8 !important;
            background: rgba(255, 255, 255, 0.08);
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            background: #ffffff;
        }

        .card-header {
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
        }

        .badge-method-card {
            background: #0284c7;
            color: #ffffff;
        }

        .badge-method-echeck {
            background: #059669;
            color: #ffffff;
        }

        .badge-method-saved {
            background: #7c3aed;
            color: #ffffff;
        }

        .table > :not(caption) > * > * {
            padding: 0.85rem 0.75rem;
            vertical-align: middle;
        }

        footer {
            margin-top: auto;
            background: #0f172a;
            color: #94a3b8;
            font-size: 0.875rem;
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('payment.form') }}">
                <i class="fa-solid fa-shield-halved text-primary fs-4"></i>
                <span>Authorize.Net Gateway</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('payment.form') ? 'active' : '' }}" href="{{ route('payment.form') }}">
                            <i class="fa-solid fa-credit-card me-1"></i> Make Payment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('payment.dashboard') ? 'active' : '' }}" href="{{ route('payment.dashboard') }}">
                            <i class="fa-solid fa-chart-pie me-1"></i> Dashboard &amp; Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('payment.history') ? 'active' : '' }}" href="{{ route('payment.history') }}">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('payment.refunds') ? 'active' : '' }}" href="{{ route('payment.refunds') }}">
                            <i class="fa-solid fa-arrow-rotate-left me-1"></i> Refunds Audit
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('payment.saved-cards*') ? 'active' : '' }}" href="{{ route('payment.saved-cards') }}">
                            <i class="fa-solid fa-vault me-1"></i> Saved Cards (CIM)
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark border border-secondary text-light px-3 py-2 small">
                        <i class="fa-solid fa-circle text-success me-1" style="font-size: 8px;"></i>
                        {{ strtoupper(config('authorize.mode', 'sandbox')) }}
                    </span>
                    <a href="{{ route('payment.form') }}" class="btn btn-primary btn-sm px-3 fw-semibold">
                        <i class="fa-solid fa-plus me-1"></i> New Payment
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Notifications & Content -->
    <div class="container my-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-check fs-5 me-2"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i>
                <div class="flex-grow-1">{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="py-3 text-center">
        <div class="container">
            <span>&copy; {{ date('Y') }} Authorize.Net Payment Gateway Integration &bull; PCI-DSS Ready Tokenized Transactions</span>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
