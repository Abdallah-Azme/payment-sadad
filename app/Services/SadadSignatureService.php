<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Handles checksum generation and verification for SADAD Web Checkout.
 *
 * The actual SADAD algorithm (as used in production):
 *  1. Build a data array with postData + secretKey
 *  2. JSON encode the data
 *  3. Generate a random 4-char salt
 *  4. SHA256 hash: json_string + "|" + salt
 *  5. Append salt to hash
 *  6. AES-128-CBC encrypt the result using (secretKey + merchantId) as key
 */
class SadadSignatureService
{
    public function __construct(
        private string $secretKey,
        private string $merchantId,
    ) {
        $this->secretKey = $secretKey;
        $this->merchantId = $merchantId;
    }

    /**
     * Generate a checksumhash for a SADAD payment request.
     *
     * @param  array<string, mixed>  $params  All request params (no checksumhash key)
     */
    public function generateChecksumHash(array $params): string
    {
        $checksumData = [
            'postData'  => $params,
            'secretKey' => (string) $this->secretKey,
        ];

        $key = $this->secretKey . $this->merchantId;
        $jsonStr = json_encode($checksumData);

        Log::debug('SADAD Checksum Generation', [
            'json_payload' => $jsonStr,
            'encryption_key_length' => strlen($key),
        ]);

        return $this->getChecksumFromString($jsonStr, $key);
    }

    /**
     * Verify a checksumhash received in a SADAD callback or webhook payload.
     *
     * @param  array<string, mixed>  $params        Full payload including checksumhash
     * @param  string                $receivedHash  The checksumhash value from the payload
     */
    public function verifyChecksum(array $params, string $receivedHash): bool
    {
        $postData = $params;
        unset($postData['checksumhash']);

        $dataToVerify = [
            'postData'  => $postData,
            'secretKey' => $this->secretKey,
        ];

        $key = $this->secretKey . $this->merchantId;
        $jsonStr = json_encode($dataToVerify);

        return $this->verifychecksumFromStr($jsonStr, $key, $receivedHash) === 'TRUE';
    }

    /**
     * Generate checksum: SHA256(json + "|" + salt) → append salt → AES encrypt.
     */
    protected function getChecksumFromString(string $str, string $key): string
    {
        $salt = $this->generateSalt(4);
        $finalString = $str . '|' . $salt;
        $hash = hash('sha256', $finalString);
        $hashString = $hash . $salt;

        return $this->encrypt($hashString, $key);
    }

    /**
     * Verify checksum: AES decrypt → extract salt → recompute SHA256 → compare.
     */
    public function verifychecksumFromStr(string $str, string $key, string $checksumvalue): string
    {
        $sadadHash = $this->decrypt($checksumvalue, $key);
        if (! $sadadHash) {
            return 'FALSE';
        }

        $salt = substr($sadadHash, -4);
        $finalString = $str . '|' . $salt;
        $websiteHash = hash('sha256', $finalString);
        $websiteHash .= $salt;

        return ($websiteHash == $sadadHash) ? 'TRUE' : 'FALSE';
    }

    /**
     * Generate a random salt string.
     */
    public function generateSalt(int $length): string
    {
        $random = '';
        srand((int) ((float) microtime() * 1000000));
        $data = 'AbcDE123IJKLMN67QRSTUVWXYZ';
        $data .= 'aBCdefghijklmn123opq45rs67tuv89wxyz';
        $data .= '0FGH45OP89';

        for ($i = 0; $i < $length; $i++) {
            $random .= substr($data, (rand() % (strlen($data))), 1);
        }

        return $random;
    }

    /**
     * AES-128-CBC encrypt.
     */
    public function encrypt(string $input, string $ky): string
    {
        $ky = html_entity_decode($ky);
        $iv = '@@@@&&&&####$$$$';

        return openssl_encrypt($input, 'AES-128-CBC', $ky, 0, $iv);
    }

    /**
     * AES-128-CBC decrypt.
     */
    public function decrypt(string $crypt, string $ky): string|false
    {
        $ky = html_entity_decode($ky);
        $iv = '@@@@&&&&####$$$$';

        return openssl_decrypt($crypt, 'AES-128-CBC', $ky, 0, $iv);
    }
}
