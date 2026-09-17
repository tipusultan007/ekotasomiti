@extends('layouts.app')

@section('title', $account->exists ? __('Edit Bank Account') : __('Add Bank Account'))

@section('content')
<div class="card shadow-sm" style="max-width: 650px;">
    <div class="card-header bg-white fw-semibold">{{ $account->exists ? __('Edit Bank Account') : __('Add Bank Account') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $account->exists ? route('bank-accounts.update', $account) : route('bank-accounts.store') }}">
            @csrf
            @if ($account->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Account Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $account->account_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Account Number') }}</label>
                    <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $account->account_number) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Bank Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $account->bank_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Branch') }}</label>
                    <input type="text" name="branch" class="form-control" value="{{ old('branch', $account->branch) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Opening Balance') }}</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', $account->opening_balance) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $account->status) === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status', $account->status) === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $account->exists ? __('Update Account') : __('Create Account') }}</button>
                <a href="{{ route('bank-accounts.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection