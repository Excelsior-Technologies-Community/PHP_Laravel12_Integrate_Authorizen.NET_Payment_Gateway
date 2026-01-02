<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway - Authorize.Net</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-header { background-color: #6f42c1; color: white; }
        .test-cards { background-color: #f8f9fa; border-left: 4px solid #6f42c1; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Payment Gateway (Authorize.Net Sandbox)</h4>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="mb-4 test-cards p-3">
                            <h6>Test Credit Cards:</h6>
                            <ul class="mb-0">
                                <li><strong>Visa:</strong> 4007000000027</li>
                                <li><strong>MasterCard:</strong> 5424000000000015</li>
                                <li><strong>American Express:</strong> 370000000000002</li>
                                <li><strong>Discover:</strong> 6011000000000012</li>
                                <li><strong>Visa (Decline):</strong> 4222222222222</li>
                            </ul>
                            <small class="text-muted">Expiration: Any future date (YYYY-MM), CVV: Any 3-4 digits</small>
                        </div>

                        <form method="POST" action="{{ route('payment.process') }}">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount ($)</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       value="{{ old('amount', '10.00') }}" step="0.01" min="1" required>
                            </div>

                            <h5 class="mb-3">Credit Card Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="card_number" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="card_number" 
                                           name="card_number" value="{{ old('card_number', '4007000000027') }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" 
                                           name="cvv" value="{{ old('cvv', '123') }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="exp_date" class="form-label">Expiration Date (YYYY-MM)</label>
                                <input type="text" class="form-control" id="exp_date" 
                                       name="exp_date" value="{{ old('exp_date', date('Y-m', strtotime('+1 year'))) }}" 
                                       placeholder="YYYY-MM" required>
                            </div>

                            <h5 class="mb-3">Billing Information</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" 
                                           name="first_name" value="{{ old('first_name', 'John') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" 
                                           name="last_name" value="{{ old('last_name', 'Doe') }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" 
                                       name="address" value="{{ old('address', '123 Main St') }}" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" 
                                           name="city" value="{{ old('city', 'New York') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" 
                                           name="state" value="{{ old('state', 'NY') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="zip" class="form-label">ZIP Code</label>
                                    <input type="text" class="form-control" id="zip" 
                                           name="zip" value="{{ old('zip', '10001') }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" 
                                       name="country" value="{{ old('country', 'USA') }}" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Process Payment
                                </button>
                                <a href="{{ route('payment.history') }}" class="btn btn-outline-secondary">
                                    View Payment History
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="mt-3 text-center text-muted">
                    <small>Using Authorize.Net Sandbox Environment</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>