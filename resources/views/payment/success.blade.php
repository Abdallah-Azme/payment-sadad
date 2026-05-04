@extends('payment.layout')

@section('title', 'Payment Successful')
@section('meta_description', 'Your payment was completed successfully.')

@section('content')
<div class="glass-card text-center">

    <div class="icon-success">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
    </div>

    <div class="card-header" style="margin-bottom:24px;">
        <h1>Payment Successful! 🎉</h1>
        <p>Your transaction has been confirmed. Thank you for your payment.</p>
    </div>

    <div class="alert alert--success">
        ✅ Your order is confirmed and will be processed shortly.
    </div>

    <div class="detail-table mb-6">
        <div class="detail-row">
            <span class="detail-label">Order Reference</span>
            <span class="detail-value detail-value--mono">{{ $transaction->order_id }}</span>
        </div>
        @if ($transaction->sadad_transaction_number)
        <div class="detail-row">
            <span class="detail-label">SADAD Ref No.</span>
            <span class="detail-value detail-value--mono">{{ $transaction->sadad_transaction_number }}</span>
        </div>
        @endif
        <div class="detail-row">
            <span class="detail-label">Amount Paid</span>
            <span class="detail-value" style="color: var(--clr-success); font-size:1.1rem;">QAR {{ number_format($transaction->amount, 2) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value">{{ $transaction->created_at->format('d M Y, H:i') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Environment</span>
            <span class="detail-value">{{ $transaction->is_sandbox ? '🧪 Sandbox' : '🟢 Live' }}</span>
        </div>
    </div>

    <a href="{{ route('payment.checkout') }}" class="btn btn--success">
        Make Another Payment
    </a>

</div>
@endsection

@push('scripts')
<script>
    // Confetti micro-animation
    const colors = ['#6366f1','#8b5cf6','#22c55e','#f59e0b','#06b6d4','#ec4899'];
    for (let i = 0; i < 60; i++) {
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        el.style.cssText = `
            left: ${Math.random() * 100}vw;
            top: -10px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            animation-delay: ${Math.random() * 1.5}s;
            animation-duration: ${2 + Math.random() * 2}s;
            transform: rotate(${Math.random() * 360}deg);
            width: ${6 + Math.random() * 10}px;
            height: ${6 + Math.random() * 10}px;
        `;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }
</script>
@endpush
