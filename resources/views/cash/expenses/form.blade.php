@extends('layouts.app')

@section('title', $expense->exists ? __('Edit Expense') : __('Add Expense'))

@section('content')
<div class="card shadow-sm" style="max-width: 650px;">
    <div class="card-header bg-white fw-semibold">{{ $expense->exists ? __('Edit Expense') : __('Record Expense') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $expense->exists ? route('cash.expenses.update', $expense) : route('cash.expenses.store') }}">
            @csrf
            @if ($expense->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Expense Date') }}</label>
                    <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', $expense->expense_date?->toDateString() ?? now()->toDateString()) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Category') }}</label>
                    <select name="expense_category_id" class="form-select">
                        <option value="">{{ __('Select Category') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('expense_category_id', $expense->expense_category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ old('amount', $expense->amount) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Payment Method') }}</label>
                    <select name="payment_method" class="form-select">
                        @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                            <option value="{{ $method }}" @selected(old('payment_method', $expense->payment_method) === $method)>{{ __(ucfirst($method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Payee') }}</label>
                    <input type="text" name="payee" class="form-control" value="{{ old('payee', $expense->payee) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $expense->description) }}</textarea>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-danger"><i class="bi bi-check-lg me-1"></i>{{ $expense->exists ? __('Update Expense') : __('Record Expense') }}</button>
                <a href="{{ route('cash.expenses.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection