<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'transaction_id',
        'authorization_code',
        'invoice_number',
        'payment_method',
        'customer_name',
        'customer_email',
        'amount',
        'refunded_amount',
        'card_last4',
        'card_brand',
        'bank_name',
        'account_type',
        'account_last4',
        'customer_profile_id',
        'payment_profile_id',
        'payment_status',
        'refund_status',
        'error_message',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
    ];

    /**
     * Refunds relationship
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class, 'payment_id')->latest();
    }

    /**
     * Remaining amount available for refund
     */
    public function getRemainingRefundableAmountAttribute(): float
    {
        return max(0, round((float) $this->amount - (float) $this->refunded_amount, 2));
    }

    /**
     * Check if payment can be refunded
     */
    public function isRefundable(): bool
    {
        return $this->payment_status === 'success'
            && !in_array($this->refund_status, ['full', 'voided'])
            && $this->remaining_refundable_amount > 0;
    }

    /**
     * Check if payment can be voided (e.g. today or unsettled)
     */
    public function isVoidable(): bool
    {
        return $this->payment_status === 'success'
            && $this->refund_status === 'none'
            && (float) $this->refunded_amount === 0.0;
    }

    /**
     * Check if payment was made via eCheck / ACH
     */
    public function isEcheck(): bool
    {
        return $this->payment_method === 'echeck';
    }
}