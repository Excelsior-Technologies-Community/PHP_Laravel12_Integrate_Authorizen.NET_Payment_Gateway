<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceiptMail;
use App\Models\CustomerPaymentProfile;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Services\AuthorizeNetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    protected AuthorizeNetService $authorizeNet;

    public function __construct(AuthorizeNetService $authorizeNet)
    {
        $this->authorizeNet = $authorizeNet;
    }

    /**
     * Show payment form with tabs for Credit Card, eCheck / ACH, and Saved Cards
     */
    public function showPaymentForm()
    {
        $testCards = $this->authorizeNet->getTestCards();
        $testBank = $this->authorizeNet->getTestBankAccounts();
        $connectionStatus = $this->authorizeNet->testConnection();
        $credentials = $this->authorizeNet->validateCredentials();
        $savedCards = CustomerPaymentProfile::orderBy('is_default', 'desc')->get();

        return view('payment.form', compact('testCards', 'testBank', 'connectionStatus', 'credentials', 'savedCards'));
    }

    /**
     * Process payment supporting: Credit Card, eCheck (ACH), and Saved Card (1-Click)
     */
    public function processPayment(Request $request)
    {
        $paymentType = $request->input('payment_type', 'credit_card');

        if ($paymentType === 'echeck') {
            return $this->processEcheckPayment($request);
        } elseif ($paymentType === 'saved_card') {
            return $this->processSavedCardPayment($request);
        }

        return $this->processCreditCardPayment($request);
    }

    /**
     * Process standard Credit Card payment
     */
    protected function processCreditCardPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'card_number' => 'required|string',
            'exp_date' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'cvv' => 'required|string|min:3|max:4',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'customer_email' => 'required|email|max:100',
            'address' => 'required|string|max:100',
            'city' => 'required|string|max:50',
            'state' => 'required|string|max:5',
            'zip' => 'required|string|max:10',
            'country' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Please resolve the validation errors below.');
        }

        $connectionStatus = $this->authorizeNet->testConnection();
        if (!$connectionStatus['connected']) {
            return back()
                ->with('error', 'Cannot connect to payment gateway. Please verify gateway credentials.')
                ->withInput();
        }

        $cleanCardNumber = str_replace(' ', '', $request->card_number);
        $cardBrand = $this->authorizeNet->detectCardBrand($cleanCardNumber);
        $cardLast4 = substr($cleanCardNumber, -4);
        $invoiceNumber = 'INV-' . time() . rand(100, 999);

        $paymentData = [
            'amount' => $request->amount,
            'card_number' => $cleanCardNumber,
            'exp_date' => $request->exp_date,
            'cvv' => $request->cvv,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'billing_address' => [
                'firstName' => $request->first_name,
                'lastName' => $request->last_name,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
                'country' => $request->country,
            ],
            'invoice_number' => $invoiceNumber,
            'description' => 'Payment for order',
        ];

        $result = $this->authorizeNet->chargeCreditCard($paymentData);

        if ($result['success']) {
            $customerProfileId = null;
            $paymentProfileId = null;

            // Handle Secure Card Tokenization (CIM) if requested
            if ($request->boolean('save_card')) {
                $cimResult = $this->authorizeNet->createCustomerProfile([
                    'customer_name' => $request->first_name . ' ' . $request->last_name,
                    'email' => $request->customer_email,
                    'card_number' => $cleanCardNumber,
                    'exp_date' => $request->exp_date,
                    'cvv' => $request->cvv,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'zip' => $request->zip,
                    'country' => $request->country,
                ]);

                if ($cimResult['success']) {
                    $customerProfileId = $cimResult['customer_profile_id'];
                    $paymentProfileId = $cimResult['payment_profile_id'];

                    CustomerPaymentProfile::create([
                        'customer_name' => $request->first_name . ' ' . $request->last_name,
                        'customer_email' => $request->customer_email,
                        'customer_profile_id' => $customerProfileId,
                        'payment_profile_id' => $paymentProfileId,
                        'card_last4' => $cardLast4,
                        'card_type' => $cardBrand,
                        'expiration_date' => $request->exp_date,
                        'is_default' => CustomerPaymentProfile::count() === 0,
                    ]);
                }
            }

            $payment = Payment::create([
                'transaction_id' => $result['transaction_id'],
                'authorization_code' => $result['auth_code'] ?? null,
                'invoice_number' => $invoiceNumber,
                'payment_method' => 'credit_card',
                'customer_name' => $request->first_name . ' ' . $request->last_name,
                'customer_email' => $request->customer_email,
                'amount' => $request->amount,
                'card_last4' => $cardLast4,
                'card_brand' => $cardBrand,
                'customer_profile_id' => $customerProfileId,
                'payment_profile_id' => $paymentProfileId,
                'payment_status' => 'success',
                'refund_status' => 'none',
                'payment_date' => now(),
            ]);

            $this->sendReceiptEmail($payment);

            session([
                'receipt' => [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'auth_code' => $payment->authorization_code,
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date,
                    'card_last4' => $payment->card_last4,
                    'card_brand' => $payment->card_brand,
                    'payment_method' => 'Credit Card',
                    'customer_name' => $payment->customer_name,
                    'customer_email' => $payment->customer_email,
                    'invoice_number' => $payment->invoice_number,
                    'status' => 'success'
                ]
            ]);

            return redirect()->route('payment.receipt')
                ->with('success', 'Credit card transaction processed successfully!')
                ->with('payment_id', $payment->id);
        }

        // Record Failed Payment
        $failedPayment = Payment::create([
            'transaction_id' => 'FAILED-' . time(),
            'invoice_number' => $invoiceNumber,
            'payment_method' => 'credit_card',
            'customer_name' => $request->first_name . ' ' . $request->last_name,
            'customer_email' => $request->customer_email,
            'amount' => $request->amount,
            'card_last4' => $cardLast4,
            'card_brand' => $cardBrand,
            'payment_status' => 'failed',
            'error_message' => $result['message'],
            'payment_date' => now(),
        ]);

        $this->sendReceiptEmail($failedPayment);

        return back()
            ->with('error', 'Payment failed: ' . $result['message'])
            ->withInput();
    }

    /**
     * Process Direct Bank Transfer / ACH (eCheck) Payment
     */
    protected function processEcheckPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'routing_number' => 'required|digits:9',
            'account_number' => 'required|string|min:5|max:17',
            'name_on_account' => 'required|string|max:100',
            'bank_name' => 'required|string|max:100',
            'account_type' => 'required|in:checking,savings,businessChecking',
            'customer_email' => 'required|email|max:100',
            'address' => 'required|string|max:100',
            'city' => 'required|string|max:50',
            'state' => 'required|string|max:5',
            'zip' => 'required|string|max:10',
            'country' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Please resolve the eCheck validation errors below.');
        }

        $invoiceNumber = 'ACH-' . time() . rand(100, 999);
        $accountLast4 = substr($request->account_number, -4);

        $echeckData = [
            'amount' => $request->amount,
            'routing_number' => $request->routing_number,
            'account_number' => $request->account_number,
            'name_on_account' => $request->name_on_account,
            'bank_name' => $request->bank_name,
            'account_type' => $request->account_type,
            'customer_name' => $request->name_on_account,
            'first_name' => $request->name_on_account,
            'last_name' => '',
            'billing_address' => [
                'firstName' => $request->name_on_account,
                'lastName' => '',
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
                'country' => $request->country,
            ],
            'invoice_number' => $invoiceNumber,
        ];

        $result = $this->authorizeNet->chargeBankAccount($echeckData);

        if ($result['success']) {
            $payment = Payment::create([
                'transaction_id' => $result['transaction_id'],
                'authorization_code' => $result['auth_code'] ?? null,
                'invoice_number' => $invoiceNumber,
                'payment_method' => 'echeck',
                'customer_name' => $request->name_on_account,
                'customer_email' => $request->customer_email,
                'amount' => $request->amount,
                'bank_name' => $request->bank_name,
                'account_type' => $request->account_type,
                'account_last4' => $accountLast4,
                'card_brand' => 'eCheck (ACH)',
                'payment_status' => 'success',
                'refund_status' => 'none',
                'payment_date' => now(),
            ]);

            $this->sendReceiptEmail($payment);

            session([
                'receipt' => [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'auth_code' => $payment->authorization_code,
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date,
                    'card_last4' => $payment->account_last4,
                    'card_brand' => 'eCheck / ACH',
                    'payment_method' => 'eCheck Bank Transfer',
                    'customer_name' => $payment->customer_name,
                    'customer_email' => $payment->customer_email,
                    'invoice_number' => $payment->invoice_number,
                    'status' => 'success'
                ]
            ]);

            return redirect()->route('payment.receipt')
                ->with('success', 'eCheck bank transfer processed successfully!')
                ->with('payment_id', $payment->id);
        }

        $failedPayment = Payment::create([
            'transaction_id' => 'FAILED-ACH-' . time(),
            'invoice_number' => $invoiceNumber,
            'payment_method' => 'echeck',
            'customer_name' => $request->name_on_account,
            'customer_email' => $request->customer_email,
            'amount' => $request->amount,
            'bank_name' => $request->bank_name,
            'account_type' => $request->account_type,
            'account_last4' => $accountLast4,
            'card_brand' => 'eCheck (ACH)',
            'payment_status' => 'failed',
            'error_message' => $result['message'],
            'payment_date' => now(),
        ]);

        $this->sendReceiptEmail($failedPayment);

        return back()
            ->with('error', 'eCheck processing failed: ' . $result['message'])
            ->withInput();
    }

    /**
     * Process 1-Click Checkout with Tokenized Saved Card (CIM)
     */
    protected function processSavedCardPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'saved_profile_id' => 'required|exists:customer_payment_profiles,id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Please choose a valid saved payment card.');
        }

        $profile = CustomerPaymentProfile::findOrFail($request->saved_profile_id);
        $invoiceNumber = 'CIM-' . time() . rand(100, 999);

        $result = $this->authorizeNet->chargeCustomerProfile(
            $profile->customer_profile_id,
            $profile->payment_profile_id,
            (float) $request->amount
        );

        if ($result['success']) {
            $payment = Payment::create([
                'transaction_id' => $result['transaction_id'],
                'authorization_code' => $result['auth_code'] ?? null,
                'invoice_number' => $invoiceNumber,
                'payment_method' => 'saved_card',
                'customer_name' => $profile->customer_name,
                'customer_email' => $profile->customer_email,
                'amount' => $request->amount,
                'card_last4' => $profile->card_last4,
                'card_brand' => $profile->card_type,
                'customer_profile_id' => $profile->customer_profile_id,
                'payment_profile_id' => $profile->payment_profile_id,
                'payment_status' => 'success',
                'refund_status' => 'none',
                'payment_date' => now(),
            ]);

            $this->sendReceiptEmail($payment);

            session([
                'receipt' => [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'auth_code' => $payment->authorization_code,
                    'amount' => $payment->amount,
                    'date' => $payment->payment_date,
                    'card_last4' => $payment->card_last4,
                    'card_brand' => $payment->card_brand,
                    'payment_method' => 'Saved Card (1-Click CIM)',
                    'customer_name' => $payment->customer_name,
                    'customer_email' => $payment->customer_email,
                    'invoice_number' => $payment->invoice_number,
                    'status' => 'success'
                ]
            ]);

            return redirect()->route('payment.receipt')
                ->with('success', '1-Click payment processed via tokenized card profile!')
                ->with('payment_id', $payment->id);
        }

        return back()
            ->with('error', 'Saved card payment declined: ' . $result['message'])
            ->withInput();
    }

    /**
     * Helper to safely send transaction email without blocking flow
     */
    protected function sendReceiptEmail(Payment $payment): void
    {
        if (empty($payment->customer_email)) {
            return;
        }

        try {
            Mail::to($payment->customer_email)->send(new PaymentReceiptMail($payment));
        } catch (\Exception $e) {
            Log::warning('Email receipt could not be sent: ' . $e->getMessage());
        }
    }

    /**
     * Show success page
     */
    public function success()
    {
        if (!session('success')) {
            return redirect()->route('payment.form');
        }

        return view('payment.success');
    }

    /**
     * Show receipt page
     */
    public function receipt()
    {
        $receipt = session('receipt');

        if (!$receipt) {
            return redirect()->route('payment.form');
        }

        $receipt['merchant_name'] = 'Authorize.Net Merchant Store';
        return view('payment.receipt', compact('receipt'));
    }

    /**
     * Printable & Downloadable PDF-ready Invoice
     */
    public function invoice($id)
    {
        $payment = Payment::with('refunds')->findOrFail($id);
        return view('payment.invoice', compact('payment'));
    }

    /**
     * Show payment history with search, status, and method filters
     */
    public function history(Request $request)
    {
        $transactions = Payment::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $transactions->where(function ($query) use ($search) {
                $query->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('card_last4', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $query->orWhere('amount', $search);
                }
            });
        }

        if ($request->filled('status')) {
            $transactions->where('payment_status', $request->status);
        }

        if ($request->filled('method')) {
            $transactions->where('payment_method', $request->method);
        }

        if ($request->filled('refund_status')) {
            $transactions->where('refund_status', $request->refund_status);
        }

        if ($request->filled('from_date')) {
            $transactions->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $transactions->whereDate('payment_date', '<=', $request->to_date);
        }

        $transactions = $transactions
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('payment.history', compact('transactions'));
    }

    /**
     * Execute Full or Partial Refund via Authorize.Net API
     */
    public function refund(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if (!$payment->isRefundable()) {
            return back()->with('error', 'This transaction is not eligible for refund.');
        }

        $maxRefundable = $payment->remaining_refundable_amount;

        $request->validate([
            'refund_amount' => "required|numeric|min:0.01|max:{$maxRefundable}",
            'reason' => 'nullable|string|max:255',
        ]);

        $refundAmount = (float) $request->refund_amount;
        $cardLast4 = $payment->card_last4 ?? '0015';

        $result = $this->authorizeNet->refundTransaction(
            $payment->transaction_id,
            $refundAmount,
            $cardLast4
        );

        if ($result['success']) {
            $newRefundedTotal = round((float) $payment->refunded_amount + $refundAmount, 2);
            $newStatus = $newRefundedTotal >= (float) $payment->amount ? 'full' : 'partial';

            PaymentRefund::create([
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'refund_transaction_id' => $result['transaction_id'] ?? ('REF-' . time()),
                'amount' => $refundAmount,
                'refund_type' => 'refund',
                'reason' => $request->reason ?: 'Customer requested refund',
                'status' => 'success',
                'refunded_at' => now(),
            ]);

            $payment->update([
                'refunded_amount' => $newRefundedTotal,
                'refund_status' => $newStatus,
            ]);

            return back()->with('success', "Refund of \${$refundAmount} processed successfully.");
        }

        return back()->with('error', 'Refund failed: ' . $result['message']);
    }

    /**
     * Execute Void on Unsettled Transaction via Authorize.Net API
     */
    public function void(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if (!$payment->isVoidable()) {
            return back()->with('error', 'This transaction cannot be voided.');
        }

        $result = $this->authorizeNet->voidTransaction($payment->transaction_id);

        if ($result['success']) {
            PaymentRefund::create([
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'refund_transaction_id' => $result['transaction_id'] ?? ('VOID-' . time()),
                'amount' => $payment->amount,
                'refund_type' => 'void',
                'reason' => $request->reason ?: 'Unsettled transaction voided',
                'status' => 'success',
                'refunded_at' => now(),
            ]);

            $payment->update([
                'refunded_amount' => $payment->amount,
                'refund_status' => 'voided',
            ]);

            return back()->with('success', 'Transaction voided successfully before settlement.');
        }

        return back()->with('error', 'Void failed: ' . $result['message']);
    }

    /**
     * Show Refund & Void Audit History
     */
    public function refundsHistory()
    {
        $refunds = PaymentRefund::with('payment')->latest()->paginate(15);
        $totalRefundedAmount = PaymentRefund::where('status', 'success')->where('refund_type', 'refund')->sum('amount');
        $totalVoidedCount = PaymentRefund::where('status', 'success')->where('refund_type', 'void')->count();

        return view('payment.refunds', compact('refunds', 'totalRefundedAmount', 'totalVoidedCount'));
    }

    /**
     * Manage Tokenized Saved Cards (CIM)
     */
    public function savedCards()
    {
        $cards = CustomerPaymentProfile::latest()->get();
        return view('payment.saved-cards', compact('cards'));
    }

    /**
     * Delete Saved Card
     */
    public function deleteSavedCard($id)
    {
        $card = CustomerPaymentProfile::findOrFail($id);
        $this->authorizeNet->deleteCustomerProfile($card->customer_profile_id);
        $card->delete();

        return back()->with('success', 'Saved payment card removed from vault.');
    }

    /**
     * Set Card as Default
     */
    public function setDefaultSavedCard($id)
    {
        CustomerPaymentProfile::query()->update(['is_default' => false]);
        $card = CustomerPaymentProfile::findOrFail($id);
        $card->update(['is_default' => true]);

        return back()->with('success', 'Default payment card updated.');
    }

    /**
     * Dashboard view with KPI cards & Chart.js placeholders
     */
    public function dashboard()
    {
        $totalPayments = Payment::count();
        $successPayments = Payment::where('payment_status', 'success')->count();
        $failedPayments = Payment::where('payment_status', 'failed')->count();
        $todayPayments = Payment::whereDate('payment_date', today())->count();

        $grossRevenue = (float) Payment::where('payment_status', 'success')->sum('amount');
        $totalRefunded = (float) Payment::sum('refunded_amount');
        $netRevenue = max(0, round($grossRevenue - $totalRefunded, 2));

        $todayRevenue = (float) Payment::whereDate('payment_date', today())
            ->where('payment_status', 'success')
            ->sum('amount');

        $avgOrderValue = $successPayments > 0 ? round($grossRevenue / $successPayments, 2) : 0;
        $recentPayments = Payment::latest()->take(6)->get();

        return view('payment.dashboard', compact(
            'totalPayments',
            'successPayments',
            'failedPayments',
            'todayPayments',
            'grossRevenue',
            'totalRefunded',
            'netRevenue',
            'todayRevenue',
            'avgOrderValue',
            'recentPayments'
        ));
    }

    /**
     * API for 30-day Chart.js analytics & revenue metrics
     */
    public function analytics()
    {
        $startDate = Carbon::today()->subDays(29);
        $days = [];
        $revenues = [];
        $counts = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dateStr = $date->format('M d');
            $days[] = $dateStr;

            $dayPayments = Payment::whereDate('payment_date', $date->toDateString())
                ->where('payment_status', 'success')
                ->get();

            $revenues[] = round((float) $dayPayments->sum('amount'), 2);
            $counts[] = $dayPayments->count();
        }

        // Card Brand & Method Distribution
        $visaCount = Payment::where('card_brand', 'like', '%Visa%')->count();
        $masterCount = Payment::where('card_brand', 'like', '%MasterCard%')->count();
        $amexCount = Payment::where('card_brand', 'like', '%Amex%')->count();
        $discoverCount = Payment::where('card_brand', 'like', '%Discover%')->count();
        $echeckCount = Payment::where('payment_method', 'echeck')->count();
        $otherCount = max(0, Payment::count() - ($visaCount + $masterCount + $amexCount + $discoverCount + $echeckCount));

        // Status Distribution
        $successCount = Payment::where('payment_status', 'success')->where('refund_status', 'none')->count();
        $failedCount = Payment::where('payment_status', 'failed')->count();
        $refundedCount = Payment::whereIn('refund_status', ['partial', 'full'])->count();
        $voidedCount = Payment::where('refund_status', 'voided')->count();

        return response()->json([
            'trend' => [
                'labels' => $days,
                'revenue' => $revenues,
                'counts' => $counts,
            ],
            'brands' => [
                'labels' => ['Visa', 'MasterCard', 'Amex', 'Discover', 'eCheck (ACH)', 'Other'],
                'data' => [$visaCount, $masterCount, $amexCount, $discoverCount, $echeckCount, $otherCount],
            ],
            'status' => [
                'labels' => ['Approved', 'Failed', 'Refunded', 'Voided'],
                'data' => [$successCount, $failedCount, $refundedCount, $voidedCount],
            ],
        ]);
    }

    /**
     * Test payment gateway connection API
     */
    public function testConnection()
    {
        $result = $this->authorizeNet->testConnection();
        $credentials = $this->authorizeNet->validateCredentials();

        return response()->json([
            'connected' => $result['connected'],
            'credentials' => $credentials,
            'response' => $result['response'] ?? null,
            'status_code' => $result['status_code'] ?? null,
        ]);
    }

    /**
     * Mark manual status override (Success)
     */
    public function markSuccess(Payment $payment)
    {
        $payment->update([
            'payment_status' => 'success',
            'error_message' => null,
        ]);

        return back()->with('success', 'Payment status updated to Success.');
    }

    /**
     * Mark manual status override (Failed)
     */
    public function markFailed(Payment $payment)
    {
        $payment->update([
            'payment_status' => 'failed',
            'error_message' => 'Manually marked as failed',
        ]);

        return back()->with('success', 'Payment status updated to Failed.');
    }

    /**
     * Delete transaction record
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();
        return back()->with('success', 'Transaction deleted successfully.');
    }

    /**
     * Export Transactions to CSV
     */
    public function export()
    {
        $fileName = 'payments_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
        ];

        $columns = [
            'ID',
            'Invoice Number',
            'Transaction ID',
            'Authorization Code',
            'Method',
            'Customer Name',
            'Customer Email',
            'Amount',
            'Refunded Amount',
            'Card / Account',
            'Brand',
            'Payment Status',
            'Refund Status',
            'Date',
        ];

        return new StreamedResponse(function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            Payment::latest('id')->chunk(100, function ($payments) use ($file) {
                foreach ($payments as $payment) {
                    fputcsv($file, [
                        $payment->id,
                        $payment->invoice_number,
                        $payment->transaction_id,
                        $payment->authorization_code,
                        $payment->payment_method,
                        $payment->customer_name,
                        $payment->customer_email,
                        $payment->amount,
                        $payment->refunded_amount,
                        $payment->payment_method === 'echeck' ? $payment->account_last4 : $payment->card_last4,
                        $payment->card_brand,
                        $payment->payment_status,
                        $payment->refund_status,
                        $payment->payment_date ? $payment->payment_date->toDateTimeString() : '',
                    ]);
                }
            });

            fclose($file);
        }, 200, $headers);
    }
}
