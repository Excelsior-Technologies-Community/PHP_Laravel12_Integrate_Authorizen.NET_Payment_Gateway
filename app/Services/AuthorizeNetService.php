<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AuthorizeNetService
{
    protected $client;
    protected $apiLoginId;
    protected $transactionKey;
    protected $endpoint;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // For testing only, remove in production
        ]);
        $this->apiLoginId = config('authorize.api_login_id');
        $this->transactionKey = config('authorize.transaction_key');
        $this->endpoint = config('authorize.endpoint');
    }

    /**
     * Validate credentials before making API call
     */
    public function validateCredentials()
    {
        if (empty($this->apiLoginId) || empty($this->transactionKey)) {
            throw new \Exception('Authorize.Net credentials are not configured');
        }
        
        return [
            'api_login_id' => substr($this->apiLoginId, 0, 3) . '...' . substr($this->apiLoginId, -3),
            'transaction_key' => substr($this->transactionKey, 0, 3) . '...' . substr($this->transactionKey, -3),
            'endpoint' => $this->endpoint,
            'mode' => config('authorize.mode'),
        ];
    }

    /**
     * Charge a credit card
     */
    public function chargeCreditCard(array $paymentData)
    {
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Create proper payload
            $payload = $this->createChargePayload($paymentData);
            
            // Make API request
            $response = $this->makeRequest($payload);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Authorize.Net Charge Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
                'transaction_id' => null
            ];
        }
    }

    /**
     * Validate payment data
     */
    protected function validatePaymentData(array $data)
    {
        $required = ['amount', 'card_number', 'exp_date'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Required field '{$field}' is missing");
            }
        }
        
        // Validate amount
        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new \Exception('Invalid amount');
        }
        
        // Validate card number (remove spaces)
        $data['card_number'] = str_replace(' ', '', $data['card_number']);
        if (!preg_match('/^\d{13,19}$/', $data['card_number'])) {
            throw new \Exception('Invalid credit card number');
        }
        
        // Validate expiration date (format: YYYY-MM)
        if (!preg_match('/^\d{4}-\d{2}$/', $data['exp_date'])) {
            throw new \Exception('Invalid expiration date format. Use YYYY-MM');
        }
        
        return true;
    }

    /**
     * Create payload for charge transaction (FIXED)
     */
protected function createChargePayload(array $data)
{
    return [
        "createTransactionRequest" => [
            "merchantAuthentication" => [
                "name" => $this->apiLoginId,
                "transactionKey" => $this->transactionKey,
            ],

            "refId" => "REF" . time(),

            "transactionRequest" => [

                "transactionType" => "authCaptureTransaction",

                "amount" => number_format($data['amount'], 2, '.', ''),

                "payment" => [
                    "creditCard" => [
                        "cardNumber" => str_replace(' ', '', $data['card_number']),
                        "expirationDate" => $data['exp_date'],
                        "cardCode" => $data['cvv'],
                    ]
                ],

                "billTo" => [
                    "firstName" => $data['billing_address']['firstName'],
                    "lastName"  => $data['billing_address']['lastName'],
                    "address"   => $data['billing_address']['address'],
                    "city"      => $data['billing_address']['city'],
                    "state"     => $data['billing_address']['state'],
                    "zip"       => $data['billing_address']['zip'],
                    "country"   => $data['billing_address']['country'],
                ]

            ]
        ]
    ];
}
    /**
     * Make API request to Authorize.Net (FIXED)
     */
 protected function makeRequest(array $payload)
{
    try {

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);

        Log::info('========== AUTHORIZE.NET REQUEST ==========');
        Log::info($jsonPayload);

        $response = $this->client->post($this->endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => $jsonPayload,
            'http_errors' => false,
        ]);

        $statusCode = $response->getStatusCode();

        $responseBody = $response->getBody()->getContents();

        // Remove UTF-8 BOM if present
        $responseBody = preg_replace('/^\xEF\xBB\xBF/', '', $responseBody);

        Log::info('========== AUTHORIZE.NET RESPONSE ==========');
        Log::info($responseBody);

        $responseData = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON Response: ' . json_last_error_msg());
        }

        if ($statusCode != 200) {
            throw new \Exception("HTTP Error {$statusCode}");
        }

        return $this->parseResponse($responseData);

    } catch (\Exception $e) {

        Log::error('Authorize.Net Error: '.$e->getMessage());

        return [
            'success' => false,
            'message' => $e->getMessage(),
            'transaction_id' => null,
        ];
    }
}

    /**
     * Parse API response (FIXED)
     */
    protected function parseResponse(array $response)
    {
        // Check for API level errors
        if (isset($response['messages']['resultCode']) && $response['messages']['resultCode'] === 'Error') {
            $errorText = $response['messages']['message'][0]['text'] ?? 'Unknown API error';
            return [
                'success' => false,
                'message' => 'API Error: ' . $errorText,
                'transaction_id' => null,
                'raw_response' => $response
            ];
        }

        // Check transaction response
        if (!isset($response['transactionResponse'])) {
            return [
                'success' => false,
                'message' => 'Invalid response format from payment gateway',
                'transaction_id' => null,
                'raw_response' => $response
            ];
        }

        $transactionResponse = $response['transactionResponse'];
        $responseCode = $transactionResponse['responseCode'] ?? '0';

        // Response code 1 = Approved, 2 = Declined, 3 = Error, 4 = Held for review
        if ($responseCode === '1') {
            return [
                'success' => true,
                'message' => $transactionResponse['messages'][0]['description'] ?? 'Transaction approved',
                'transaction_id' => $transactionResponse['transId'] ?? null,
                'auth_code' => $transactionResponse['authCode'] ?? null,
                'response_code' => $responseCode,
                'raw_response' => $transactionResponse
            ];
        } else {
            // Get error message
            $errorMessage = 'Transaction failed';
            if (isset($transactionResponse['errors'][0]['errorText'])) {
                $errorMessage = $transactionResponse['errors'][0]['errorText'];
            } elseif (isset($transactionResponse['messages'][0]['description'])) {
                $errorMessage = $transactionResponse['messages'][0]['description'];
            }
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'transaction_id' => $transactionResponse['transId'] ?? null,
                'error_code' => $transactionResponse['errors'][0]['errorCode'] ?? $responseCode,
                'response_code' => $responseCode,
                'raw_response' => $transactionResponse
            ];
        }
    }

    /**
     * Test API connection
     */
public function testConnection()
{
    try {

        $payload = [
            "authenticateTestRequest" => [
                "merchantAuthentication" => [
                    "name" => $this->apiLoginId,
                    "transactionKey" => $this->transactionKey
                ]
            ]
        ];

        $response = $this->client->post($this->endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => json_encode($payload),
            'http_errors' => false,
        ]);

        $responseBody = $response->getBody()->getContents();

        // Remove UTF-8 BOM if present
        $responseBody = preg_replace('/^\xEF\xBB\xBF/', '', $responseBody);

        Log::info('Authorize.Net Test Connection');
        Log::info($responseBody);

        $responseData = json_decode($responseBody, true);

        return [
            'connected' => isset($responseData['messages']['resultCode'])
                && $responseData['messages']['resultCode'] === 'Ok',

            'response' => $responseData,
            'status_code' => $response->getStatusCode(),
        ];

    } catch (\Exception $e) {

        Log::error('Authorize.Net Test Connection Error: '.$e->getMessage());

        return [
            'connected' => false,
            'response' => null,
            'status_code' => 500,
            'error' => $e->getMessage(),
        ];
    }
}

    /**
     * Get test credit card numbers for sandbox
     */
    public function getTestCards()
    {
        return [
            'visa' => '4007000000027',
            'mastercard' => '5424000000000015',
            'amex' => '370000000000002',
            'discover' => '6011000000000012',
            'visa_decline' => '4222222222222',
            'jcb' => '3088000000000017',
        ];
    }

    /**
     * Validate credit card using Luhn algorithm
     */
    public function validateCardNumber($cardNumber)
    {
        $cardNumber = str_replace(' ', '', $cardNumber);
        
        if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
            return false;
        }
        
        // Luhn algorithm
        $sum = 0;
        $alt = false;
        
        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $n = intval($cardNumber[$i]);
            
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            
            $sum += $n;
            $alt = !$alt;
        }
        
        return ($sum % 10 === 0);
    }
}