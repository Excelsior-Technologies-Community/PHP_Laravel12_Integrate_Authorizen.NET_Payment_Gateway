<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'transaction_id',
        'authorization_code',
        'invoice_number',
        'customer_name',
        'amount',
        'card_last4',
        'payment_status',
        'error_message',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];
}