<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">

        <div class="card shadow-lg">

            <div class="card-header bg-success text-white text-center">
                <h3 class="mb-0">
                    Payment Successful
                </h3>
            </div>


            <div class="card-body p-5">

                <div class="text-center mb-4">
                    <h4>
                        Payment Receipt
                    </h4>

                    <p class="text-muted">
                        Thank you for your payment
                    </p>
                </div>


                <table class="table table-bordered">

                    <tr>
                        <th>Merchant Name</th>
                        <td>{{ $receipt['merchant_name'] }}</td>
                    </tr>

                    <tr>
                        <th>Customer Name</th>
                        <td>{{ $receipt['customer_name'] }}</td>
                    </tr>

                    <tr>
                        <th>Invoice Number</th>
                        <td>{{ $receipt['invoice_number'] }}</td>
                    </tr>

                    <tr>
                        <th>Transaction ID</th>
                        <td>{{ $receipt['id'] }}</td>
                    </tr>

                    <tr>
                        <th>Authorization Code</th>
                        <td>{{ $receipt['auth_code'] }}</td>
                    </tr>

                    <tr>
                        <th>Amount</th>
                        <td>${{ number_format($receipt['amount'],2) }}</td>
                    </tr>

                    <tr>
                        <th>Card</th>
                        <td>
                            **** **** **** {{ $receipt['card_last4'] }}
                        </td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-success">
                                {{ ucfirst($receipt['status']) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <th>Date & Time</th>
                        <td>{{ $receipt['date'] }}</td>
                    </tr>

                </table>

                <div class="text-center mt-4">

                    <button onclick="window.print()"
                        class="btn btn-primary">
                        Print Receipt
                    </button>


                    <a href="{{ route('payment.history') }}"
                        class="btn btn-dark">
                        View History
                    </a>

                </div>


            </div>

        </div>

    </div>

</body>

</html>