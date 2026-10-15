@extends('layouts.app')

@section('title', 'Transaction History - Authorize.Net')

@section('content')
<!-- Header & Quick Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h3 class="fw-bold mb-0 text-dark">
            <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Transaction History
        </h3>
        <p class="text-muted small mb-0">Browse, filter, print invoices, and execute refunds or voids on Authorize.Net transactions.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('payment.export') }}" class="btn btn-outline-success btn-sm fw-semibold">
            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </a>
        <a href="{{ route('payment.refunds') }}" class="btn btn-outline-danger btn-sm fw-semibold">
            <i class="fa-solid fa-arrow-rotate-left me-1"></i> Refunds Audit
        </a>
        <a href="{{ route('payment.form') }}" class="btn btn-primary btn-sm fw-semibold">
            <i class="fa-solid fa-plus me-1"></i> New Payment
        </a>
    </div>
</div>

<!-- Advanced Search & Filter Box -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3 bg-white">
        <form action="{{ route('payment.history') }}" method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Search Keywords</label>
                    <input type="text"
                           class="form-control form-control-sm"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Trans ID, Invoice, Customer, Card...">
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small text-muted mb-1">Payment Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small text-muted mb-1">Payment Method</label>
                    <select name="method" class="form-select form-select-sm">
                        <option value="">All Methods</option>
                        <option value="credit_card" {{ request('method') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                        <option value="echeck" {{ request('method') === 'echeck' ? 'selected' : '' }}>eCheck (ACH)</option>
                        <option value="saved_card" {{ request('method') === 'saved_card' ? 'selected' : '' }}>Saved Card (CIM)</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="form-label small text-muted mb-1">Refund Status</label>
                    <select name="refund_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="none" {{ request('refund_status') === 'none' ? 'selected' : '' }}>None</option>
                        <option value="partial" {{ request('refund_status') === 'partial' ? 'selected' : '' }}>Partially Refunded</option>
                        <option value="full" {{ request('refund_status') === 'full' ? 'selected' : '' }}>Fully Refunded</option>
                        <option value="voided" {{ request('refund_status') === 'voided' ? 'selected' : '' }}>Voided</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-9 col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-semibold">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('payment.history') }}" class="btn btn-outline-secondary btn-sm">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Transaction ID / Invoice</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Refund State</th>
                        <th>Date</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                    <tr>
                        <td class="ps-3 fw-bold text-muted">
                            {{ $loop->iteration + (($transactions->currentPage() - 1) * $transactions->perPage()) }}
                        </td>
                        <td>
                            <div class="fw-bold font-monospace text-dark">{{ $transaction->transaction_id }}</div>
                            <small class="text-muted d-block">
                                <i class="fa-solid fa-file-invoice text-secondary me-1"></i>{{ $transaction->invoice_number }}
                            </small>
                            @if($transaction->authorization_code)
                                <small class="text-muted">Auth: <span class="font-monospace">{{ $transaction->authorization_code }}</span></small>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $transaction->customer_name }}</div>
                            <small class="text-muted">{{ $transaction->customer_email ?? '-' }}</small>
                        </td>
                        <td>
                            @if($transaction->payment_method === 'echeck')
                                <span class="badge badge-method-echeck">
                                    <i class="fa-solid fa-building-columns me-1"></i> eCheck (*{{ $transaction->account_last4 }})
                                </span>
                            @elseif($transaction->payment_method === 'saved_card')
                                <span class="badge badge-method-saved">
                                    <i class="fa-solid fa-vault me-1"></i> 1-Click (*{{ $transaction->card_last4 }})
                                </span>
                            @else
                                <span class="badge badge-method-card">
                                    <i class="fa-regular fa-credit-card me-1"></i> {{ $transaction->card_brand ?? 'Card' }} (*{{ $transaction->card_last4 }})
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6">${{ number_format($transaction->amount, 2) }}</div>
                            @if((float)$transaction->refunded_amount > 0)
                                <small class="text-danger d-block">
                                    Ref: -${{ number_format($transaction->refunded_amount, 2) }}
                                </small>
                            @endif
                        </td>
                        <td>
                            @if($transaction->payment_status === 'success')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fa-solid fa-circle-check me-1"></i> Success
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{ $transaction->error_message }}">
                                    <i class="fa-solid fa-circle-xmark me-1"></i> Failed
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($transaction->refund_status === 'full')
                                <span class="badge bg-danger">Full Refund</span>
                            @elseif($transaction->refund_status === 'partial')
                                <span class="badge bg-warning text-dark">Partial Refund</span>
                            @elseif($transaction->refund_status === 'voided')
                                <span class="badge bg-secondary">Voided</span>
                            @else
                                <span class="badge bg-light text-muted border">None</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ optional($transaction->payment_date)->format('M d, Y h:i A') }}</small>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <!-- Printable PDF Invoice -->
                                <a href="{{ route('payment.invoice', $transaction->id) }}"
                                   class="btn btn-outline-primary"
                                   title="View &amp; Print PDF Invoice">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>

                                <!-- Refund Action Button -->
                                @if($transaction->isRefundable())
                                <button type="button"
                                        class="btn btn-outline-danger"
                                        onclick="openRefundModal('{{ $transaction->id }}', '{{ $transaction->transaction_id }}', '{{ $transaction->remaining_refundable_amount }}')"
                                        title="Execute Full or Partial Refund">
                                    <i class="fa-solid fa-arrow-rotate-left"></i>
                                </button>
                                @endif

                                <!-- Void Action Button -->
                                @if($transaction->isVoidable())
                                <button type="button"
                                        class="btn btn-outline-warning text-dark"
                                        onclick="openVoidModal('{{ $transaction->id }}', '{{ $transaction->transaction_id }}', '{{ $transaction->amount }}')"
                                        title="Void Unsettled Transaction">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                                @endif

                                <!-- Status Toggle Button -->
                                @if($transaction->payment_status === 'success')
                                <form method="POST" action="{{ route('payment.failed', $transaction->id) }}" style="display:inline;">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn btn-outline-secondary" onclick="return confirm('Mark this transaction as failed?')" title="Mark Failed">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                                @else
                                <form method="POST" action="{{ route('payment.success.status', $transaction->id) }}" style="display:inline;">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn btn-outline-success" onclick="return confirm('Mark this transaction as success?')" title="Mark Success">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                                @endif

                                <!-- Delete -->
                                <form method="POST" action="{{ route('payment.destroy', $transaction->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" onclick="return confirm('Permanently delete this transaction record?')" title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-receipt fs-2 mb-2 d-block text-secondary"></i>
                            No payment transactions found matching criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center p-3">
            {{ $transactions->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<!-- Modal 1: Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="refundModalLabel">
                    <i class="fa-solid fa-arrow-rotate-left me-2"></i> Authorize.Net Refund Transaction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="refundForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Funds will be credited back to the customer's payment method via Authorize.Net API.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Original Transaction ID</label>
                        <div class="fw-bold font-monospace text-dark fs-6" id="refundTransIdText">-</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Refund Amount ($ USD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number"
                                   step="0.01"
                                   min="0.01"
                                   class="form-control"
                                   name="refund_amount"
                                   id="refundAmountInput"
                                   required>
                        </div>
                        <small class="text-muted">Maximum refundable balance: $<strong id="maxRefundableText">0.00</strong></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason for Refund</label>
                        <input type="text"
                               class="form-control"
                               name="reason"
                               placeholder="e.g. Customer return, order cancellation"
                               value="Customer requested refund">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-semibold" id="btnExecuteRefund">
                        <i class="fa-solid fa-arrow-rotate-left me-1"></i> Process Refund
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Void Modal -->
<div class="modal fade" id="voidModal" tabindex="-1" aria-labelledby="voidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="voidModalLabel">
                    <i class="fa-solid fa-ban me-2"></i> Void Unsettled Transaction
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="voidForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Voiding cancels an unsettled transaction before batch close, preventing credit card processing fees.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Target Transaction ID</label>
                        <div class="fw-bold font-monospace text-dark fs-6" id="voidTransIdText">-</div>
                        <div class="text-muted small">Amount to Void: $<strong id="voidAmountText">0.00</strong></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason for Void</label>
                        <input type="text"
                               class="form-control"
                               name="reason"
                               placeholder="e.g. Accidental duplicate charge"
                               value="Transaction voided before settlement">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-semibold" id="btnExecuteVoid">
                        <i class="fa-solid fa-ban me-1"></i> Confirm Void Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openRefundModal(id, transId, maxRefund) {
        document.getElementById('refundTransIdText').innerText = transId;
        document.getElementById('maxRefundableText').innerText = parseFloat(maxRefund).toFixed(2);
        document.getElementById('refundAmountInput').value = parseFloat(maxRefund).toFixed(2);
        document.getElementById('refundAmountInput').max = maxRefund;
        document.getElementById('refundForm').action = "/payment/refund/" + id;

        const modal = new bootstrap.Modal(document.getElementById('refundModal'));
        modal.show();
    }

    function openVoidModal(id, transId, amount) {
        document.getElementById('voidTransIdText').innerText = transId;
        document.getElementById('voidAmountText').innerText = parseFloat(amount).toFixed(2);
        document.getElementById('voidForm').action = "/payment/void/" + id;

        const modal = new bootstrap.Modal(document.getElementById('voidModal'));
        modal.show();
    }

    document.getElementById('refundForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnExecuteRefund');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sending Refund to Gateway...';
    });

    document.getElementById('voidForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnExecuteVoid');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting Void Request...';
    });
</script>
@endsection