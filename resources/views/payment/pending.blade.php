@extends('payment.layout')

@section('title', 'Payment Pending')
@section('meta_description', 'Your payment is being processed.')

@section('content')
<div class="glass-card text-center">

    <div class="icon-pending">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
        </svg>
    </div>

    <div class="card-header" style="margin-bottom:24px;">
        <h1>Payment Processing</h1>
        <p>Your payment is being confirmed by your bank. This may take a moment.</p>
    </div>

    <div class="alert alert--warning">
        ⏳ Please do not close this page. We'll update you automatically.
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
            <span class="detail-label">Amount</span>
            <span class="detail-value">QAR {{ number_format($transaction->amount, 2) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span id="status-badge" class="detail-value" style="color:var(--clr-warning);">In Progress</span>
        </div>
    </div>

    <button id="check-btn" onclick="checkStatus()" class="btn btn--outline btn--sm" style="width:auto; margin:0 auto;">
        🔄 Check Status Now
    </button>

    <p class="mt-4" style="font-size:0.8rem; color:var(--clr-muted);">
        Auto-checking every 30 seconds · Transactions may finalise after midnight (Qatar time).
    </p>
</div>
@endsection

@push('scripts')
<script>
    const orderId    = @json($transaction->order_id);
    const statusUrl  = @json(route('payment.status', $transaction->order_id));
    const successUrl = @json(route('payment.success', $transaction->order_id));
    const failedUrl  = @json(route('payment.failed',  $transaction->order_id));

    async function checkStatus() {
        const btn = document.getElementById('check-btn');
        btn.textContent = 'Checking…';
        btn.disabled = true;

        try {
            const res  = await fetch(statusUrl);
            const data = await res.json();

            if (data.status === 'successful') {
                window.location.href = successUrl;
            } else if (data.status === 'failed') {
                window.location.href = failedUrl;
            } else {
                document.getElementById('status-badge').textContent = 'Still in progress…';
            }
        } catch (e) {
            console.error('Status check failed', e);
        } finally {
            btn.textContent = '🔄 Check Status Now';
            btn.disabled = false;
        }
    }

    // Auto-poll every 30 seconds
    setInterval(checkStatus, 30000);
</script>
@endpush
