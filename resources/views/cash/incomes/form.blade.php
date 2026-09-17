@extends('layouts.app')

@section('title', $income->exists ? __('Edit Income') : __('Add Income'))

@section('content')
<div class="card shadow-sm" style="max-width: 650px;">
    <div class="card-header bg-white fw-semibold">{{ $income->exists ? __('Edit Income') : __('Record Income') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $income->exists ? route('cash.incomes.update', $income) : route('cash.incomes.store') }}">
            @csrf
            @if ($income->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Income Date') }}</label>
                    <input type="date" name="income_date" class="form-control" value="{{ old('income_date', $income->income_date?->toDateString() ?? now()->toDateString()) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Category') }}</label>
                    <select name="income_category_id" class="form-select">
                        <option value="">{{ __('Select Category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('income_category_id', $income->income_category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ old('amount', $income->amount) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Payment Method') }}</label>
                    <select name="payment_method" class="form-select">
                        @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                            <option value="{{ $method }}" @selected(old('payment_method', $income->payment_method) === $method)>{{ __(ucfirst($method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Source') }}</label>
                    <input type="text" name="source" class="form-control" value="{{ old('source', $income->source) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $income->description) }}</textarea>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>{{ $income->exists ? __('Update Income') : __('Record Income') }}</button>
                <a href="{{ route('cash.incomes.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection