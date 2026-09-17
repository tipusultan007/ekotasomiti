@extends('layouts.app')

@section('title', __('Income'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Income') }}</h4>
    <a href="{{ route('cash.incomes.create') }}" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Income') }}</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">{{ __('Record Income') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ route('cash.incomes.store') }}">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">{{ __('Date') }}</label>
                    <input type="date" name="income_date" class="form-control" value="{{ old('income_date', now()->toDateString()) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Category') }}</label>
                    <select name="income_category_id" class="form-select">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('income_category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ old('amount') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Method') }}</label>
                    <select name="payment_method" class="form-select">
                        @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                            <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ __(ucfirst($method)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Source') }}</label>
                    <input type="text" name="source" class="form-control" value="{{ old('source') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-success w-100"><i class="bi bi-plus-lg me-1"></i>{{ __('Record') }}</button>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Description') }}</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3"><div class="card-body">
    <div class="text-muted small">{{ __('Filtered Total') }}</div>
    <div class="fs-5 fw-bold text-success">৳{{ number_format($total, 2) }}</div>
</div></div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
    <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">{{ __('Filter') }}</button></div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('No') }}</th><th>{{ __('Date') }}</th><th>{{ __('Category') }}</th><th>{{ __('Source') }}</th><th>{{ __('Description') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Method') }}</th><th>{{ __('By') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Action') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($incomes as $income)
                    <tr>
                        <td class="fw-semibold">{{ $income->income_no }}</td>
                        <td>{{ $income->income_date->format('d-m-Y') }}</td>
                        <td>{{ $income->category?->name }}</td>
                        <td>{{ $income->source }}</td>
                        <td>{{ $income->description }}</td>
                        <td class="amount text-success fw-semibold">৳{{ number_format($income->amount, 2) }}</td>
                        <td>{{ __(ucfirst($income->payment_method)) }}</td>
                        <td>{{ $income->creator?->name }}</td>
                        <td><span class="badge {{ $income->status === 'posted' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ __(ucfirst($income->status)) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('cash.incomes.edit', $income) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('cash.incomes.destroy', $income) }}" class="d-inline" data-confirm="{{ __('Delete income :no? The linked cash entry will also be removed.', ['no' => $income->income_no]) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">{{ __('No income records found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $incomes->links() }}
@endsection