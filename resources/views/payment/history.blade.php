<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Payment History</h4>
                        <a href="{{ route('payment.form') }}" class="btn btn-primary">
                            New Payment
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Note:</strong> In a real application, transactions would be stored in your database.
                            This is a demonstration of the Authorize.Net integration.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Card Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Example rows - In production, these would come from database -->
                                    <tr>
                                        <td><code>2230582181</code></td>
                                        <td>{{ now()->subDays(2)->format('Y-m-d H:i') }}</td>
                                        <td>$25.00</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td>Visa</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info">View Details</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><code>2230582182</code></td>
                                        <td>{{ now()->subDays(1)->format('Y-m-d H:i') }}</td>
                                        <td>$50.00</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td>MasterCard</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info">View Details</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="{{ route('payment.form') }}" class="btn btn-outline-secondary">
                                ← Back to Payment Form
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>