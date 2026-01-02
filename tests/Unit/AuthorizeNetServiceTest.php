<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuthorizeNetService;
use Illuminate\Support\Facades\Config;

class AuthorizeNetServiceTest extends TestCase
{
    protected $authorizeNet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authorizeNet = new AuthorizeNetService();
    }

    /** @test */
    public function it_can_retrieve_test_cards()
    {
        $testCards = $this->authorizeNet->getTestCards();
        
        $this->assertIsArray($testCards);
        $this->assertArrayHasKey('visa', $testCards);
        $this->assertArrayHasKey('mastercard', $testCards);
        $this->assertArrayHasKey('amex', $testCards);
    }

    /** @test */
    public function it_has_correct_endpoint_for_sandbox()
    {
        Config::set('authorize.mode', 'sandbox');
        
        $authorizeNet = new AuthorizeNetService();
        
        $this->assertEquals(
            'https://apitest.authorize.net/xml/v1/request.api',
            config('authorize.endpoint')
        );
    }

    /** @test */
    public function it_can_create_charge_payload()
    {
        $paymentData = [
            'amount' => '10.00',
            'card_number' => '4111111111111111',
            'exp_date' => '2025-12',
            'cvv' => '123',
        ];

        $payload = $this->authorizeNet->createChargePayload($paymentData);
        
        $this->assertEquals('authCaptureTransaction', 
            $payload['createTransactionRequest']['transactionRequest']['transactionType']);
        $this->assertEquals('10.00', 
            $payload['createTransactionRequest']['transactionRequest']['amount']);
    }
}