<?php

use App\Models\PaymentTransaction;
use App\Services\SadadSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


function makeWebhookHash(array $payload, string $secret = 'TEST_SECRET'): string
{
    $sig = new SadadSignatureService($secret);
    return strtolower($sig->generateRequestSignature($payload));
}

it('returns 200 and updates transaction for a valid webhook', function () {
    $txn = PaymentTransaction::factory()->create([
        'order_id' => 'ORD-WH-001',
        'amount'   => '50.00',
    ]);

    $payload = [
        'invoiceNumber'     => '',
        'isTestMode'        => 1,
        'merchantId'        => '7015085',
        'message'           => 'success',
        'transactionNumber' => 'SD2418209648273',
        'transactionStatus' => 3,
        'txnAmount'         => 50,
        'websiteRefNo'      => 'ORD-WH-001',
    ];
    $payload['checksumhash'] = makeWebhookHash(array_map('strval', $payload));

    config(['sadad.secret_key' => 'TEST_SECRET']);

    $this->postJson(route('payment.webhook'), $payload)
        ->assertStatus(200)
        ->assertJson(['status' => 'success']);

    expect($txn->fresh()->status)->toBe('successful');
    expect($txn->fresh()->signature_verified_webhook)->toBeTrue();
    expect($txn->fresh()->sadad_transaction_number)->toBe('SD2418209648273');
});

it('returns 200 but does NOT update when webhook checksum is invalid', function () {
    $txn = PaymentTransaction::factory()->create([
        'order_id' => 'ORD-WH-002',
        'status'   => 'pending',
    ]);

    $payload = [
        'transactionStatus' => 3,
        'transactionNumber' => 'SD9999999999999',
        'websiteRefNo'      => 'ORD-WH-002',
        'checksumhash'      => 'badhash00000000000000000000000000000000000000000000000000000000',
    ];

    config(['sadad.secret_key' => 'TEST_SECRET']);

    $this->postJson(route('payment.webhook'), $payload)
        ->assertStatus(200)
        ->assertJson(['status' => 'success']);

    // Status must not have changed
    expect($txn->fresh()->status)->toBe('pending');
});

it('ignores duplicate webhook for same transaction number (replay attack)', function () {
    $txn = PaymentTransaction::factory()->successful()->create([
        'order_id'                   => 'ORD-WH-003',
        'sadad_transaction_number'   => 'SD1111111111111',
        'signature_verified_webhook' => true,
        'status'                     => 'successful',
    ]);

    $payload = [
        'isTestMode'        => 1,
        'merchantId'        => '7015085',
        'message'           => 'success',
        'transactionNumber' => 'SD1111111111111', // same as already stored
        'transactionStatus' => 3,
        'txnAmount'         => 50,
        'websiteRefNo'      => 'ORD-WH-003',
    ];
    $payload['checksumhash'] = makeWebhookHash(array_map('strval', $payload));

    config(['sadad.secret_key' => 'TEST_SECRET']);

    $this->postJson(route('payment.webhook'), $payload)
        ->assertStatus(200)
        ->assertJson(['status' => 'success']);

    // Should remain unchanged
    expect($txn->fresh()->status)->toBe('successful');
});

it('sets status to failed when webhook transactionStatus is 2', function () {
    $txn = PaymentTransaction::factory()->create([
        'order_id' => 'ORD-WH-004',
        'amount'   => '200.00',
    ]);

    $payload = [
        'isTestMode'        => 1,
        'merchantId'        => '7015085',
        'message'           => 'failed',
        'transactionNumber' => 'SD5555555555555',
        'transactionStatus' => 2,
        'txnAmount'         => 200,
        'websiteRefNo'      => 'ORD-WH-004',
    ];
    $payload['checksumhash'] = makeWebhookHash(array_map('strval', $payload));

    config(['sadad.secret_key' => 'TEST_SECRET']);

    $this->postJson(route('payment.webhook'), $payload)
        ->assertStatus(200);

    expect($txn->fresh()->status)->toBe('failed');
});
