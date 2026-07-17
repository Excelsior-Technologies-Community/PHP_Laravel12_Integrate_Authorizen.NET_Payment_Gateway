<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f8fafc;
        }

        .card{
            border:none;
            border-radius:15px;
            box-shadow:0 10px 30px rgba(0,0,0,.08);
        }

        .table th{
            background:#0d6efd;
            color:#fff;
            vertical-align:middle;
        }

        .badge{
            font-size:.85rem;
        }

        code{
            color:#0d6efd;
            font-size:14px;
        }

        .filter-box{
            background:#f8fafc;
            padding:20px;
            border-radius:12px;
            margin-bottom:20px;
        }
    </style>

</head>

<body>


<div class="container py-5">


    <div class="card">


        <div class="card-header bg-white d-flex justify-content-between align-items-center">

            <h3 class="mb-0">
                💳 Payment History
            </h3>


            <a href="{{ route('payment.form') }}" class="btn btn-primary">
                New Payment
            </a>


        </div>



        <div class="card-body">


            <div class="alert alert-success">

                <strong>Database Storage:</strong>

                All Authorize.Net transactions are stored securely in database.

            </div>



            <div class="filter-box">


                <form method="GET" action="{{ route('payment.history') }}" class="row g-3">


                    <div class="col-md-5">

                        <input 
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search Transaction ID or Card Last 4"
                            value="{{ request('search') }}"
                        >

                    </div>



                    <div class="col-md-3">

                        <select name="status" class="form-select">


                            <option value="">
                                All Status
                            </option>


                            <option value="success"
                                {{ request('status') == 'success' ? 'selected' : '' }}>
                                Success
                            </option>


                            <option value="failed"
                                {{ request('status') == 'failed' ? 'selected' : '' }}>
                                Failed
                            </option>


                        </select>


                    </div>



                    <div class="col-md-4">


                        <button class="btn btn-primary">

                            🔍 Search

                        </button>


                        <a href="{{ route('payment.history') }}" 
                           class="btn btn-secondary">

                            Reset

                        </a>


                    </div>


                </form>


            </div>





            <div class="table-responsive">


                <table class="table table-bordered table-hover align-middle">


                    <thead>


                    <tr>

                        <th>Transaction ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Card</th>
                        <th>Auth Code / Error</th>

                    </tr>


                    </thead>



                    <tbody>



                    @forelse($transactions as $transaction)


                        <tr>


                            <td>

                                <code>
                                    {{ $transaction->transaction_id }}
                                </code>

                            </td>


                            <td>

                                {{ $transaction->customer_name }}

                            </td>



                            <td>

                                {{ $transaction->payment_date?->format('Y-m-d H:i:s') }}

                            </td>



                            <td>

                                ${{ number_format($transaction->amount,2) }}

                            </td>



                            <td>


                                @if($transaction->payment_status == 'success')


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


                                **** {{ $transaction->card_last4 ?? 'N/A' }}


                            </td>



                            <td>


                                @if($transaction->payment_status == 'success')


                                    {{ $transaction->authorization_code ?? '-' }}


                                @else


                                    <span class="text-danger">

                                        {{ $transaction->error_message ?? '-' }}

                                    </span>


                                @endif


                            </td>



                        </tr>



                    @empty



                        <tr>


                            <td colspan="7" class="text-center py-5">


                                <h5>
                                    No payment history found.
                                </h5>


                                <p class="text-muted mb-3">

                                    Make your first payment to see transactions.

                                </p>


                                <a href="{{ route('payment.form') }}" 
                                   class="btn btn-primary">

                                    Make Payment

                                </a>


                            </td>


                        </tr>



                    @endforelse



                    </tbody>


                </table>


            </div>


        </div>



        <div class="card-footer bg-white">


            <a href="{{ route('payment.form') }}" 
               class="btn btn-outline-secondary">

                ← Back to Payment Form

            </a>


        </div>



    </div>


</div>  


</body>
</html>