@extends('payment.layout')

@section('title', 'Payment Failed')
@section('meta_description', 'Your payment could not be completed.')

@section('content')
<div class="glass-card text-center">

    <div class="icon-danger">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6"  y1="6" x2="18" y2="18"/>
        </svg>
    </div>

    <div class="card-header" style="margin-bottom:24px;">
        <h1>Payment Failed</h1>
        <p>We were unable to process your payment. No charges have been made.</p>
    </div>

    <div class="alert alert--danger">
        ❌ {{ $transaction->resp_msg
                ? ucfirst(strtolower($transaction->resp_msg))
                : 'Your payment could not be completed. Please try again.' }}
    </div>

    <div class="detail-table mb-6">
        <div class="detail-row">
            <span class="detail-label">Order Reference</span>
            <span class="detail-value detail-value--mono">{{ $transaction->order_id }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Amount</span>
            <span class="detail-value">QAR {{ number_format($transaction->amount, 2) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value">{{ $transaction->created_at->format('d M Y, H:i') }}</span>
        </div>
    </div>

    <a href="{{ route('payment.checkout') }}" class="btn btn--primary">
        🔄 Try Again
    </a>

    <p class="mt-4" style="font-size:0.82rem; color:var(--clr-muted);">
        If the problem persists, please contact your bank or try a different payment method.
    </p>

</div>
@endsection
