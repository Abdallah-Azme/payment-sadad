@extends('payment.layout')

@section('title', 'Redirecting to SADAD…')

@section('content')
<div class="glass-card text-center">

    <div class="redirect-spinner" role="status" aria-label="Loading"></div>

    <div class="card-header">
        <h1>Redirecting to SADAD</h1>
        <p>You are being securely redirected to the SADAD payment page.<br>Please do not close this tab.</p>
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
    </div>

    {{-- Hidden auto-submit form to SADAD --}}
    <form id="sadad-form" action="{{ $checkoutUrl }}" method="POST" style="display:none;">
        @foreach ($formFields as $key => $value)
            @if (is_array($value))
                {{-- Handle nested arrays like productdetail --}}
                @foreach ($value as $i => $item)
                    @if (is_array($item))
                        @foreach ($item as $subKey => $subValue)
                            <input type="hidden" name="{{ $key }}[{{ $i }}][{{ $subKey }}]" value="{{ $subValue }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}[{{ $i }}]" value="{{ $item }}">
                    @endif
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
    </form>

    <div class="countdown-bar">
        <div class="countdown-bar__fill"></div>
    </div>

    <p style="margin-top:16px; color: var(--clr-muted); font-size:0.8rem;">
        Not redirected automatically?
    </p>
    <button onclick="document.getElementById('sadad-form').submit()" class="btn btn--outline mt-4 btn--sm" style="width:auto; margin:8px auto 0;">
        Click here to continue →
    </button>

</div>
@endsection

@push('scripts')
<script>
    // Auto-submit after 2 seconds to give the user a moment to see the details
    setTimeout(function () {
        document.getElementById('sadad-form').submit();
    }, 2000);
</script>
@endpush
