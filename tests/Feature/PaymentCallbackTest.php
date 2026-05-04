<?php

use App\Models\PaymentTransaction;
use App\Services\SadadSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper: generate a valid checksumhash for a callback payload using the test secret key.
 */
function makeCallbackHash(array $payload, string $secret = 'TEST_SECRET'): string
{
    $sig = new SadadSignatureService($secret);
    return strtolower($sig->generateRequestSignature($payload));
}

it('redirects to success page when callback has valid hash and status 3', function () {
    // Create a pending transaction
    $txn = PaymentTransaction::factory()->create([
        'order_id' => 'ORD-TEST-001',
        'amount'   => '150.00',
    ]);

    $payload = [
        'MID'                => '7015085',
        'ORDERID'            => 'ORD-TEST-001',
        'RESPCODE'           => '3',
        'RESPMSG'            => 'Txn Success',
        'STATUS'             => 'TXN_SUCCESS',
        'TXNAMOUNT'          => '150.00',
        'issandboxmode'      => '1',
        'transaction_number' => 'SD1234567890123',
        'transaction_status' => '3',
        'website_ref_no'     => 'ORD-TEST-001',
    ];
    $payload['checksumhash'] = makeCallbackHash($payload);

    // Override config to use the same test secret key
    config(['sadad.secret_key' => 'TEST_SECRET']);

    $this->post(route('payment.callback'), $payload)
        ->assertRedirect(route('payment.success', 'ORD-TEST-001'));

    expect($txn->fresh()->status)->toBe('successful');
    expect($txn->fresh()->signature_verified_callback)->toBeTrue();
});

it('redirects to failed page when callback has valid hash and status 2', function () {
    $txn = PaymentTransaction::factory()->create([
        'order_id' => 'ORD-TEST-002',
        'amount'   => '75.00',
    ]);

    $payload = [
        'MID'                => '7015085',
        'ORDERID'            => 'ORD-TEST-002',
        'RESPCODE'           => '2',
        'RESPMSG'            => 'Transaction declined',
        'STATUS'             => 'TXN_FAILURE',
        'TXNAMOUNT'          => '75.00',
        'issandboxmode'      => '1',
        'transaction_number' => 'SD9876543210987',
        'transaction_status' => '2',
        'website_ref_no'     => 'ORD-TEST-002',
    ];
    $payload['checksumhash'] = makeCallbackHash($payload);

    config(['sadad.secret_key' => 'TEST_SECRET']);

    $this->post(route('payment.callback'), $payload)
        ->assertRedirect(route('payment.failed', 'ORD-TEST-002'));

    expect($txn->fresh()->status)->toBe('failed');
});

it('does not update status when checksumhash is invalid', function () {
    $txn = PaymentTransaction::factory()->create([
        'order_id' => 'ORD-TEST-003',
        'status'   => 'pending',
    ]);

    $payload = [
        'ORDERID'            => 'ORD-TEST-003',
        'transaction_status' => '3',
        'website_ref_no'     => 'ORD-TEST-003',
        'checksumhash'       => 'INVALIDHASH000000000000000000000000000000000000000000000000000000',
    ];

    config(['sadad.secret_key' => 'TEST_SECRET']);

    $this->post(route('payment.callback'), $payload);

    // Status must NOT have changed
    expect($txn->fresh()->status)->toBe('pending');
    expect($txn->fresh()->signature_verified_callback)->toBeFalse();
});

it('redirects to pending page when callback status is 1', function () {
    $txn = PaymentTransaction::factory()->create([
        'order_id' => 'ORD-TEST-004',
    ]);

    $payload = [
        'ORDERID'            => 'ORD-TEST-004',
        'transaction_status' => '1',
        'website_ref_no'     => 'ORD-TEST-004',
        'TXNAMOUNT'          => '50.00',
        'issandboxmode'      => '1',
        'transaction_number' => 'SD0000000000001',
        'RESPCODE'           => '1',
        'RESPMSG'            => 'Pending',
        'MID'                => '7015085',
        'STATUS'             => 'PENDING',
    ];
    $payload['checksumhash'] = makeCallbackHash($payload);

    config(['sadad.secret_key' => 'TEST_SECRET']);

    $this->post(route('payment.callback'), $payload)
        ->assertRedirect(route('payment.pending', 'ORD-TEST-004'));

    expect($txn->fresh()->status)->toBe('in_progress');
});
