@extends('layouts.app')

@section('title', 'Tokenized Saved Cards (CIM) - Authorize.Net')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-vault text-primary me-2"></i> Tokenized Saved Cards Vault (CIM)
            </h4>
            <small class="text-muted">Customer Information Manager (CIM) enables secure 1-click checkout without storing PAN on server.</small>
        </div>
        <a href="{{ route('payment.form') }}" class="btn btn-primary btn-sm fw-semibold">
            <i class="fa-solid fa-plus me-1"></i> Add / Tokenize New Card
        </a>
    </div>

    <div class="card-body p-0">
        <div class="alert alert-info rounded-0 border-0 mb-0 d-flex align-items-center py-2 px-3 small">
            <i class="fa-solid fa-shield-halved fs-5 me-2 text-info"></i>
            <div>
                <strong>PCI-DSS Compliant Storage:</strong> Credit card numbers are vaulted inside Authorize.Net's secure data centers. Only Profile Tokens are referenced.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Customer</th>
                        <th>Card Brand</th>
                        <th>Masked Number</th>
                        <th>Expiration</th>
                        <th>Customer Profile ID</th>
                        <th>Payment Profile ID</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cards as $card)
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#{{ $card->id }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $card->customer_name }}</div>
                            <small class="text-muted">{{ $card->customer_email }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="fa-regular fa-credit-card me-1"></i> {{ $card->card_type }}
                            </span>
                        </td>
                        <td>
                            <span class="font-monospace fw-bold text-dark">**** {{ $card->card_last4 }}</span>
                        </td>
                        <td>
                            <span class="small text-muted">{{ $card->expiration_date ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <code class="small text-secondary">{{ $card->customer_profile_id }}</code>
                        </td>
                        <td>
                            <code class="small text-secondary">{{ $card->payment_profile_id }}</code>
                        </td>
                        <td>
                            @if($card->is_default)
                                <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Default</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Secondary</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                @if(!$card->is_default)
                                <form action="{{ route('payment.saved-cards.default', $card->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-outline-success" title="Set as default card">
                                        <i class="fa-regular fa-star"></i> Default
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('payment.saved-cards.delete', $card->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" onclick="return confirm('Permanently remove this card from Authorize.Net vault?')" title="Delete from vault">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-vault fs-2 mb-2 d-block text-secondary"></i>
                            No tokenized payment cards found in vault.
                            <div class="mt-2">
                                <a href="{{ route('payment.form') }}" class="btn btn-sm btn-outline-primary">
                                    Tokenize a Card Now
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
