@extends('layouts.app')

@section('title', 'Payment Receipt - Authorize.Net')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white text-center py-3">
                <i class="fa-solid fa-circle-check fs-1 mb-2 d-block"></i>
                <h3 class="fw-bold mb-0">Payment Successful</h3>
                <small>Your transaction was authorized and processed by Authorize.Net.</small>
            </div>

            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h5 class="fw-bold text-dark mb-1">Official Electronic Receipt</h5>
                    <p class="text-muted small">An automated confirmation email has been logged for this purchase.</p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <tbody>
                            <tr>
                                <th class="bg-light text-muted" width="35%">Merchant</th>
                                <td class="fw-semibold">{{ $receipt['merchant_name'] ?? 'Authorize.Net Merchant Store' }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Customer Name</th>
                                <td class="fw-bold text-dark">{{ $receipt['customer_name'] }}</td>
                            </tr>
                            @if(isset($receipt['customer_email']))
                            <tr>
                                <th class="bg-light text-muted">Customer Email</th>
                                <td>{{ $receipt['customer_email'] }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="bg-light text-muted">Invoice Number</th>
                                <td class="font-monospace fw-bold text-primary">{{ $receipt['invoice_number'] }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Transaction ID</th>
                                <td class="font-monospace text-dark">{{ $receipt['transaction_id'] ?? ($receipt['id'] ?? '-') }}</td>
                            </tr>
                            @if(!empty($receipt['auth_code']))
                            <tr>
                                <th class="bg-light text-muted">Authorization Code</th>
                                <td class="font-monospace text-success fw-bold">{{ $receipt['auth_code'] }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="bg-light text-muted">Amount Paid</th>
                                <td class="fs-5 fw-bold text-success">${{ number_format($receipt['amount'], 2) }} USD</td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Payment Method</th>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $receipt['payment_method'] ?? 'Credit Card' }} (*{{ $receipt['card_last4'] }})
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Status</th>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="fa-solid fa-circle-check me-1"></i> Approved
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light text-muted">Transaction Date</th>
                                <td>{{ is_object($receipt['date']) ? $receipt['date']->format('M d, Y h:i A') : $receipt['date'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                    <button onclick="window.print()" class="btn btn-outline-primary fw-semibold">
                        <i class="fa-solid fa-print me-1"></i> Quick Print
                    </button>
                    @if(isset($receipt['id']) && is_numeric($receipt['id']))
                    <a href="{{ route('payment.invoice', $receipt['id']) }}" class="btn btn-primary fw-semibold">
                        <i class="fa-solid fa-file-invoice me-1"></i> Full PDF Invoice
                    </a>
                    @endif
                    <a href="{{ route('payment.history') }}" class="btn btn-dark fw-semibold">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> View Transactions
                    </a>
                    <a href="{{ route('payment.form') }}" class="btn btn-outline-secondary fw-semibold">
                        <i class="fa-solid fa-plus me-1"></i> Another Payment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection