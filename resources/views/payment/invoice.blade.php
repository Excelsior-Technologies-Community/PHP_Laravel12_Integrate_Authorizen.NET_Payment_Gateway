@extends('layouts.app')

@section('title', 'Invoice #' . $payment->invoice_number . ' - Authorize.Net')

@section('styles')
<style>
    @media print {
        body { background: #fff !important; }
        .navbar, footer, .no-print { display: none !important; }
        .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
    .invoice-box {
        background: #ffffff;
        border-radius: 12px;
        padding: 40px;
    }
    .invoice-title {
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">

        <!-- Top Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="{{ route('payment.history') }}" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to History
            </a>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary fw-semibold" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Print / Save PDF
                </button>
            </div>
        </div>

        <!-- Printable Invoice Box -->
        <div class="card shadow-sm border invoice-box">
            <!-- Header -->
            <div class="row align-items-center border-bottom pb-4 mb-4">
                <div class="col-sm-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-shield-halved text-primary fs-2"></i>
                        <span class="fs-4 fw-bold text-dark">Authorize.Net Merchant</span>
                    </div>
                    <p class="text-muted small mb-0">
                        100 Financial Plaza, Suite 400<br>
                        San Francisco, CA 94105, USA<br>
                        support@example-merchant.com
                    </p>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                    <h2 class="invoice-title text-primary mb-1">INVOICE</h2>
                    <div class="fw-bold text-dark fs-5">#{{ $payment->invoice_number }}</div>
                    <div class="text-muted small">Date: {{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : now()->format('M d, Y') }}</div>
                    <div class="mt-2">
                        @if($payment->refund_status === 'full')
                            <span class="badge bg-danger fs-6 px-3 py-1">REFUNDED</span>
                        @elseif($payment->refund_status === 'partial')
                            <span class="badge bg-warning text-dark fs-6 px-3 py-1">PARTIALLY REFUNDED</span>
                        @elseif($payment->refund_status === 'voided')
                            <span class="badge bg-secondary fs-6 px-3 py-1">VOIDED</span>
                        @elseif($payment->payment_status === 'success')
                            <span class="badge bg-success fs-6 px-3 py-1">PAID</span>
                        @else
                            <span class="badge bg-danger fs-6 px-3 py-1">FAILED</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Customer & Transaction Info -->
            <div class="row mb-4">
                <div class="col-sm-6 border-end-sm">
                    <h6 class="text-muted text-uppercase fw-bold small mb-2">Billed To:</h6>
                    <h5 class="fw-bold text-dark mb-1">{{ $payment->customer_name }}</h5>
                    @if($payment->customer_email)
                        <div class="text-muted small mb-1"><i class="fa-regular fa-envelope me-1"></i> {{ $payment->customer_email }}</div>
                    @endif
                    <div class="text-muted small">Electronic Transaction Receipt</div>
                </div>
                <div class="col-sm-6 text-sm-end mt-3 mt-sm-0 ps-sm-4">
                    <h6 class="text-muted text-uppercase fw-bold small mb-2">Payment Details:</h6>
                    <div class="small mb-1">
                        <span class="text-muted">Transaction ID:</span>
                        <strong class="font-monospace">{{ $payment->transaction_id ?? 'N/A' }}</strong>
                    </div>
                    @if($payment->authorization_code)
                    <div class="small mb-1">
                        <span class="text-muted">Auth Code:</span>
                        <strong class="font-monospace">{{ $payment->authorization_code }}</strong>
                    </div>
                    @endif
                    <div class="small mb-1">
                        <span class="text-muted">Method:</span>
                        @if($payment->payment_method === 'echeck')
                            <span class="badge bg-success-subtle text-success border border-success-subtle">eCheck (ACH) - *{{ $payment->account_last4 }}</span>
                        @elseif($payment->payment_method === 'saved_card')
                            <span class="badge bg-purple-subtle text-primary border">1-Click Token (*{{ $payment->card_last4 }})</span>
                        @else
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $payment->card_brand ?? 'Card' }} (*{{ $payment->card_last4 }})</span>
                        @endif
                    </div>
                    <div class="small">
                        <span class="text-muted">Gateway:</span>
                        <span>Authorize.Net Sandbox</span>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item Description</th>
                            <th class="text-center" width="80">Qty</th>
                            <th class="text-end" width="120">Unit Price</th>
                            <th class="text-end" width="140">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">Payment Service Order</div>
                                <small class="text-muted">Authorize.Net electronic processing for Invoice #{{ $payment->invoice_number }}</small>
                            </td>
                            <td class="text-center">1</td>
                            <td class="text-end">${{ number_format($payment->amount, 2) }}</td>
                            <td class="text-end fw-semibold">${{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                            <td class="text-end">${{ number_format($payment->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end text-muted">Processing / Tax:</td>
                            <td class="text-end text-muted">$0.00</td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fs-5 fw-bold text-dark">Total Amount:</td>
                            <td class="text-end fs-5 fw-bold text-primary">${{ number_format($payment->amount, 2) }} USD</td>
                        </tr>
                        @if((float)$payment->refunded_amount > 0)
                        <tr class="table-warning">
                            <td colspan="3" class="text-end fw-bold text-danger">Total Refunded / Reversed:</td>
                            <td class="text-end fw-bold text-danger">-${{ number_format($payment->refunded_amount, 2) }}</td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold text-dark">Net Amount Paid:</td>
                            <td class="text-end fw-bold text-success">${{ number_format($payment->remaining_refundable_amount, 2) }}</td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>

            <!-- Refund Audit Trail (if applicable) -->
            @if($payment->refunds->isNotEmpty())
            <div class="mb-4">
                <h6 class="fw-bold text-danger small text-uppercase mb-2">
                    <i class="fa-solid fa-rotate-left me-1"></i> Associated Refund &amp; Void Audit Trail
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light small">
                            <tr>
                                <th>Refund ID</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            @foreach($payment->refunds as $ref)
                            <tr>
                                <td class="font-monospace">{{ $ref->refund_transaction_id }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($ref->refund_type) }}</span></td>
                                <td class="text-danger fw-semibold">-${{ number_format($ref->amount, 2) }}</td>
                                <td>{{ $ref->reason }}</td>
                                <td>{{ $ref->refunded_at ? $ref->refunded_at->format('M d, Y h:i A') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Bottom QR Code / Barcode representation & Note -->
            <div class="border-top pt-4 d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <div class="font-monospace small text-muted">
                        ||| | |||| | ||||| |||| || |||| ||||| ||| |||||||
                    </div>
                    <small class="text-muted d-block mt-1">
                        Verification Hash: {{ hash('sha256', $payment->transaction_id . $payment->created_at) }}
                    </small>
                </div>
                <div class="text-sm-end text-muted small mt-2 mt-sm-0">
                    <div>Thank you for your business!</div>
                    <div>Questions? Contact billing@example-merchant.com</div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
