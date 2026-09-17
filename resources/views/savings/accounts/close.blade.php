@extends('layouts.app')

@section('title', __('Close Account') . ' - ' . $account->account_no)

@section('content')
<div class="card shadow-sm" style="max-width: 600px;">
    <div class="card-header bg-white fw-semibold">{{ __('Close Account') }} {{ $account->account_no }}</div>
    <div class="card-body">
        <div class="alert alert-info">
            {{ __('Current balance:') }} <strong>৳{{ number_format($account->current_balance, 2) }}</strong>
        </div>
        <form method="POST" action="{{ route('savings.accounts.close', $account) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">{{ __('Close Date') }}</label>
                <input type="date" name="close_date" class="form-control" value="{{ now()->toDateString() }}">
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('Closing Reason') }} <span class="text-danger">*</span></label>
                <input type="text" name="closing_reason" class="form-control" required>
            </div>
            <button class="btn btn-danger"><i class="bi bi-check-lg me-1"></i>{{ __('Close Account') }}</button>
            <a href="{{ route('savings.accounts.transactions', $account) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
        </form>
    </div>
</div>
@endsection