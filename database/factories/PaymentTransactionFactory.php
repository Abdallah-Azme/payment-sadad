<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'order_id'               => 'ORD-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'sadad_transaction_number' => null,
            'amount'                 => fake()->randomFloat(2, 10, 5000),
            'customer_name'          => fake()->name(),
            'customer_email'         => fake()->safeEmail(),
            'customer_mobile'        => '974'.fake()->numerify('########'),
            'product_detail'         => null,
            'status'                 => 'pending',
            'raw_callback_payload'   => null,
            'raw_webhook_payload'    => null,
            'signature_verified_callback' => false,
            'signature_verified_webhook'  => false,
            'resp_code'              => null,
            'resp_msg'               => null,
            'is_sandbox'             => true,
            'txn_date'               => now()->toDateString(),
        ];
    }

    /** Simulate a successful transaction. */
    public function successful(): static
    {
        return $this->state(fn () => [
            'status'                      => 'successful',
            'sadad_transaction_number'    => 'SD'.fake()->numerify('#############'),
            'signature_verified_callback' => true,
            'resp_code'                   => '3',
            'resp_msg'                    => 'Txn Success',
        ]);
    }

    /** Simulate a failed transaction. */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status'   => 'failed',
            'resp_code' => '2',
            'resp_msg'  => 'Transaction declined',
        ]);
    }

    /** Simulate an in-progress transaction. */
    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status'                   => 'in_progress',
            'sadad_transaction_number' => 'SD'.fake()->numerify('#############'),
        ]);
    }
}
