<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'order_id',
        'sadad_transaction_number',
        'amount',
        'customer_name',
        'customer_email',
        'customer_mobile',
        'product_detail',
        'status',
        'raw_callback_payload',
        'raw_webhook_payload',
        'signature_verified_callback',
        'signature_verified_webhook',
        'resp_code',
        'resp_msg',
        'is_sandbox',
        'txn_date',
    ];

    protected function casts(): array
    {
        return [
            'amount'                      => 'decimal:2',
            'product_detail'              => 'array',
            'raw_callback_payload'        => 'array',
            'raw_webhook_payload'         => 'array',
            'signature_verified_callback' => 'boolean',
            'signature_verified_webhook'  => 'boolean',
            'is_sandbox'                  => 'boolean',
            'txn_date'                    => 'date',
        ];
    }

    /**
     * Determine if this transaction has been fully confirmed as successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'successful';
    }

    /**
     * Determine if this transaction has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Determine if this transaction is still in progress / pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }
}
