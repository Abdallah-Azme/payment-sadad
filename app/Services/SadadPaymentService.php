<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates SADAD Web Checkout payment initiation.
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
     * (including the server-generated checksumhash) ready for hidden form submission.
     *
     * @param  array{
     *     amount: string|float,
     *     customer_name: string,
     *     customer_email: string,
     *     customer_mobile: string,
     *     product_detail?: array<int, array{order_id: string, amount: string, quantity: int}>|null
     * }  $orderData
     *
     * @return array{transaction: PaymentTransaction, form_fields: array<string, mixed>, checkout_url: string}
     */
    public function initiatePayment(array $orderData): array
    {
        $orderId = $this->generateOrderId();
        $txnDate = date('Y-m-d H:i:s');
        $amount  = number_format((float) $orderData['amount'], 2, '.', '');

        $callbackUrl = config('sadad.callback_url');
        $merchantId  = (string) config('sadad.merchant_id');
        $website     = (string) config('sadad.website');

        // Product details (required by SADAD)
        $productDetail = $orderData['product_detail'] ?? [
            [
                'order_id' => $orderId,
                'amount'   => (float) $amount,
                'quantity' => 1,
            ],
        ];

        // Format productdetail items (trim values as per working implementation)
        $formattedProductDetail = [];
        foreach ($productDetail as $i => $item) {
            foreach ($item as $key => $value) {
                $formattedProductDetail[$i][$key] = trim((string) $value);
            }
        }

        // Build params matching the working carwash implementation
        $params = [
            'merchant_id'  => $merchantId,
            'ORDER_ID'     => (string) $orderId,
            'WEBSITE'      => $website,
            'TXN_AMOUNT'   => $amount,
            'CUST_ID'      => (string) $orderData['customer_email'],
            'EMAIL'        => (string) $orderData['customer_email'],
            'MOBILE_NO'    => (string) $orderData['customer_mobile'],
            'SADAD_WEBCHECKOUT_PAGE_LANGUAGE' => 'ENG',
            'CALLBACK_URL' => (string) $callbackUrl,
            'txnDate'      => $txnDate,
            'productdetail' => $formattedProductDetail,
        ];

        // Generate checksumhash using the SADAD encryption algorithm
        $checksumhash = $this->signatureService->generateChecksumHash($params);
        $params['checksumhash'] = $checksumhash;

        Log::info('SADAD Payment Request', [
            'order_id'    => $orderId,
            'amount'      => $amount,
            'checkout_url' => config('sadad.checkout_url'),
            'checksumhash' => $checksumhash,
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

        return [
            'transaction'  => $transaction,
            'form_fields'  => $params,
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
