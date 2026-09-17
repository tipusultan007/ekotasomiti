@extends('layouts.app')

@section('title', $program->exists ? __('Edit Program') : __('Add Program'))

@section('content')
<div class="card shadow-sm" style="max-width: 700px;">
    <div class="card-header bg-white fw-semibold">{{ $program->exists ? __('Edit Savings Program') : __('Add Savings Program') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $program->exists ? route('savings.programs.update', $program) : route('savings.programs.store') }}">
            @csrf
            @if ($program->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $program->code) }}" placeholder="{{ __('e.g. DS') }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $program->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Frequency') }}</label>
                    <select name="frequency" class="form-select">
                        @foreach (['daily', 'weekly', 'monthly'] as $frequency)
                            <option value="{{ $frequency }}" @selected(old('frequency', $program->frequency) === $frequency)>{{ __(ucfirst($frequency)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Account Prefix') }} <span class="text-danger">*</span></label>
                    <input type="text" name="prefix" class="form-control" value="{{ old('prefix', $program->prefix) }}" placeholder="{{ __('e.g. DS') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Min Deposit') }}</label>
                    <input type="number" step="0.01" name="min_deposit" class="form-control" value="{{ old('min_deposit', $program->min_deposit) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Expected Deposit') }}</label>
                    <input type="number" step="0.01" name="expected_deposit" class="form-control" value="{{ old('expected_deposit', $program->expected_deposit) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Max Balance') }}</label>
                    <input type="number" step="0.01" name="max_balance" class="form-control" value="{{ old('max_balance', $program->max_balance) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Linked Fund') }} <span class="text-muted small">({{ __('Optional') }})</span></label>
                    <select name="fund_id" class="form-select">
                        <option value="">{{ __('-- None --') }}</option>
                        @foreach ($funds as $fund)
                            <option value="{{ $fund->id }}" @selected(old('fund_id', $program->fund_id) == $fund->id)>{{ $fund->name }} ({{ $fund->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Fund Contribution per ৳3,000') }}</label>
                    <input type="number" step="0.01" name="fund_contribution" class="form-control" value="{{ old('fund_contribution', $program->fund_contribution ?? 50) }}" placeholder="e.g. 50">
                    <div class="form-text small">{{ __('For monthly savings: ৳50 is deducted for every ৳3,000 installment (e.g. ৳50 for 3,000, ৳100 for 6,000, ৳150 for 9,000).') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $program->status) === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status', $program->status) === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $program->description) }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $program->exists ? __('Update Program') : __('Create Program') }}</button>
                <a href="{{ route('savings.programs.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection