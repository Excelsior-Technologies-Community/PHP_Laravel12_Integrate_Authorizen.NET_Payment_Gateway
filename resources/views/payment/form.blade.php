@extends('layouts.app')

@section('title', 'Make Payment - Authorize.Net Gateway')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">

        <!-- Gateway Connection Status Banner -->
        @if(isset($connectionStatus) && $connectionStatus['connected'])
            <div class="alert alert-success d-flex align-items-center py-2 px-3 mb-4 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check fs-5 me-2 text-success"></i>
                <div class="small flex-grow-1">
                    <strong>Gateway Connected:</strong> Authorize.Net Sandbox mode active (Login ID: <code>{{ $credentials['api_login_id'] ?? 'Configured' }}</code>).
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Ready for Payments</span>
            </div>
        @else
            <div class="alert alert-warning d-flex align-items-center py-2 px-3 mb-4 shadow-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation fs-5 me-2 text-warning"></i>
                <div class="small flex-grow-1">
                    <strong>Gateway Standby:</strong> Live credentials validation in progress or operating in test simulation mode.
                </div>
            </div>
        @endif

        <!-- Test Credentials Accordion / Helper -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="fa-solid fa-flask-vial text-primary me-2"></i> Sandbox Test Cards &amp; eCheck Bank Accounts
                </span>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#testCredentialsCollapse">
                    Toggle Test Data
                </button>
            </div>
            <div class="collapse show" id="testCredentialsCollapse">
                <div class="card-body bg-light border-top">
                    <div class="row g-3">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-credit-card me-1"></i> Test Credit Cards:</h6>
                            <ul class="list-unstyled small mb-0 font-monospace">
                                <li><strong>Visa:</strong> <code>4007000000027</code></li>
                                <li><strong>MasterCard:</strong> <code>5424000000000015</code></li>
                                <li><strong>Amex:</strong> <code>370000000000002</code></li>
                                <li><strong>Discover:</strong> <code>6011000000000012</code></li>
                                <li><strong>Visa (Declined):</strong> <code>4222222222222</code></li>
                            </ul>
                            <small class="text-muted d-block mt-1">Expiry: Any future date (YYYY-MM), CVV: 123</small>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-building-columns me-1"></i> Test eCheck (ACH):</h6>
                            <ul class="list-unstyled small mb-0 font-monospace">
                                <li><strong>Routing Number:</strong> <code>122105155</code></li>
                                <li><strong>Checking Account:</strong> <code>1234567890</code></li>
                                <li><strong>Savings Account:</strong> <code>9876543210</code></li>
                                <li><strong>Bank Name:</strong> <code>Authorize.Net Test Bank</code></li>
                            </ul>
                            <small class="text-muted d-block mt-1">Account Types: Checking / Savings / Business Checking</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Payment Checkout Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-lock me-2"></i> Secure Checkout
                    </h5>
                    <span class="badge bg-light text-dark fw-semibold">256-Bit SSL Encrypted</span>
                </div>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('payment.process') }}" method="POST" id="checkoutForm">
                    @csrf

                    <!-- Order Total Amount -->
                    <div class="mb-4 p-3 bg-light rounded border">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <label for="amount" class="form-label fw-bold text-dark mb-0">Payment Amount ($ USD)</label>
                                <small class="text-muted d-block">Enter the payment charge amount</small>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white fw-bold">$</span>
                                    <input type="number"
                                           class="form-control fw-bold fs-4 text-primary"
                                           id="amount"
                                           name="amount"
                                           value="{{ old('amount', '25.00') }}"
                                           step="0.01"
                                           min="0.01"
                                           max="999999.99"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Tabs -->
                    <label class="form-label fw-bold text-dark mb-2">Select Payment Method</label>
                    <ul class="nav nav-pills nav-fill mb-4" id="paymentMethodTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-2"
                                    id="card-tab"
                                    data-bs-toggle="pill"
                                    data-bs-target="#cardTabPane"
                                    type="button"
                                    role="tab"
                                    onclick="setPaymentType('credit_card')">
                                <i class="fa-solid fa-credit-card me-1"></i> Credit / Debit Card
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-2"
                                    id="echeck-tab"
                                    data-bs-toggle="pill"
                                    data-bs-target="#echeckTabPane"
                                    type="button"
                                    role="tab"
                                    onclick="setPaymentType('echeck')">
                                <i class="fa-solid fa-building-columns me-1"></i> eCheck (ACH Bank Transfer)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-2 {{ $savedCards->isEmpty() ? 'disabled' : '' }}"
                                    id="saved-tab"
                                    data-bs-toggle="pill"
                                    data-bs-target="#savedTabPane"
                                    type="button"
                                    role="tab"
                                    onclick="setPaymentType('saved_card')">
                                <i class="fa-solid fa-vault me-1"></i> Pay with Saved Card ({{ $savedCards->count() }})
                            </button>
                        </li>
                    </ul>

                    <input type="hidden" name="payment_type" id="payment_type" value="credit_card">

                    <div class="tab-content" id="paymentMethodTabContent">

                        <!-- Tab 1: Credit Card Information -->
                        <div class="tab-pane fade show active" id="cardTabPane" role="tabpanel">
                            <h6 class="fw-bold text-secondary text-uppercase small mb-3">Card Details</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold">Card Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="fa-regular fa-credit-card text-muted"></i></span>
                                        <input type="text"
                                               class="form-control"
                                               name="card_number"
                                               id="card_number"
                                               placeholder="4007 0000 0002 7"
                                               value="{{ old('card_number') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Expiration Date (YYYY-MM)</label>
                                    <input type="text"
                                           class="form-control"
                                           name="exp_date"
                                           placeholder="2027-12"
                                           value="{{ old('exp_date', '2027-12') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Security Code (CVV)</label>
                                    <input type="text"
                                           class="form-control"
                                           name="cvv"
                                           placeholder="123"
                                           maxlength="4"
                                           value="{{ old('cvv', '123') }}">
                                </div>
                                <div class="col-12">
                                    <div class="form-check p-3 bg-light rounded border">
                                        <input class="form-check-input" type="checkbox" name="save_card" value="1" id="saveCardCheckbox">
                                        <label class="form-check-label text-dark fw-semibold" for="saveCardCheckbox">
                                            <i class="fa-solid fa-shield-cat text-primary me-1"></i>
                                            Save this card securely in Authorize.Net vault for faster 1-click checkout
                                        </label>
                                        <small class="text-muted d-block mt-1 ms-4">
                                            Card details are tokenized via CIM; full numbers are never stored on this server.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: eCheck / ACH Bank Transfer -->
                        <div class="tab-pane fade" id="echeckTabPane" role="tabpanel">
                            <h6 class="fw-bold text-success text-uppercase small mb-3">ACH Direct Bank Account Details</h6>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Bank Name</label>
                                    <input type="text"
                                           class="form-control"
                                           name="bank_name"
                                           placeholder="Authorize.Net Test Bank"
                                           value="{{ old('bank_name', 'Wells Fargo Bank') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Account Type</label>
                                    <select name="account_type" class="form-select">
                                        <option value="checking" selected>Checking Account</option>
                                        <option value="savings">Savings Account</option>
                                        <option value="businessChecking">Business Checking</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">9-Digit Routing Number (ABA)</label>
                                    <input type="text"
                                           class="form-control font-monospace"
                                           name="routing_number"
                                           placeholder="122105155"
                                           maxlength="9"
                                           value="{{ old('routing_number', '122105155') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Bank Account Number</label>
                                    <input type="text"
                                           class="form-control font-monospace"
                                           name="account_number"
                                           placeholder="1234567890"
                                           value="{{ old('account_number', '1234567890') }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold">Name on Bank Account</label>
                                    <input type="text"
                                           class="form-control"
                                           name="name_on_account"
                                           placeholder="John Doe"
                                           value="{{ old('name_on_account', 'Valued Customer') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Pay with Saved Card (1-Click CIM) -->
                        <div class="tab-pane fade" id="savedTabPane" role="tabpanel">
                            <h6 class="fw-bold text-purple text-uppercase small mb-3">Choose a Saved Vault Card</h6>
                            @if($savedCards->isNotEmpty())
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label small fw-semibold">Select Tokenized Card</label>
                                        <select name="saved_profile_id" class="form-select form-select-lg">
                                            @foreach($savedCards as $saved)
                                                <option value="{{ $saved->id }}">
                                                    {{ $saved->card_type }} ending in **** {{ $saved->card_last4 }}
                                                    (Exp: {{ $saved->expiration_date ?? 'N/A' }})
                                                    {{ $saved->is_default ? ' - [DEFAULT CARD]' : '' }}
                                                    -- {{ $saved->customer_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 small mb-0">
                                            <i class="fa-solid fa-lock me-1"></i> Authorize.Net Customer Information Manager (CIM) token will be charged directly.
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-vault fs-1 mb-2"></i>
                                    <p class="mb-0">No saved cards found in vault yet. Pay with Credit Card and check "Save Card" to tokenize for 1-click checkout.</p>
                                </div>
                            @endif
                        </div>

                    </div>

                    <!-- Customer & Billing Details Section -->
                    <hr class="my-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="fa-solid fa-user text-secondary me-2"></i> Customer &amp; Billing Address
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name</label>
                            <input type="text"
                                   class="form-control"
                                   name="first_name"
                                   placeholder="First name"
                                   value="{{ old('first_name', 'Alex') }}"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name</label>
                            <input type="text"
                                   class="form-control"
                                   name="last_name"
                                   placeholder="Last name"
                                   value="{{ old('last_name', 'Miller') }}"
                                   required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Customer Email Address (for automated receipt &amp; alerts)</label>
                            <input type="email"
                                   class="form-control"
                                   name="customer_email"
                                   placeholder="customer@example.com"
                                   value="{{ old('customer_email', 'alex.miller@example.com') }}"
                                   required>
                            <div class="form-text">An automated PDF invoice &amp; transaction confirmation receipt will be sent here.</div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Street Address</label>
                            <input type="text"
                                   class="form-control"
                                   name="address"
                                   placeholder="123 Financial Way"
                                   value="{{ old('address', '123 Market Street') }}"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text"
                                   class="form-control"
                                   name="city"
                                   placeholder="City"
                                   value="{{ old('city', 'New York') }}"
                                   required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">State</label>
                            <input type="text"
                                   class="form-control"
                                   name="state"
                                   placeholder="NY"
                                   maxlength="5"
                                   value="{{ old('state', 'NY') }}"
                                   required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Zip Code</label>
                            <input type="text"
                                   class="form-control"
                                   name="zip"
                                   placeholder="10001"
                                   maxlength="10"
                                   value="{{ old('zip', '10001') }}"
                                   required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Country</label>
                            <input type="text"
                                   class="form-control"
                                   name="country"
                                   placeholder="USA"
                                   value="{{ old('country', 'USA') }}"
                                   required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm py-3" id="btnSubmitPayment">
                            <i class="fa-solid fa-lock me-2"></i> Submit Payment
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    function setPaymentType(type) {
        document.getElementById('payment_type').value = type;
    }

    document.getElementById('checkoutForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmitPayment');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Authorizing &amp; Processing Transaction...';
    });
</script>
@endsection