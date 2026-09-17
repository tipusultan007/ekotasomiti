@extends('layouts.app')

@section('title', __('New Withdrawal'))

@section('content')
<div class="card shadow-sm" style="max-width: 700px;">
    <div class="card-header bg-white fw-semibold">{{ __('Record Savings Withdrawal') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ route('savings.withdrawals.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">{{ __('Account') }} <span class="text-danger">*</span></label>
                <select name="savings_account_id" class="form-select select2" required>
                    <option value="">{{ __('Select Account') }}</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected(old('savings_account_id', $selectedAccount?->id) == $account->id)>
                            {{ $account->account_no }} — {{ $account->member->name }} ({{ __('Balance') }}: ৳{{ number_format($account->current_balance, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select">
                        @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                            <option value="{{ $method }}" @selected(old('payment_method', 'cash') === $method)>{{ __(ucfirst($method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Purpose') }}</label>
                    <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Remarks') }}</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-danger"><i class="bi bi-cash-stack me-1"></i>{{ __('Record Withdrawal') }}</button>
                <a href="{{ route('savings.withdrawals.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection