@extends('layouts.app')

@section('title', __('Collect Payment - :no', ['no' => $loan->loan_no]))

@section('content')
<div class="card shadow-sm" style="max-width: 700px;">
    <div class="card-header bg-white fw-semibold">{{ __('Collect Loan Payment — :no', ['no' => $loan->loan_no]) }}</div>
    <div class="card-body">
        <div class="alert alert-info">
            {{ __('Member') }}: <strong>{{ $loan->member->name }}</strong><br>
            {{ __('Outstanding') }}: <strong class="text-danger">৳{{ number_format($loan->outstanding, 2) }}</strong>
            @if ($nextDue)
                | {{ __('Next installment (:no):', ['no' => $nextDue->installment_no]) }} <strong>৳{{ number_format($nextDue->total, 2) }}</strong>
            @endif
        </div>

        <form method="POST" action="{{ route('loans.repay.store', $loan) }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" value="{{ $nextDue?->total }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Collection Date') }}</label>
                    <input type="date" name="collection_date" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Payment Method') }}</label>
                    <select name="payment_method" class="form-select">
                        @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                            <option value="{{ $method }}">{{ __(ucfirst($method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="setFull">{{ __('Set full installment (:amount)', ['amount' => '৳' . number_format($nextDue?->total ?? 0, 2)]) }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="setOutstanding">{{ __('Set full outstanding (:amount)', ['amount' => '৳' . number_format($loan->outstanding, 2)]) }}</button>
            </div>

            <div class="mt-4">
                <button class="btn btn-success"><i class="bi bi-cash-coin me-1"></i>{{ __('Record Payment') }}</button>
                <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('setFull')?.addEventListener('click', () => document.getElementById('amount').value = {{ $nextDue?->total ?? 0 }});
    document.getElementById('setOutstanding')?.addEventListener('click', () => document.getElementById('amount').value = {{ $loan->outstanding }});
</script>
@endpush