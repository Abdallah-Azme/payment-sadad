<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\SadadSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly SadadSignatureService $signatureService
    ) {}

    /**
     * Handle a SADAD server-to-server webhook notification.
     *
     * SADAD sends a JSON POST immediately after a transaction is created,
     * independent of customer browser behaviour.
     *
     * IMPORTANT: This endpoint must ALWAYS return HTTP 200 with {"status":"success"},
     * even if verification fails or the payload is a replay — per SADAD docs.
     * Non-200 responses cause SADAD to retry delivery and increase replay risk.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        Log::info('[SADAD Webhook] Received', ['payload' => $payload]);

        // Always return 200 immediately — fulfil any extra processing below
        try {
            $this->processWebhook($payload);
        } catch (\Throwable $e) {
            Log::error('[SADAD Webhook] Processing error', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function processWebhook(array $payload): void
    {
        $receivedHash        = $payload['checksumhash'] ?? null;
        $transactionNumber   = $payload['transactionNumber'] ?? null;
        $websiteRefNo        = $payload['websiteRefNo'] ?? null;
        $transactionStatus   = (int) ($payload['transactionStatus'] ?? 0);

        // Verify checksum
        $isValid = $receivedHash
            ? $this->signatureService->verifyChecksum($payload, $receivedHash)
            : false;

        if (! $isValid) {
            Log::warning('[SADAD Webhook] Checksum verification failed', [
                'receivedHash' => $receivedHash,
            ]);

            return;
        }

        // Find transaction by merchant order reference
        $transaction = PaymentTransaction::where('order_id', $websiteRefNo)->first();

        if (! $transaction) {
            Log::warning('[SADAD Webhook] Transaction not found', ['websiteRefNo' => $websiteRefNo]);

            return;
        }

        // Idempotency guard — replay attack protection
        // If a valid webhookTransaction number is already stored, skip re-processing
        if (
            $transaction->sadad_transaction_number === $transactionNumber
            && $transaction->signature_verified_webhook
        ) {
            Log::info('[SADAD Webhook] Duplicate webhook ignored (idempotency)', [
                'transactionNumber' => $transactionNumber,
            ]);

            return;
        }

        $status = match ($transactionStatus) {
            3       => 'successful',
            2       => 'failed',
            default => 'in_progress',
        };

        $transaction->update([
            'sadad_transaction_number'  => $transactionNumber,
            'status'                    => $status,
            'signature_verified_webhook' => true,
            'raw_webhook_payload'        => $payload,
            'is_sandbox'                 => ($payload['isTestMode'] ?? 1) === 1,
        ]);

        Log::info('[SADAD Webhook] Transaction updated', [
            'order_id' => $transaction->order_id,
            'status'   => $status,
        ]);
    }
}
