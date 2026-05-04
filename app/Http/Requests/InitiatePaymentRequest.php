<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|\Illuminate\Contracts\Validation\Rule>>
     */
    public function rules(): array
    {
        return [
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'customer_name'   => ['required', 'string', 'max:100'],
            'customer_email'  => ['required', 'email', 'max:150'],
            'customer_mobile' => ['required', 'string', 'max:20'],

            // Optional product details
            'product_detail'              => ['sometimes', 'array'],
            'product_detail.*.order_id'   => ['required_with:product_detail', 'string'],
            'product_detail.*.amount'     => ['required_with:product_detail', 'numeric', 'min:0.01'],
            'product_detail.*.quantity'   => ['required_with:product_detail', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'          => 'Please enter the payment amount.',
            'amount.min'               => 'Amount must be at least 0.01.',
            'customer_name.required'   => 'Please enter your full name.',
            'customer_email.required'  => 'Please enter your email address.',
            'customer_email.email'     => 'Please enter a valid email address.',
            'customer_mobile.required' => 'Please enter your mobile number with country code.',
        ];
    }
}
