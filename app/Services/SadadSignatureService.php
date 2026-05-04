<?php

namespace App\Services;

/**
 * Handles SHA256 signature generation and verification for SADAD Web Checkout 2.1.
 *
 * All cryptographic operations must be performed server-side only.
 * Never expose the secret key or this logic to the frontend.
 *
 * Algorithm (per SADAD docs):
 *  1. Sort parameters alphabetically by key
 *  2. Prefix string with Secret Key
 *  3. Concatenate parameter VALUES only (no keys, no separators)
 *  4. SHA256 hash → uppercase
 */
class SadadSignatureService
{
    public function __construct(
        private string $secretKey
    ) {
        $this->secretKey = trim($secretKey);
    }

    /**
     * Generate a SADAD request signature from an array of payment parameters.
     *
     * Exclude the `signature` key itself before calling this method.
     *
     * @param  array<string, string>  $params  All mandatory request params (no `signature` key)
     */
    public function generateRequestSignature(array $params): string
    {
        ksort($params, SORT_STRING);

        $string = $this->secretKey;
        $appendLog = [];

        foreach ($params as $key => $value) {
            $string .= $value;
            $appendLog[] = "[{$key}] = \"{$value}\"";
        }

        \Illuminate\Support\Facades\Log::debug('SADAD SHA256 Input', [
            'sorted_keys_order' => array_keys($params),
            'values_appended'   => $appendLog,
            'secret_key'        => substr($this->secretKey, 0, 4) . str_repeat('*', max(0, strlen($this->secretKey) - 4)),
            'full_pre_hash_string' => $string,
        ]);

        return strtoupper(hash('sha256', $string));
    }

    /**
     * Verify a checksumhash received in a SADAD callback or webhook payload.
     *
     * @param  array<string, mixed>  $params        Full payload including checksumhash
     * @param  string                $receivedHash  The checksumhash value from the payload
     */
    public function verifyChecksum(array $params, string $receivedHash): bool
    {
        // Remove checksumhash from the params before hashing
        unset($params['checksumhash']);

        // Cast all values to string (webhook delivers numeric types in JSON)
        $stringParams = array_map('strval', $params);

        $generatedHash = $this->generateRequestSignature($stringParams);

        return strtolower($generatedHash) === strtolower($receivedHash);
    }
}
