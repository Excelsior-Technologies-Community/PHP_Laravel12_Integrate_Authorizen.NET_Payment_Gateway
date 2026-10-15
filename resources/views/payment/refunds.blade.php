@extends('layouts.app')

@section('title', 'Refunds & Void Audit - Authorize.Net')

@section('content')
<!-- Header Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="card p-3 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Total Amount Refunded</span>
            <h3 class="fw-bold text-danger mt-2 mb-0">${{ number_format($totalRefundedAmount, 2) }}</h3>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card p-3 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Total Voided Operations</span>
            <h3 class="fw-bold text-secondary mt-2 mb-0">{{ $totalVoidedCount }}</h3>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="card p-3 shadow-sm border-0">
            <span class="text-muted small text-uppercase fw-semibold">Total Audit Entries</span>
            <h3 class="fw-bold text-primary mt-2 mb-0">{{ $refunds->total() }}</h3>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-arrow-rotate-left text-danger me-2"></i> Refunds &amp; Void Audit Trail
            </h4>
            <small class="text-muted">Complete electronic audit record of all reversed and cancelled Authorize.Net transactions.</small>
        </div>
        <a href="{{ route('payment.history') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> Transaction History
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Original Trans ID</th>
                        <th>Gateway Refund ID</th>
                        <th>Customer / Invoice</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="pe-3">Processed At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($refunds as $ref)
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#{{ $ref->id }}</td>
                        <td>
                            <span class="font-monospace fw-semibold">{{ $ref->transaction_id }}</span>
                        </td>
                        <td>
                            <span class="font-monospace text-muted">{{ $ref->refund_transaction_id ?? '-' }}</span>
                        </td>
                        <td>
                            @if($ref->payment)
                                <div class="fw-semibold text-dark">{{ $ref->payment->customer_name }}</div>
                                <a href="{{ route('payment.invoice', $ref->payment->id) }}" class="small text-primary text-decoration-none">
                                    <i class="fa-solid fa-file-invoice me-1"></i>{{ $ref->payment->invoice_number }}
                                </a>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($ref->refund_type === 'void')
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="fa-solid fa-ban me-1"></i> VOID
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="fa-solid fa-rotate-left me-1"></i> REFUND
                                </span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-danger">-${{ number_format($ref->amount, 2) }}</strong>
                        </td>
                        <td>
                            <span class="small text-muted">{{ $ref->reason ?? 'None specified' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="fa-solid fa-check me-1"></i> Success
                            </span>
                        </td>
                        <td class="pe-3">
                            <small class="text-muted">{{ $ref->refunded_at ? $ref->refunded_at->format('M d, Y h:i A') : $ref->created_at->format('M d, Y h:i A') }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fs-2 mb-2 d-block text-secondary"></i>
                            No refunds or void transactions recorded yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center p-3">
            {{ $refunds->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
