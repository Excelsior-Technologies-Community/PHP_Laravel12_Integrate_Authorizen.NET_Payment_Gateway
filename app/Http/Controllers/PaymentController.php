<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
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

        if ($request->filled('search')) {

            $search = $request->search;

            $transactions->where(function ($query) use ($search) {

                $query->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('card_last4', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $query->orWhere('amount', $search);
                }
            });
        }

        if ($request->filled('status')) {

            $transactions->where('payment_status', $request->status);
        }

        if ($request->filled('from_date')) {

            $transactions->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {

            $transactions->whereDate('payment_date', '<=', $request->to_date);
        }

        $transactions = $transactions
            ->oldest()
            ->paginate(5)
            ->withQueryString();

        return view('payment.history', compact('transactions'));
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

    public function dashboard()
    {
        $data = [
            'totalPayments' => Payment::count(),

            'successPayments' => Payment::where('payment_status', 'success')->count(),

            'failedPayments' => Payment::where('payment_status', 'failed')->count(),

            'todayPayments' => Payment::whereDate('payment_date', today())->count(),

            'totalRevenue' => Payment::where('payment_status', 'success')->sum('amount'),

            'todayRevenue' => Payment::whereDate('payment_date', today())
                ->where('payment_status', 'success')
                ->sum('amount'),

            'recentPayments' => Payment::latest()->take(5)->get(),
        ];

        return view('payment.dashboard', $data);
    }

    public function export()
    {
        $fileName = 'payments_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
        ];

        $callback = function () {

            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Transaction ID',
                'Invoice',
                'Customer',
                'Amount',
                'Status',
                'Card',
                'Date'
            ]);

            Payment::latest()->chunk(100, function ($payments) use ($file) {

                foreach ($payments as $payment) {

                    fputcsv($file, [

                        $payment->transaction_id,

                        $payment->invoice_number,

                        $payment->customer_name,

                        $payment->amount,

                        $payment->payment_status,

                        $payment->card_last4,

                        optional($payment->payment_date)->format('Y-m-d H:i:s'),

                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()
            ->route('payment.history')
            ->with('success', 'Payment deleted successfully.');
    }

    public function markFailed(Payment $payment)
    {
        $payment->update([
            'payment_status' => 'failed'
        ]);

        return back()->with('success', 'Payment status updated.');
    }

    public function markSuccess(Payment $payment)
    {
        $payment->update([
            'payment_status' => 'success'
        ]);

        return back()->with('success', 'Payment status updated.');
    }
}
