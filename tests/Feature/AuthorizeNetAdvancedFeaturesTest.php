<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\CustomerPaymentProfile;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Services\AuthorizeNetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthorizeNetAdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Feature 1: Test Refund processing and audit tracking
     */
    public function test_refund_processing_updates_payment_and_creates_audit_log(): void
    {
        $payment = Payment::create([
            'transaction_id' => 'TRANS_12345',
            'authorization_code' => 'AUTH_999',
            'invoice_number' => 'INV-1001',
            'payment_method' => 'credit_card',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'amount' => 100.00,
            'refunded_amount' => 0.00,
            'card_last4' => '0015',
            'card_brand' => 'MasterCard',
            'payment_status' => 'success',
            'refund_status' => 'none',
            'payment_date' => now(),
        ]);

        $response = $this->post(route('payment.refund', $payment->id), [
            'refund_amount' => 40.00,
            'reason' => 'Partial product return',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals(40.00, (float) $payment->refunded_amount);
        $this->assertEquals('partial', $payment->refund_status);

        $this->assertDatabaseHas('payment_refunds', [
            'payment_id' => $payment->id,
            'amount' => 40.00,
            'refund_type' => 'refund',
            'reason' => 'Partial product return',
            'status' => 'success',
        ]);
    }

    /**
     * Feature 1: Test Void processing on unsettled transactions
     */
    public function test_void_processing_cancels_transaction(): void
    {
        $payment = Payment::create([
            'transaction_id' => 'TRANS_VOID_123',
            'invoice_number' => 'INV-1002',
            'payment_method' => 'credit_card',
            'customer_name' => 'Alice Smith',
            'amount' => 50.00,
            'card_last4' => '0027',
            'card_brand' => 'Visa',
            'payment_status' => 'success',
            'refund_status' => 'none',
            'payment_date' => now(),
        ]);

        $response = $this->post(route('payment.void', $payment->id), [
            'reason' => 'Accidental duplicate',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('voided', $payment->refund_status);

        $this->assertDatabaseHas('payment_refunds', [
            'payment_id' => $payment->id,
            'refund_type' => 'void',
            'status' => 'success',
        ]);
    }

    /**
     * Feature 1: Test Refunds Audit History page
     */
    public function test_refunds_audit_history_page_loads(): void
    {
        $response = $this->get(route('payment.refunds'));
        $response->assertStatus(200);
        $response->assertSee('Refunds &amp; Void Audit Trail', false);
    }

    /**
     * Feature 2: Test Tokenized Customer Payment Profile (CIM) creation
     */
    public function test_credit_card_charge_with_save_card_creates_tokenized_profile(): void
    {
        Mail::fake();

        $response = $this->post(route('payment.process'), [
            'payment_type' => 'credit_card',
            'amount' => '30.00',
            'card_number' => '4007000000027',
            'exp_date' => '2027-12',
            'cvv' => '123',
            'first_name' => 'Sarah',
            'last_name' => 'Connor',
            'customer_email' => 'sarah@example.com',
            'address' => '100 Cyberdyne Blvd',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90001',
            'country' => 'USA',
            'save_card' => '1',
        ]);

        $response->assertRedirect(route('payment.receipt'));

        $this->assertDatabaseHas('customer_payment_profiles', [
            'customer_email' => 'sarah@example.com',
            'card_last4' => '0027',
        ]);
    }

    /**
     * Feature 2: Test 1-Click checkout using saved card profile
     */
    public function test_saved_card_one_click_checkout(): void
    {
        Mail::fake();

        $profile = CustomerPaymentProfile::create([
            'customer_name' => 'Sarah Connor',
            'customer_email' => 'sarah@example.com',
            'customer_profile_id' => 'CP_TEST_123',
            'payment_profile_id' => 'PP_TEST_456',
            'card_last4' => '0027',
            'card_type' => 'Visa',
            'expiration_date' => '2027-12',
            'is_default' => true,
        ]);

        $response = $this->post(route('payment.process'), [
            'payment_type' => 'saved_card',
            'saved_profile_id' => $profile->id,
            'amount' => '15.50',
        ]);

        $response->assertRedirect(route('payment.receipt'));

        $this->assertDatabaseHas('payments', [
            'payment_method' => 'saved_card',
            'amount' => 15.50,
            'customer_name' => 'Sarah Connor',
        ]);
    }

    /**
     * Feature 2: Test Saved Cards vault management view
     */
    public function test_saved_cards_vault_view_loads(): void
    {
        $response = $this->get(route('payment.saved-cards'));
        $response->assertStatus(200);
        $response->assertSee('Tokenized Saved Cards Vault (CIM)');
    }

    /**
     * Feature 3: Test Printable and Downloadable PDF Invoice view
     */
    public function test_printable_pdf_invoice_view_renders(): void
    {
        $payment = Payment::create([
            'transaction_id' => 'TRANS_INV_999',
            'invoice_number' => 'INV-INVOICE-TEST',
            'payment_method' => 'credit_card',
            'customer_name' => 'David Warner',
            'customer_email' => 'david@example.com',
            'amount' => 125.00,
            'card_last4' => '0015',
            'card_brand' => 'MasterCard',
            'payment_status' => 'success',
            'refund_status' => 'none',
            'payment_date' => now(),
        ]);

        $response = $this->get(route('payment.invoice', $payment->id));

        $response->assertStatus(200);
        $response->assertSee('INV-INVOICE-TEST');
        $response->assertSee('David Warner');
        $response->assertSee('$125.00');
        $response->assertSee('Print / Save PDF');
    }

    /**
     * Feature 3: Test Payment Receipt Mailable
     */
    public function test_payment_receipt_mailable_builds_correctly(): void
    {
        $payment = Payment::create([
            'transaction_id' => 'TRANS_MAIL_123',
            'invoice_number' => 'INV-MAIL-888',
            'payment_method' => 'credit_card',
            'customer_name' => 'Emma Watson',
            'customer_email' => 'emma@example.com',
            'amount' => 75.00,
            'card_last4' => '0015',
            'payment_status' => 'success',
            'payment_date' => now(),
        ]);

        $mailable = new PaymentReceiptMail($payment);
        $mailable->assertHasSubject('Payment Receipt - Invoice #INV-MAIL-888');
    }

    /**
     * Feature 4: Test 30-Day Analytics API endpoint returns valid JSON structure
     */
    public function test_analytics_api_endpoint_returns_json(): void
    {
        $response = $this->get(route('payment.analytics'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'trend' => [
                'labels',
                'revenue',
                'counts',
            ],
            'brands' => [
                'labels',
                'data',
            ],
            'status' => [
                'labels',
                'data',
            ],
        ]);
    }

    /**
     * Feature 4: Test Dashboard view with KPI cards
     */
    public function test_dashboard_view_loads_with_kpis(): void
    {
        $response = $this->get(route('payment.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Payment Analytics &amp; Revenue Dashboard', false);
        $response->assertSee('Gross Revenue');
        $response->assertSee('Net Revenue');
        $response->assertSee('Average Order (AOV)');
    }

    /**
     * Feature 5: Test eCheck (ACH Direct Bank Transfer) payment processing
     */
    public function test_echeck_ach_payment_processing(): void
    {
        Mail::fake();

        $response = $this->post(route('payment.process'), [
            'payment_type' => 'echeck',
            'amount' => '45.00',
            'routing_number' => '122105155',
            'account_number' => '1234567890',
            'name_on_account' => 'Robert Downey',
            'bank_name' => 'Authorize.Net Test Bank',
            'account_type' => 'checking',
            'customer_email' => 'robert@example.com',
            'address' => '500 Malibu Point',
            'city' => 'Malibu',
            'state' => 'CA',
            'zip' => '90265',
            'country' => 'USA',
        ]);

        $response->assertRedirect(route('payment.receipt'));

        $this->assertDatabaseHas('payments', [
            'payment_method' => 'echeck',
            'amount' => 45.00,
            'customer_name' => 'Robert Downey',
            'account_type' => 'checking',
            'account_last4' => '7890',
            'payment_status' => 'success',
        ]);
    }
}
