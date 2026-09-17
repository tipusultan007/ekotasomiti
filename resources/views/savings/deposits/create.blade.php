@extends('layouts.app')

@section('title', __('New Deposit'))

@section('content')
<div class="card shadow-sm border-0 mb-4" style="max-width: 800px; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center gap-2.5">
        <div class="p-2 rounded-3 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="bi bi-wallet2 fs-5"></i>
        </div>
        <div>
            <h5 class="mb-0 fw-bold text-dark">{{ __('Record Savings Deposit') }}</h5>
            <div class="text-muted small" style="font-size: 0.78rem;">{{ __('Post a new savings deposit transaction') }}</div>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('savings.deposits.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small mb-1">{{ __('Account') }} <span class="text-danger">*</span></label>
                <select name="account_id" class="form-select select2" required>
                    <option value="">{{ __('Select Account') }}</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected(old('account_id', $selectedAccount?->id) == $account->id)>
                            {{ $account->account_no }} — {{ $account->member->name }} ({{ $account->program->name }})
                        </option>
                    @endforeach
                </select>
                @error('account_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small mb-1">{{ __('Deposit Amount') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-success-subtle text-success fw-bold border-end-0 px-3 fs-5">৳</span>
                        <input type="number" step="0.01" min="0.01" name="amount" id="depositAmountInput" 
                               class="form-control form-control-lg fw-bold text-dark border-start-0 ps-1" 
                               value="{{ old('amount') }}" placeholder="0.00" required style="font-size: 1.25rem;">
                    </div>
                    @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small mb-1">{{ __('Transaction Date') }}</label>
                    <input type="date" name="txn_date" class="form-control form-control-lg text-dark" value="{{ old('txn_date', $defaultDate) }}">
                    @error('txn_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small mb-1">{{ __('Payment Method') }}</label>
                    <select name="payment_method" class="form-select">
                        @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                            <option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>{{ __(ucfirst($method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small mb-1">{{ __('Reference / Voucher No') }}</label>
                    <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" placeholder="{{ __('Optional reference or receipt no') }}">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold text-secondary small mb-1">{{ __('Notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('Additional remarks or transaction notes') }}">{{ old('notes') }}</textarea>
                </div>
            </div>

            @if ($selectedAccount)
                <div class="alert alert-info mt-3 mb-0 d-flex align-items-center gap-2 rounded-3">
                    <i class="bi bi-info-circle fs-5"></i>
                    <div>{{ __('Selected account balance:') }} <strong class="fs-6">৳{{ number_format($selectedAccount->current_balance, 2) }}</strong></div>
                </div>
            @endif

            <div class="mt-4 pt-2 d-flex gap-2">
                <button type="submit" class="btn btn-success px-4 py-2 fw-bold rounded-pill shadow-sm">
                    <i class="bi bi-check-circle-fill me-1"></i>{{ __('Record Deposit') }}
                </button>
                <a href="{{ route('savings.deposits.index') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection