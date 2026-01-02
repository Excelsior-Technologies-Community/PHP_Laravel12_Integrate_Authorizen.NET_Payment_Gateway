# PHP_Laravel12_Integrate_Authorizen.NET_Payment_Gateway

A complete implementation of the Authorize.Net payment gateway in **Laravel 12**, using **sandbox (test) credentials** for safe testing. This project demonstrates how to process credit card payments without relying on external SDKs, using direct API requests.

---

## Overview

This project shows how to integrate Authorize.Net into a Laravel 12 application with a clean service-based architecture. It supports full payment lifecycle operations such as charge, authorize, capture, refund, and void.

The implementation is suitable for:

* Learning payment gateway integration
* College or MCA projects
* Real-world Laravel payment modules

---

## Features

* Complete Authorize.Net API integration
* Sandbox testing with test credentials
* Credit card operations:

  * Charge
  * Authorize only
  * Capture authorized payment
  * Refund transaction
  * Void transaction
* Responsive payment form
* Payment history tracking
* Comprehensive error handling and logging
* No external SDK dependency
* Clean service-based architecture

---

## Requirements

* PHP 8.1 or higher
* Laravel 12.x
* Composer
* Guzzle HTTP Client
* MySQL or compatible database

---

## Installation

### Step 1: Clone the Repository

```
git clone https://github.com/your-username/laravel-authnet.git
cd laravel-authnet
```

### Step 2: Install Dependencies

```
composer install
```

### Step 3: Environment Setup

```
cp .env.example .env
php artisan key:generate
```

### Step 4: Configure Authorize.Net Credentials

Update the `.env` file:

```
AUTHORIZE_NET_API_LOGIN_ID=5KP3u95bQpv
AUTHORIZE_NET_TRANSACTION_KEY=346HZ32z3fP4hTG2
AUTHORIZE_NET_SIGNATURE_KEY=Simon
AUTHORIZE_NET_MODE=sandbox

AUTHORIZE_NET_SANDBOX_URL=https://apitest.authorize.net/xml/v1/request.api
AUTHORIZE_NET_PRODUCTION_URL=https://api.authorize.net/xml/v1/request.api
```

These are **official sandbox credentials** provided by Authorize.Net for testing.

---

## Project Structure

```
app/
├── Services/
│   └── AuthorizeNetService.php
├── Http/Controllers/
│   └── PaymentController.php
├── Providers/
│   └── AuthorizeNetServiceProvider.php

config/
└── authorize.php

resources/views/payment/
├── form.blade.php
├── success.blade.php
└── history.blade.php

routes/
└── web.php
```

---

## Running the Application

```
php artisan serve
```

Open the browser:

```
http://localhost:8000/payment
```

---

## Test Credit Cards (Sandbox)

Use the following card numbers for testing:

* Visa: `4007000000027`
* MasterCard: `5424000000000015`
* American Express: `370000000000002`
* Discover: `6011000000000012`
* Visa Decline Test: `4222222222222`

Expiration Date: Any future date (YYYY-MM)

CVV: Any 3 or 4 digits

---

## Routes & Endpoints

| Method | Endpoint         | Description          |
| ------ | ---------------- | -------------------- |
| GET    | /payment         | Show payment form    |
| POST   | /payment/process | Process payment      |
| GET    | /payment/success | Payment success page |
| GET    | /payment/history | View payment history |

---

## Service Methods

### Charge Credit Card

```
$service->chargeCreditCard([
    'amount' => '10.00',
    'card_number' => '4007000000027',
    'exp_date' => '2025-12',
    'cvv' => '123',
]);
```

### Authorize Only

```
$service->authorizeCreditCard([...]);
```

### Capture Payment

```
$service->captureAuthorizedAmount($transactionId, $amount);
```

### Refund Transaction

```
$service->refundTransaction([
    'transaction_id' => '123456',
    'amount' => '10.00',
    'last_4_digits' => '0027',
    'exp_date' => '2025-12',
]);
```

### Void Transaction

```
$service->voidTransaction($transactionId);
```

---

## Configuration File

`config/authorize.php`

```
return [
    'api_login_id' => env('AUTHORIZE_NET_API_LOGIN_ID'),
    'transaction_key' => env('AUTHORIZE_NET_TRANSACTION_KEY'),
    'signature_key' => env('AUTHORIZE_NET_SIGNATURE_KEY'),
    'mode' => env('AUTHORIZE_NET_MODE', 'sandbox'),

    'urls' => [
        'sandbox' => env('AUTHORIZE_NET_SANDBOX_URL'),
        'production' => env('AUTHORIZE_NET_PRODUCTION_URL'),
    ],

    'timeout' => 30,
    'verify_ssl' => true,
];
```

---

## Error Handling

* Invalid card details
* Transaction declined
* API authentication failures
* Network/API connection issues

All errors are logged to:

```
storage/logs/laravel.log
```

---

## Testing

Run all tests:

```
php artisan test
```

Run specific test:

```
php artisan test tests/Unit/AuthorizeNetServiceTest.php
```
---
## Screenshot
<img width="1127" height="955" alt="image" src="https://github.com/user-attachments/assets/8c15e1b3-9e18-4a5d-be13-57711e853e0e" />
<img width="1599" height="508" alt="image" src="https://github.com/user-attachments/assets/f9762ade-87d4-4083-aa5a-712cf92cb075" />


---

## Production Deployment Checklist

* Replace sandbox credentials with live credentials
* Set `AUTHORIZE_NET_MODE=production`
* Enable HTTPS
* Enable SSL verification
* Add request rate limiting
* Validate all inputs
* Store transaction logs securely

---

## Security Best Practices

* Never commit API credentials
* Use environment variables
* Use HTTPS only
* Follow PCI DSS guidelines
* Mask card numbers in logs
* Implement fraud detection

---

## Troubleshooting

### Invalid Credentials

* Verify API Login ID and Transaction Key
* Check sandbox vs production mode

### Transaction Declined

* Use valid test card numbers
* Check expiration format (YYYY-MM)

### Connection Errors

* Verify API URL
* Check firewall or proxy settings

---

## Sample API Response

### Success

```
{
  "success": true,
  "message": "Transaction successful",
  "transaction_id": "2230582181",
  "auth_code": "E4W2H9"
}
```

### Error

```
{
  "success": false,
  "message": "Invalid credit card number",
  "error_code": "6"
}
```

---

## Roadmap

* Recurring payments
* Webhook support
* Admin dashboard
* Export transactions
* Multi-currency support
* Customer profiles

---
