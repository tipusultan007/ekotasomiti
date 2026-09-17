@extends('layouts.app')

@section('title', __('Loan Report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Loan Report') }}</h4>
    <a href="{{ route('reports.export', array_merge(request()->query(), ['report' => 'loans'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export CSV') }}</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Disbursed') }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($summary['disbursed'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Repaid') }}</div>
            <div class="fs-5 fw-bold text-primary">৳{{ number_format($summary['repaid'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Outstanding Principal') }}</div>
            <div class="fs-5 fw-bold text-warning">৳{{ number_format($summary['outstanding'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Overdue Amount') }}</div>
            <div class="fs-5 fw-bold text-danger">৳{{ number_format($summary['overdue'], 2) }}</div>
        </div></div>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">{{ __('All Status') }}</option>
            @foreach (['active', 'paid', 'overdue', 'closed', 'cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst($status)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="product_id" class="form-select">
            <option value="">{{ __('All Products') }}</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ __($product->name) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="area_id" class="form-select">
            <option value="">{{ __('All Areas') }}</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">{{ __('Filter') }}</button></div>
</form>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">{{ __('Loans') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Loan No') }}</th><th>{{ __('Member') }}</th><th>{{ __('Product') }}</th><th>{{ __('Disbursed') }}</th><th class="text-end">{{ __('Principal') }}</th><th class="text-end">{{ __('Repaid') }}</th><th class="text-end">{{ __('Balance') }}</th><th class="text-end">{{ __('Overdue') }}</th><th>{{ __('Status') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td>{{ $loan->loan_no }}</td>
                        <td>{{ $loan->member->name }}</td>
                        <td>{{ $loan->product->name }}</td>
                        <td>{{ $loan->disbursement_date?->format('d-m-Y') }}</td>
                        <td class="amount">৳{{ number_format($loan->principal_amount, 2) }}</td>
                        <td class="amount text-success">৳{{ number_format($loan->repaid_principal, 2) }}</td>
                        <td class="amount fw-semibold">৳{{ number_format($loan->outstanding_principal, 2) }}</td>
                        <td class="amount text-danger">৳{{ number_format($loan->overdue_amount, 2) }}</td>
                        <td><span class="badge {{ $loan->status === 'paid' ? 'bg-success-subtle text-success' : ($loan->overdue_amount > 0 ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') }}">{{ __(ucfirst($loan->status)) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No loans found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection