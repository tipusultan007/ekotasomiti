@extends('layouts.app')

@section('title', __('Settings'))

@section('content')
<h4 class="mb-4">{{ __('General Settings') }}</h4>

<form method="POST" action="{{ route('settings.update') }}">
    @csrf @method('PUT')

    <div class="card shadow-sm mb-4" style="max-width: 800px;">
        <div class="card-header bg-white fw-semibold">{{ __('Organization') }}</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Organization Name') }}</label>
                    <input type="text" name="org_name" class="form-control" value="{{ old('org_name', $settings['org_name'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Currency') }}</label>
                    <input type="text" name="currency" class="form-control" value="{{ old('currency', $settings['currency'] ?? '৳') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Address') }}</label>
                    <input type="text" name="org_address" class="form-control" value="{{ old('org_address', $settings['org_address'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Phone') }}</label>
                    <input type="text" name="org_phone" class="form-control" value="{{ old('org_phone', $settings['org_phone'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Email') }}</label>
                    <input type="email" name="org_email" class="form-control" value="{{ old('org_email', $settings['org_email'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Date Format') }}</label>
                    <input type="text" name="date_format" class="form-control" value="{{ old('date_format', $settings['date_format'] ?? 'd-m-Y') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Account Number Format') }}</label>
                    <input type="text" name="account_number_format" class="form-control" value="{{ old('account_number_format', $settings['account_number_format'] ?? '{prefix}-{sequence}') }}">
                    <div class="form-text">{{ __('Tokens:') }} <code>{prefix}</code> <code>{sequence}</code> <code>{year}</code> <code>{area}</code></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4" style="max-width: 800px;">
        <div class="card-header bg-white fw-semibold">{{ __('Loan Rules') }}</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Daily Late Fee (৳)') }}</label>
                    <input type="number" step="0.01" min="0" name="daily_late_fee" class="form-control" value="{{ old('daily_late_fee', $settings['daily_late_fee'] ?? '0') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4" style="max-width: 800px;">
        <div class="card-header bg-white fw-semibold">{{ __('Savings Rules') }}</div>
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="withdrawal_requires_approval" value="1" id="wd_approval"
                       @checked(old('withdrawal_requires_approval', $settings['withdrawal_requires_approval'] ?? '1') == '1')>
                <label class="form-check-label" for="wd_approval">{{ __('Withdrawals require authorized approval') }}</label>
            </div>
        </div>
    </div>

    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ __('Save Settings') }}</button>
</form>
@endsection