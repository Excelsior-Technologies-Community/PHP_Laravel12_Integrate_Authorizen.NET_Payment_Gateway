<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Payment History</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
        }

        .stat-card {
            color: #fff;
            border-radius: 15px;
            padding: 20px;
        }

        .table th {
            background: #0d6efd;
            color: #fff;
            vertical-align: middle;
        }

        .badge {
            font-size: .85rem;
        }

        .filter-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .table td {
            vertical-align: middle;
        }
    </style>

</head>

<body>

    <div class="container py-5">

        @php

        $totalPayments = \App\Models\Payment::count();

        $successPayments = \App\Models\Payment::where('payment_status','success')->count();

        $failedPayments = \App\Models\Payment::where('payment_status','failed')->count();

        $totalRevenue = \App\Models\Payment::where('payment_status','success')->sum('amount');

        @endphp


        {{-- Dashboard Cards --}}

        <div class="row mb-4">

            <div class="col-md-3">

                <div class="stat-card bg-primary">

                    <h6>Total Payments</h6>

                    <h2>{{ $totalPayments }}</h2>

                </div>

            </div>

            <div class="col-md-3">

                <div class="stat-card bg-success">

                    <h6>Success</h6>

                    <h2>{{ $successPayments }}</h2>

                </div>

            </div>

            <div class="col-md-3">

                <div class="stat-card bg-danger">

                    <h6>Failed</h6>

                    <h2>{{ $failedPayments }}</h2>

                </div>

            </div>

            <div class="col-md-3">

                <div class="stat-card bg-dark">

                    <h6>Total Revenue</h6>

                    <h2>${{ number_format($totalRevenue,2) }}</h2>

                </div>

            </div>

        </div>



        <div class="card">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <h3 class="mb-0">

                    Payment History

                </h3>

                <div>

                    <a href="{{ route('payment.export') }}"
                        class="btn btn-success">

                        Export CSV

                    </a>

                    <a href="{{ route('payment.form') }}"
                        class="btn btn-primary">

                        New Payment

                    </a>

                </div>

            </div>



            <div class="card-body">

                @if(session('success'))

                <div class="alert alert-success">

                    {{ session('success') }}

                </div>

                @endif

                @if(session('error'))

                <div class="alert alert-danger">

                    {{ session('error') }}

                </div>

                @endif



                <div class="filter-box">

                    <form method="GET"
                        action="{{ route('payment.history') }}">

                        <div class="row g-3">

                            <div class="col-md-4">

                                <input
                                    type="text"
                                    name="search"
                                    class="form-control"
                                    placeholder="Customer / Transaction / Invoice / Amount"
                                    value="{{ request('search') }}">

                            </div>

                            <div class="col-md-2">

                                <select
                                    name="status"
                                    class="form-select">

                                    <option value="">All Status</option>

                                    <option value="success"
                                        {{ request('status')=='success' ? 'selected':'' }}>
                                        Success
                                    </option>

                                    <option value="failed"
                                        {{ request('status')=='failed' ? 'selected':'' }}>
                                        Failed
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-2">

                                <input
                                    type="date"
                                    name="from_date"
                                    class="form-control"
                                    value="{{ request('from_date') }}">

                            </div>

                            <div class="col-md-2">

                                <input
                                    type="date"
                                    name="to_date"
                                    class="form-control"
                                    value="{{ request('to_date') }}">

                            </div>

                            <div class="col-md-2 d-grid">

                                <button
                                    class="btn btn-primary">

                                    Search

                                </button>

                            </div>

                            <div class="col-md-12">

                                <a href="{{ route('payment.history') }}"
                                    class="btn btn-secondary">

                                    Reset

                                </a>

                            </div>

                        </div>

                    </form>

                </div>



                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Transaction ID</th>

                                <th>Invoice</th>

                                <th>Customer</th>

                                <th>Amount</th>

                                <th>Status</th>

                                <th>Card</th>

                                <th>Date</th>

                                <th>Auth / Error</th>

                                <th width="220">

                                    Action

                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($transactions as $transaction)

                            <tr>

                                <td>
                                    {{ $loop->iteration + (($transactions->currentPage() - 1) * $transactions->perPage()) }}
                                </td>

                                <td>

                                    <span class="fw-bold text-primary">

                                        {{ $transaction->transaction_id }}

                                    </span>

                                </td>

                                <td>

                                    {{ $transaction->invoice_number }}

                                </td>

                                <td>

                                    {{ $transaction->customer_name }}

                                </td>

                                <td>

                                    <strong>

                                        ${{ number_format($transaction->amount,2) }}

                                    </strong>

                                </td>

                                <td>

                                    @if($transaction->payment_status=='success')

                                    <span class="badge bg-success">

                                        Success

                                    </span>

                                    @else

                                    <span class="badge bg-danger">

                                        Failed

                                    </span>

                                    @endif

                                </td>

                                <td>

                                    **** {{ $transaction->card_last4 }}

                                </td>

                                <td>

                                    {{ optional($transaction->payment_date)->format('d M Y H:i') }}

                                </td>

                                <td>

                                    @if($transaction->payment_status=='success')

                                    <span class="text-success">

                                        {{ $transaction->authorization_code ?? '-' }}

                                    </span>

                                    @else

                                    <span class="text-danger">

                                        {{ $transaction->error_message ?? '-' }}

                                    </span>

                                    @endif

                                </td>

                                <td>

                                    <div class="d-flex flex-wrap gap-2">

                                        @if($transaction->payment_status=='success')

                                        <form method="POST"
                                            action="{{ route('payment.failed',$transaction->id) }}">

                                            @csrf

                                            @method('PUT')

                                            <button
                                                class="btn btn-warning btn-sm">

                                                Mark Failed

                                            </button>

                                        </form>

                                        @else

                                        <form method="POST"
                                            action="{{ route('payment.success.status',$transaction->id) }}">

                                            @csrf

                                            @method('PUT')

                                            <button
                                                class="btn btn-success btn-sm">

                                                Mark Success

                                            </button>

                                        </form>

                                        @endif

                                        <form
                                            action="{{ route('payment.destroy',$transaction->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this payment?')">

                                            @csrf

                                            @method('DELETE')

                                            <button
                                                class="btn btn-danger btn-sm">

                                                Delete

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                            @empty

                            <tr>

                                <td colspan="10" class="text-center py-5">

                                    <h4 class="text-muted">

                                        No Payments Found

                                    </h4>

                                    <p class="text-muted">

                                        No payment records match your search criteria.

                                    </p>

                                    <a href="{{ route('payment.form') }}"
                                        class="btn btn-primary">

                                        Make New Payment

                                    </a>

                                </td>

                            </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                @if($transactions->count())

                <div class="row mt-4">

                    <div class="col-md-6">

                        <p class="text-muted mb-0">

                            Showing

                            <strong>{{ $transactions->firstItem() }}</strong>

                            to

                            <strong>{{ $transactions->lastItem() }}</strong>

                            of

                            <strong>{{ $transactions->total() }}</strong>

                            payments

                        </p>

                    </div>

                    <div class="col-md-6 d-flex justify-content-end">

                        @if ($transactions->lastPage() > 1)

                        <nav>

                            <ul class="pagination justify-content-end">

                                @for ($page = 1; $page <= $transactions->lastPage(); $page++)

                                    <li class="page-item {{ $transactions->currentPage() == $page ? 'active' : '' }}">

                                        <a class="page-link"
                                            href="{{ $transactions->url($page) }}">

                                            {{ $page }}

                                        </a>

                                    </li>

                                    @endfor

                            </ul>

                        </nav>

                        @endif

                    </div>

                </div>

                @endif

            </div>

            <div class="card-footer bg-white d-flex justify-content-between">

                <a href="{{ route('payment.form') }}"
                    class="btn btn-secondary">

                    ← Back to Payment Form

                </a>

                <a href="{{ route('payment.dashboard') }}"
                    class="btn btn-dark">

                    Payment Dashboard

                </a>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>