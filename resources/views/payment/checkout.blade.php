@extends('payment.layout')

@section('title', 'Secure Payment')
@section('meta_description', 'Complete your secure payment via SADAD Web Checkout.')

@section('content')
<div class="glass-card">

    <div class="card-header">
        <div class="card-header__icon card-header__icon--primary">💳</div>
        <h1>Secure Payment</h1>
        <p>Your payment is processed securely by SADAD. No card details are stored on our servers.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert--danger" role="alert">
            <span>⚠️</span>
            <ul style="list-style:none; padding:0; margin:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="payment-form" action="{{ route('payment.initiate') }}" method="POST" novalidate>
        @csrf

        {{-- Amount --}}
        <div class="form-group">
            <label class="form-label" for="amount">Payment Amount</label>
            <div class="amount-display">
                <span class="amount-currency">QAR</span>
                <input
                    id="amount"
                    type="number"
                    name="amount"
                    class="form-input form-input--amount @error('amount') is-error @enderror"
                    placeholder="0.00"
                    step="0.01"
                    min="0.01"
                    value="{{ old('amount') }}"
                    required
                    aria-label="Payment amount in Qatari Riyals"
                >
            </div>
            @error('amount') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        {{-- Customer Name --}}
        <div class="form-group">
            <label class="form-label" for="customer_name">Full Name</label>
            <input
                id="customer_name"
                type="text"
                name="customer_name"
                class="form-input @error('customer_name') is-error @enderror"
                placeholder="John Doe"
                value="{{ old('customer_name') }}"
                required
            >
            @error('customer_name') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div class="form-group">
            <label class="form-label" for="customer_email">Email Address</label>
            <input
                id="customer_email"
                type="email"
                name="customer_email"
                class="form-input @error('customer_email') is-error @enderror"
                placeholder="you@example.com"
                value="{{ old('customer_email') }}"
                required
            >
            @error('customer_email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        {{-- Mobile --}}
        <div class="form-group">
            <label class="form-label" for="customer_mobile">Mobile (with country code)</label>
            <input
                id="customer_mobile"
                type="tel"
                name="customer_mobile"
                class="form-input @error('customer_mobile') is-error @enderror"
                placeholder="97412345678"
                value="{{ old('customer_mobile') }}"
                required
            >
            @error('customer_mobile') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <button id="pay-btn" type="submit" class="btn btn--primary mt-6">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <span class="btn-text">Pay Now via SADAD</span>
        </button>

    </form>

    <div class="divider">accepted payment methods</div>

    <div class="payment-methods">
        <span class="payment-method-badge">💳 Mastercard</span>
        <span class="payment-method-badge">💳 Visa</span>
        <span class="payment-method-badge">🍎 Apple Pay</span>
        <span class="payment-method-badge">🤖 Google Pay</span>
        <span class="payment-method-badge">🏦 SADAD</span>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.getElementById('payment-form').addEventListener('submit', function () {
        const btn = document.getElementById('pay-btn');
        btn.classList.add('is-loading');
        btn.querySelector('.btn-text').textContent = 'Redirecting to SADAD…';
    });
</script>
@endpush
