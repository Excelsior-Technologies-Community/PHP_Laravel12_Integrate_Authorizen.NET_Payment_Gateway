<?php

namespace App\Http\Controllers;

use App\Services\AuthorizeNetService;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $authorizeNet;

    public function __construct(AuthorizeNetService $authorizeNet)
    {
        $this->authorizeNet = $authorizeNet;
    }

    /**
     * Show payment form with connection test
     */
    public function showPaymentForm()
    {
        $testCards = $this->authorizeNet->getTestCards();

        // Test connection to show status
        $connectionStatus = $this->authorizeNet->testConnection();
        $credentials = $this->authorizeNet->validateCredentials();

        return view('payment.form', compact('testCards', 'connectionStatus', 'credentials'));
    }

    /**
     * Process payment with better validation
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'card_number' => 'required|string',
            'exp_date' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'cvv' => 'required|string|min:3|max:4',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'address' => 'required|string|max:100',
            'city' => 'required|string|max:50',
            'state' => 'required|string|max:2',
            'zip' => 'required|string|max:10',
            'country' => 'required|string|max:50',
        ], [
            'exp_date.regex' => 'Expiration date must be in YYYY-MM format',
            'amount.min' => 'Amount must be at least $0.01',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', 'Please fix the validation errors below.');
        }

        $connectionStatus = $this->authorizeNet->testConnection();

        if (!$connectionStatus['connected']) {
            return back()
                ->with('error', 'Cannot connect to payment gateway. Please check credentials.')
                ->withInput();
        }

        $paymentData = [
            'amount' => $request->amount,
            'card_number' => $request->card_number,
            'exp_date' => $request->exp_date,
            'cvv' => $request->cvv,
            'billing_address' => [
                'firstName' => $request->first_name,
                'lastName' => $request->last_name,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
                'country' => $request->country,
            ],
            'invoice_number' => 'INV-' . time() . rand(100, 999),
            'description' => 'Payment for order',
        ];

        $result = $this->authorizeNet->chargeCreditCard($paymentData);

if ($result['success']) {

    $payment = Payment::create([
        'transaction_id' => $result['transaction_id'],
        'authorization_code' => $result['auth_code'] ?? null,
        'invoice_number' => $paymentData['invoice_number'],
        'customer_name' => $request->first_name . ' ' . $request->last_name,
        'amount' => $request->amount,
        'card_last4' => substr($request->card_number, -4),
        'payment_status' => 'success',
        'payment_date' => now(),
    ]);


    session([
        'receipt' => [
            'id' => $payment->transaction_id,
            'transaction_id' => $payment->transaction_id,
            'auth_code' => $payment->authorization_code,
            'amount' => $payment->amount,
            'date' => $payment->payment_date,
            'card_last4' => $payment->card_last4,
            'customer_name' => $payment->customer_name,
            'invoice_number' => $payment->invoice_number,
            'status' => 'success'
        ]
    ]);

            return redirect()->route('payment.receipt')
                ->with('success', 'Payment processed successfully!')
                ->with('transaction_id', $result['transaction_id'])
                ->with('auth_code', $result['auth_code'] ?? '')
                ->with('amount', $request->amount);
        } else {

Payment::create([
    'transaction_id' => 'FAILED-' . time(),
    'invoice_number' => $paymentData['invoice_number'],
    'customer_name' => $request->first_name . ' ' . $request->last_name,
    'amount' => $request->amount,
    'card_last4' => substr($request->card_number, -4),
    'payment_status' => 'failed',
    'error_message' => $result['message'],
    'payment_date' => now(),
]);

            return back()
                ->with('error', 'Payment failed: ' . $result['message'])
                ->withInput()
                ->with('raw_error', $result['raw_response'] ?? null);
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

public function receipt()
{
    $receipt = session('receipt');

    if (!$receipt) {
        return redirect()->route('payment.form');
    }

    $receipt['merchant_name'] = 'Laravel Payment Store';

    return view('payment.receipt', compact('receipt'));
}
    /**
     * Show payment history from session
     */
public function history(Request $request)
{
    $transactions = Payment::query();


    if ($request->search) {

        $search = $request->search;

        $transactions->where(function($query) use ($search){

            $query->where('transaction_id','like',"%$search%")
                  ->orWhere('card_last4','like',"%$search%");

        });

    }


    if ($request->status) {

        $transactions->where(
            'payment_status',
            $request->status
        );

    }


    $transactions = $transactions
        ->latest()
        ->get();


    return view('payment.history', [
        'transactions'=>$transactions
    ]);
}

    /**
     * Test payment gateway connection
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
}
