@extends('layouts.app')

@section('title', __('New Settlement'))

@section('content')
<div class="card shadow-sm" style="max-width: 700px;">
    <div class="card-header bg-white fw-semibold">{{ __('New Cash Settlement') }}</div>
    <div class="card-body">
        @if ($totals)
            <div class="alert alert-info">
                {{ __("Today's auto-calculated totals:") }}
                {{ __('Savings') }} <strong>৳{{ number_format($totals['savings_collection'], 2) }}</strong> |
                {{ __('Loans') }} <strong>৳{{ number_format($totals['loan_collection'], 2) }}</strong> |
                {{ __('Other') }} <strong>৳{{ number_format($totals['other_collection'], 2) }}</strong> |
                {{ __('Total') }} <strong>৳{{ number_format($totals['total_collection'], 2) }}</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('collection.settlements.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Field Officer') }}</label>
                    <select name="field_officer_id" class="form-select select2" required>
                        @foreach ($officers as $officer)
                            <option value="{{ $officer->id }}" @selected($defaultOfficer?->id === $officer->id)>{{ $officer->name }} ({{ $officer->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Settlement Date') }}</label>
                    <input type="date" name="settlement_date" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Savings Collection') }}</label>
                    <input type="number" step="0.01" name="savings_collection" id="savings" class="form-control" value="{{ $totals['savings_collection'] ?? 0 }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Loan Collection') }}</label>
                    <input type="number" step="0.01" name="loan_collection" id="loans" class="form-control" value="{{ $totals['loan_collection'] ?? 0 }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Other Collection') }}</label>
                    <input type="number" step="0.01" name="other_collection" id="other" class="form-control" value="{{ $totals['other_collection'] ?? 0 }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Total Collection') }}</label>
                    <input type="text" id="total" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Cash Submitted') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="cash_submitted" id="submitted" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Remaining Cash') }}</label>
                    <input type="text" id="remaining" class="form-control" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ __('Submit Settlement') }}</button>
                <a href="{{ route('collection.settlements.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function recalc() {
        const s = parseFloat(document.getElementById('savings').value || 0);
        const l = parseFloat(document.getElementById('loans').value || 0);
        const o = parseFloat(document.getElementById('other').value || 0);
        const total = s + l + o;
        document.getElementById('total').value = total.toFixed(2);
        const sub = parseFloat(document.getElementById('submitted').value || 0);
        document.getElementById('remaining').value = (total - sub).toFixed(2);
    }
    ['savings', 'loans', 'other', 'submitted'].forEach(id => document.getElementById(id).addEventListener('input', recalc));
    document.getElementById('submitted').value = {{ $totals['total_collection'] ?? 0 }};
    recalc();
</script>
@endpush