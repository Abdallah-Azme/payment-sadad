<?php

use App\Services\SadadSignatureService;

// Known test vector from the SADAD docs (PHP code sample)
$secretKey = 'YOUR_TEST_SECRET_KEY';
$params = [
    'CALLBACK_URL' => 'https://yoursite.com/callback',
    'MOBILE_NO'    => '97412345678',
    'ORDER_ID'     => 'ORD-20251216-001',
    'TXN_AMOUNT'   => '150.00',
    'WEBSITE'      => 'MYSHOP',
    'email'        => 'user@example.com',
    'merchant_id'  => '123456',
    'txnDate'      => '2025-12-16',
];

it('generates a SHA256 signature sorted alphabetically by key', function () use ($secretKey, $params) {
    $service = new SadadSignatureService($secretKey);
    $hash    = $service->generateRequestSignature($params);

    // Must be uppercase
    expect($hash)->toBe(strtoupper($hash));

    // Must be a valid 64-char hex string
    expect($hash)->toMatch('/^[A-F0-9]{64}$/');
});

it('produces the same signature regardless of input order', function () use ($secretKey, $params) {
    $service  = new SadadSignatureService($secretKey);
    $shuffled = array_reverse($params, true);

    expect($service->generateRequestSignature($params))
        ->toBe($service->generateRequestSignature($shuffled));
});

it('verifies a valid checksumhash', function () use ($secretKey, $params) {
    $service = new SadadSignatureService($secretKey);

    // Generate hash and include it in the payload as checksumhash
    $hash    = strtolower($service->generateRequestSignature($params));
    $payload = array_merge($params, ['checksumhash' => $hash]);

    expect($service->verifyChecksum($payload, $hash))->toBeTrue();
});

it('rejects a tampered checksumhash', function () use ($secretKey, $params) {
    $service = new SadadSignatureService($secretKey);
    $payload = array_merge($params, ['checksumhash' => 'AABBCCDD00112233445566778899AABBCCDD00112233445566778899AABBCCDD']);

    expect($service->verifyChecksum($payload, 'AABBCCDD00112233445566778899AABBCCDD00112233445566778899AABBCCDD'))->toBeFalse();
});

it('produces different signatures for different secret keys', function () use ($params) {
    $s1 = new SadadSignatureService('KEY_ONE');
    $s2 = new SadadSignatureService('KEY_TWO');

    expect($s1->generateRequestSignature($params))
        ->not->toBe($s2->generateRequestSignature($params));
});
