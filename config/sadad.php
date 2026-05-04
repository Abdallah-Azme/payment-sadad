<?php

return [
    /*
     * Your SADAD Merchant ID (MID), found in the SADAD Merchant Panel.
     */
    'merchant_id' => env('SADAD_MERCHANT_ID'),

    /*
     * The active secret key is resolved automatically based on SADAD_ENVIRONMENT.
     * Use "test" for sandbox, "live" for production.
     */
    'secret_key' => env('SADAD_ENVIRONMENT', 'test') === 'live'
        ? env('SADAD_SECRET_KEY_LIVE')
        : env('SADAD_SECRET_KEY_TEST'),

    /*
     * Environment mode: "test" or "live".
     * The SADAD checkout URL is the same for both; the key determines the mode.
     */
    'environment' => env('SADAD_ENVIRONMENT', 'test'),

    /*
     * Merchant website identifier as registered in the SADAD Merchant Panel.
     */
    'website' => env('SADAD_WEBSITE', 'MYSHOP'),

    /*
     * The SADAD Web Checkout endpoint (same for test and live).
     */
    'checkout_url' => env('SADAD_CHECKOUT_URL', 'https://sadadqa.com/webpurchase'),

    /*
     * The publicly accessible HTTPS URL SADAD will POST the transaction result to.
     */
    'callback_url' => env('SADAD_CALLBACK_URL'),

    /*
     * The server-to-server webhook URL configured in the SADAD Merchant Panel.
     */
    'webhook_url' => env('SADAD_WEBHOOK_URL'),
];
