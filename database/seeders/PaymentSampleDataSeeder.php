<?php

namespace Database\Seeders;

use App\Models\CustomerPaymentProfile;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PaymentSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Tokenized Saved Cards (CIM)
        $profile1 = CustomerPaymentProfile::create([
            'customer_name' => 'Alex Miller',
            'customer_email' => 'alex.miller@example.com',
            'customer_profile_id' => 'CP_' . time() . '101',
            'payment_profile_id' => 'PP_' . time() . '201',
            'card_last4' => '0027',
            'card_type' => 'Visa',
            'expiration_date' => '2028-06',
            'is_default' => true,
        ]);

        $profile2 = CustomerPaymentProfile::create([
            'customer_name' => 'Sarah Connor',
            'customer_email' => 'sarah.connor@example.com',
            'customer_profile_id' => 'CP_' . time() . '102',
            'payment_profile_id' => 'PP_' . time() . '202',
            'card_last4' => '0015',
            'card_type' => 'MasterCard',
            'expiration_date' => '2027-11',
            'is_default' => false,
        ]);

        $profile3 = CustomerPaymentProfile::create([
            'customer_name' => 'Michael Scott',
            'customer_email' => 'michael.scott@example.com',
            'customer_profile_id' => 'CP_' . time() . '103',
            'payment_profile_id' => 'PP_' . time() . '203',
            'card_last4' => '0002',
            'card_type' => 'Amex',
            'expiration_date' => '2029-01',
            'is_default' => false,
        ]);

        // 2. Realistic customers list
        $customers = [
            ['name' => 'Alex Miller', 'email' => 'alex.miller@example.com'],
            ['name' => 'Sarah Connor', 'email' => 'sarah.connor@example.com'],
            ['name' => 'Michael Scott', 'email' => 'michael.scott@example.com'],
            ['name' => 'Rachel Green', 'email' => 'rachel.green@example.com'],
            ['name' => 'James Wilson', 'email' => 'james.wilson@example.com'],
            ['name' => 'Emma Watson', 'email' => 'emma.watson@example.com'],
            ['name' => 'David Warner', 'email' => 'david.warner@example.com'],
            ['name' => 'Bruce Wayne', 'email' => 'bruce.wayne@example.com'],
            ['name' => 'Clark Kent', 'email' => 'clark.kent@example.com'],
            ['name' => 'Diana Prince', 'email' => 'diana.prince@example.com'],
        ];

        $brands = ['Visa', 'MasterCard', 'Amex', 'Discover'];
        $last4s = ['0027', '0015', '0002', '0012', '4242', '1111'];

        // 3. Generate 30 payments distributed across the last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->setHour(rand(9, 20))->setMinute(rand(5, 55));
            $cust = $customers[array_rand($customers)];
            $methodType = rand(1, 10);

            // Determine method
            if ($methodType <= 5) {
                // Credit Card
                $method = 'credit_card';
                $brand = $brands[array_rand($brands)];
                $card4 = $last4s[array_rand($last4s)];
                $bankName = null;
                $accType = null;
                $accLast4 = null;
                $cId = null;
                $pId = null;
            } elseif ($methodType <= 8) {
                // eCheck / ACH
                $method = 'echeck';
                $brand = 'eCheck (ACH)';
                $card4 = null;
                $bankName = 'Authorize.Net Test Bank';
                $accType = rand(0, 1) ? 'checking' : 'savings';
                $accLast4 = '7890';
                $cId = null;
                $pId = null;
            } else {
                // Saved Card (1-Click)
                $method = 'saved_card';
                $brand = $profile1->card_type;
                $card4 = $profile1->card_last4;
                $bankName = null;
                $accType = null;
                $accLast4 = null;
                $cId = $profile1->customer_profile_id;
                $pId = $profile1->payment_profile_id;
            }

            // Status: 90% success, 10% failed
            $isSuccess = ($i !== 7 && $i !== 16 && $i !== 24);
            $amount = rand(20, 250) + (rand(0, 99) / 100);

            $payment = Payment::create([
                'transaction_id' => '60' . rand(100000000, 999999999),
                'authorization_code' => $isSuccess ? 'AUTH' . rand(1000, 9999) : null,
                'invoice_number' => 'INV-' . $date->format('Ymd') . '-' . rand(100, 999),
                'payment_method' => $method,
                'customer_name' => $cust['name'],
                'customer_email' => $cust['email'],
                'amount' => $amount,
                'refunded_amount' => 0.00,
                'card_last4' => $card4,
                'card_brand' => $brand,
                'bank_name' => $bankName,
                'account_type' => $accType,
                'account_last4' => $accLast4,
                'customer_profile_id' => $cId,
                'payment_profile_id' => $pId,
                'payment_status' => $isSuccess ? 'success' : 'failed',
                'refund_status' => 'none',
                'error_message' => $isSuccess ? null : 'The credit card has been declined by issuing bank.',
                'payment_date' => $date,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Add Refunds / Voids to a few specific records
            if ($isSuccess && $i === 12) {
                // Partial refund
                $refAmount = round($payment->amount * 0.4, 2);
                $payment->update([
                    'refunded_amount' => $refAmount,
                    'refund_status' => 'partial',
                ]);

                PaymentRefund::create([
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'refund_transaction_id' => 'REF-' . rand(100000000, 999999999),
                    'amount' => $refAmount,
                    'refund_type' => 'refund',
                    'reason' => 'Customer requested partial refund for damaged package',
                    'status' => 'success',
                    'refunded_at' => $date->copy()->addDays(2),
                    'created_at' => $date->copy()->addDays(2),
                    'updated_at' => $date->copy()->addDays(2),
                ]);
            } elseif ($isSuccess && $i === 20) {
                // Full refund
                $payment->update([
                    'refunded_amount' => $payment->amount,
                    'refund_status' => 'full',
                ]);

                PaymentRefund::create([
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'refund_transaction_id' => 'REF-' . rand(100000000, 999999999),
                    'amount' => $payment->amount,
                    'refund_type' => 'refund',
                    'reason' => 'Customer returned merchandise in full',
                    'status' => 'success',
                    'refunded_at' => $date->copy()->addDays(3),
                    'created_at' => $date->copy()->addDays(3),
                    'updated_at' => $date->copy()->addDays(3),
                ]);
            } elseif ($isSuccess && $i === 1) {
                // Void
                $payment->update([
                    'refunded_amount' => $payment->amount,
                    'refund_status' => 'voided',
                ]);

                PaymentRefund::create([
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'refund_transaction_id' => 'VOID-' . rand(100000000, 999999999),
                    'amount' => $payment->amount,
                    'refund_type' => 'void',
                    'reason' => 'Transaction voided before batch settlement',
                    'status' => 'success',
                    'refunded_at' => $date->copy()->addHours(2),
                    'created_at' => $date->copy()->addHours(2),
                    'updated_at' => $date->copy()->addHours(2),
                ]);
            }
        }
    }
}
