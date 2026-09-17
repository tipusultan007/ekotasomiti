@extends('layouts.app')

@section('title', $product->exists ? __('Edit Loan Product') : __('Add Loan Product'))

@section('content')
<div class="card shadow-sm" style="max-width: 800px;">
    <div class="card-header bg-white fw-semibold">{{ $product->exists ? __('Edit Loan Product') : __('Add Loan Product') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $product->exists ? route('loans.products.update', $product) : route('loans.products.store') }}">
            @csrf
            @if ($product->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $product->code) }}" placeholder="{{ __('e.g. DL') }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Frequency') }}</label>
                    <select name="frequency" class="form-select">
                        @foreach (['daily', 'weekly', 'monthly'] as $frequency)
                            <option value="{{ $frequency }}" @selected(old('frequency', $product->frequency) === $frequency)>{{ __(ucfirst($frequency)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Prefix') }} <span class="text-danger">*</span></label>
                    <input type="text" name="prefix" class="form-control" value="{{ old('prefix', $product->prefix) }}" placeholder="{{ __('e.g. DL') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Interest Rate (%)') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="interest_rate" class="form-control" value="{{ old('interest_rate', $product->interest_rate) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Interest Type') }}</label>
                    <select name="interest_type" class="form-select">
                        <option value="flat" @selected(old('interest_type', $product->interest_type) === 'flat')>{{ __('Flat') }}</option>
                        <option value="reducing" @selected(old('interest_type', $product->interest_type) === 'reducing')>{{ __('Reducing') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Processing Fee') }}</label>
                    <input type="number" step="0.01" name="processing_fee" class="form-control" value="{{ old('processing_fee', $product->processing_fee) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Insurance Fee') }}</label>
                    <input type="number" step="0.01" name="insurance_fee" class="form-control" value="{{ old('insurance_fee', $product->insurance_fee) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Min Amount') }}</label>
                    <input type="number" step="0.01" name="min_amount" class="form-control" value="{{ old('min_amount', $product->min_amount) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Max Amount') }}</label>
                    <input type="number" step="0.01" name="max_amount" class="form-control" value="{{ old('max_amount', $product->max_amount) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Min Term') }}</label>
                    <input type="number" name="min_term" class="form-control" value="{{ old('min_term', $product->min_term) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Max Term') }}</label>
                    <input type="number" name="max_term" class="form-control" value="{{ old('max_term', $product->max_term) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $product->status) === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status', $product->status) === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $product->exists ? __('Update Product') : __('Create Product') }}</button>
                <a href="{{ route('loans.products.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection