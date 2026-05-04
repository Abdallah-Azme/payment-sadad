<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Str;

/**
 * Orchestrates SADAD Web Checkout 2.1 payment initiation.
 *
 * This service is responsible for:
 *  - Generating unique order IDs
 *  - Building the signed form fields for SADAD
 *  - Persisting the PaymentTransaction record
 */
class SadadPaymentService
{
    public function __construct(
        private readonly SadadSignatureService $signatureService
    ) {}

    /**
     * Initiate a payment with SADAD.
     *
     * Creates a PaymentTransaction record and returns all form fields
     * (including the server-generated signature) ready for hidden form submission.
     *
     * @param  array{
     *     amount: string|float,
     *     customer_name: string,
     *     customer_email: string,
     *     customer_mobile: string,
     *     product_detail?: array<int, array{order_id: string, amount: string, quantity: int}>|null
     * }  $orderData
     *
     * @return array{transaction: PaymentTransaction, form_fields: array<string, string>}
     */
    public function initiatePayment(array $orderData): array
    {
        $orderId = $this->generateOrderId();
        $txnDate = now()->format('Y-m-d H:i:s');
        $amount  = number_format((float) $orderData['amount'], 2, '.', '');

        $callbackUrl = config('sadad.callback_url');
        $merchantId  = config('sadad.merchant_id');
        $website     = config('sadad.website');

        // Mandatory parameters — these are the exact fields used for signature generation
        $signableParams = [
            'CALLBACK_URL' => $callbackUrl,
            'MOBILE_NO'    => $orderData['customer_mobile'],
            'ORDER_ID'     => $orderId,
            'TXN_AMOUNT'   => $amount,
            'WEBSITE'      => $website,
            'email'        => $orderData['customer_email'],
            'merchant_id'  => $merchantId,
            'txnDate'      => $txnDate,
        ];

        $signature = $this->signatureService->generateRequestSignature($signableParams);

        \Illuminate\Support\Facades\Log::info('SADAD Signature Debug', [
            'signable_params' => $signableParams,
            'signature'       => $signature,
            'secret_key_len'  => strlen(config('sadad.secret_key')),
        ]);

        // Persist the transaction as pending before redirecting to SADAD
        $transaction = PaymentTransaction::create([
            'order_id'        => $orderId,
            'amount'          => $amount,
            'customer_name'   => $orderData['customer_name'] ?? null,
            'customer_email'  => $orderData['customer_email'],
            'customer_mobile' => $orderData['customer_mobile'],
            'product_detail'  => $orderData['product_detail'] ?? null,
            'status'          => 'pending',
            'is_sandbox'      => config('sadad.environment') !== 'live',
            'txn_date'        => now()->toDateString(),
        ]);

        // All form fields to POST to SADAD (signature included)
        $formFields = array_merge($signableParams, [
            'signature'                  => $signature,
            'productdetail[0][order_id]' => $orderId,
            'productdetail[0][amount]'   => $amount,
            'productdetail[0][quantity]' => 1,
        ]);

        return [
            'transaction' => $transaction,
            'form_fields' => $formFields,
            'checkout_url' => config('sadad.checkout_url'),
        ];
    }

    /**
     * Generate a unique merchant order ID.
     * Format: ORD-YYYYMMDD-{random 8 hex chars} — stays under 50 chars.
     */
    private function generateOrderId(): string
    {
        return 'ORD' . now()->format('Ymd') . strtoupper(Str::random(8));
    }
}
