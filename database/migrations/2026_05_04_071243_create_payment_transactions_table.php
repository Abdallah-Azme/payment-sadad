<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Merchant-generated unique order reference (max 50 chars per SADAD docs)
            $table->string('order_id', 50)->unique();

            // SADAD transaction reference returned in callback/webhook
            $table->string('sadad_transaction_number')->nullable();

            $table->decimal('amount', 10, 2);
            $table->string('customer_name')->nullable();
            $table->string('customer_email');
            $table->string('customer_mobile');

            // Optional product line items (productdetail[])
            $table->json('product_detail')->nullable();

            // Transaction lifecycle status
            $table->enum('status', ['pending', 'in_progress', 'successful', 'failed'])->default('pending');

            // Raw payloads logged for audit, dispute handling, and reconciliation
            $table->json('raw_callback_payload')->nullable();
            $table->json('raw_webhook_payload')->nullable();

            // Track whether checksumhash was verified for each channel
            $table->boolean('signature_verified_callback')->default(false);
            $table->boolean('signature_verified_webhook')->default(false);

            // SADAD RESPCODE and RESPMSG (from callback)
            $table->string('resp_code')->nullable();
            $table->string('resp_msg')->nullable();

            // Whether this was a sandbox transaction (issandboxmode field from SADAD)
            $table->boolean('is_sandbox')->default(true);

            // The txnDate sent in the payment request
            $table->date('txn_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
