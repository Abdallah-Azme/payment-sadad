<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitiatePaymentRequest;
use App\Models\PaymentTransaction;
use App\Services\SadadPaymentService;
use App\Services\SadadSignatureService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly SadadPaymentService $paymentService,
        private readonly SadadSignatureService $signatureService
    ) {}

    /**
     * Show the demo payment checkout form.
     */
    public function checkout(): View
    {
        return view('payment.checkout');
    }

    /**
     * Initiate a payment: validate, generate signed form, persist transaction,
     * then render the auto-submit redirect page.
     */
    public function initiate(InitiatePaymentRequest $request): View
    {
        $result = $this->paymentService->initiatePayment($request->validated());

        return view('payment.redirect', [
            'transaction'  => $result['transaction'],
            'formFields'   => $result['form_fields'],
            'checkoutUrl'  => $result['checkout_url'],
        ]);
    }

    /**
     * Receive the browser-based POST callback from SADAD after checkout completes.
     *
     * - Verifies the checksumhash
     * - Updates transaction status
     * - Redirects customer to appropriate result page
     */
    public function callback(Request $request): RedirectResponse
    {
        $payload = $request->all();

        $orderId      = $payload['ORDERID'] ?? $payload['website_ref_no'] ?? null;
        $receivedHash = $payload['checksumhash'] ?? null;
        $txnStatus    = (int) ($payload['transaction_status'] ?? 0);

        // Find the transaction
        $transaction = PaymentTransaction::where('order_id', $orderId)->first();

        if (! $transaction) {
            return redirect()->route('payment.checkout')
                ->with('error', 'Transaction not found. Please try again.');
        }

        // Verify the callback signature
        $isValid = $receivedHash
            ? $this->signatureService->verifyChecksum($payload, $receivedHash)
            : false;

        // Map SADAD numeric status to our enum
        $status = match ($txnStatus) {
            3       => 'successful',
            2       => 'failed',
            default => 'in_progress',
        };

        // Update transaction with callback data
        $transaction->update([
            'sadad_transaction_number'    => $payload['transaction_number'] ?? $transaction->sadad_transaction_number,
            'status'                      => $isValid ? $status : $transaction->status,
            'signature_verified_callback' => $isValid,
            'raw_callback_payload'        => $payload,
            'resp_code'                   => $payload['RESPCODE'] ?? null,
            'resp_msg'                    => $payload['RESPMSG'] ?? null,
            'is_sandbox'                  => ($payload['issandboxmode'] ?? '1') === '1',
        ]);

        // Redirect to the appropriate result page
        return match ($status) {
            'successful' => redirect()->route('payment.success', $transaction->order_id),
            'failed'     => redirect()->route('payment.failed', $transaction->order_id),
            default      => redirect()->route('payment.pending', $transaction->order_id),
        };
    }

    /**
     * Display the payment success page.
     */
    public function success(string $orderId): View
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();

        return view('payment.success', compact('transaction'));
    }

    /**
     * Display the payment failure page.
     */
    public function failed(string $orderId): View
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();

        return view('payment.failed', compact('transaction'));
    }

    /**
     * Display the in-progress / pending page.
     */
    public function pending(string $orderId): View
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();

        return view('payment.pending', compact('transaction'));
    }

    /**
     * Return JSON status of a transaction (for AJAX polling).
     */
    public function status(string $orderId): JsonResponse
    {
        $transaction = PaymentTransaction::where('order_id', $orderId)->firstOrFail();

        return response()->json([
            'order_id'                 => $transaction->order_id,
            'status'                   => $transaction->status,
            'sadad_transaction_number' => $transaction->sadad_transaction_number,
            'amount'                   => $transaction->amount,
            'resp_msg'                 => $transaction->resp_msg,
        ]);
    }
}
