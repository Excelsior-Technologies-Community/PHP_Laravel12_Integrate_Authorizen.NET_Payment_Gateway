<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Payment Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
        }

        .dashboard-card {
            color: #fff;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
            transition: .3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .dashboard-card h2 {
            font-size: 34px;
            margin-top: 10px;
            font-weight: bold;
        }

        .dashboard-card i {
            font-size: 40px;
        }

        .table th {
            background: #0d6efd;
            color: #fff;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }
    </style>

</head>

<body>

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="fw-bold">

                    Payment Dashboard

                </h2>

                <p class="text-muted">

                    Authorize.Net Payment Analytics

                </p>

            </div>

            <div>

                <a href="{{ route('payment.form') }}"
                    class="btn btn-primary">

                    New Payment

                </a>

                <a href="{{ route('payment.history') }}"
                    class="btn btn-dark">

                    Payment History

                </a>

            </div>

        </div>



        <div class="row g-4">

            <div class="col-lg-4">

                <div class="dashboard-card bg-primary">

                    <i class="fas fa-credit-card"></i>

                    <h2>

                        {{ $totalPayments }}

                    </h2>

                    <h5>

                        Total Payments

                    </h5>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="dashboard-card bg-success">

                    <i class="fas fa-circle-check"></i>

                    <h2>

                        {{ $successPayments }}

                    </h2>

                    <h5>

                        Successful Payments

                    </h5>

                </div>

            </div>

            <div class="col-lg-4">

                <div class="dashboard-card bg-danger">

                    <i class="fas fa-circle-xmark"></i>

                    <h2>

                        {{ $failedPayments }}

                    </h2>

                    <h5>

                        Failed Payments

                    </h5>

                </div>

            </div>

            <div class="col-lg-6">

                <div class="dashboard-card bg-warning text-dark">

                    <i class="fas fa-calendar-day"></i>

                    <h2>

                        {{ $todayPayments }}

                    </h2>

                    <h5>

                        Today's Payments

                    </h5>

                </div>

            </div>

            <div class="col-lg-6">

                <div class="dashboard-card bg-dark">

                    <i class="fas fa-dollar-sign"></i>

                    <h2>

                        ${{ number_format($totalRevenue,2) }}

                    </h2>

                    <h5>

                        Total Revenue

                    </h5>

                </div>

            </div>

        </div>



        <div class="row mt-5">

            <div class="col-lg-12">

                <div class="card">

                    <div class="card-header bg-white">

                        <h4 class="mb-0">

                            Recent Transactions

                        </h4>

                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover">

                                <thead>

                                    <tr>

                                        <th>Transaction ID</th>

                                        <th>Customer</th>

                                        <th>Amount</th>

                                        <th>Status</th>

                                        <th>Date</th>

                                    </tr>

                                </thead>

                                <tbody>@forelse($recentPayments as $payment)

                                    <tr>

                                        <td>

                                            <span class="fw-bold text-primary">

                                                {{ $payment->transaction_id }}

                                            </span>

                                        </td>

                                        <td>

                                            {{ $payment->customer_name }}

                                        </td>

                                        <td>

                                            <strong>

                                                ${{ number_format($payment->amount,2) }}

                                            </strong>

                                        </td>

                                        <td>

                                            @if($payment->payment_status=='success')

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

                                            {{ optional($payment->payment_date)->format('d M Y H:i') }}

                                        </td>

                                    </tr>

                                    @empty

                                    <tr>

                                        <td colspan="5" class="text-center py-5">

                                            <h5 class="text-muted">

                                                No Recent Transactions

                                            </h5>

                                            <p class="text-muted">

                                                Payments will appear here after processing transactions.

                                            </p>

                                        </td>

                                    </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>


            <div class="row mt-4">

                <div class="col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <h5 class="mb-3">

                                Revenue Summary

                            </h5>

                            <table class="table">

                                <tr>

                                    <th>Total Revenue</th>

                                    <td class="text-end">

                                        <strong>

                                            ${{ number_format($totalRevenue,2) }}

                                        </strong>

                                    </td>

                                </tr>

                                <tr>

                                    <th>Today's Revenue</th>

                                    <td class="text-end">

                                        <strong>

                                            ${{ number_format($todayRevenue,2) }}

                                        </strong>

                                    </td>

                                </tr>

                                <tr>

                                    <th>Total Successful</th>

                                    <td class="text-end">

                                        {{ $successPayments }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>Total Failed</th>

                                    <td class="text-end">

                                        {{ $failedPayments }}

                                    </td>

                                </tr>

                            </table>

                        </div>

                    </div>

                </div>



                <div class="col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <h5 class="mb-4">

                                Quick Actions

                            </h5>

                            <div class="d-grid gap-3">

                                <a href="{{ route('payment.form') }}"
                                    class="btn btn-primary">

                                    New Payment

                                </a>

                                <a href="{{ route('payment.history') }}"
                                    class="btn btn-success">

                                    Payment History

                                </a>

                                <a href="{{ route('payment.export') }}"
                                    class="btn btn-warning">

                                    Export CSV

                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>