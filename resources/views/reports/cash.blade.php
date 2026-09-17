@extends('layouts.app')

@section('title', __('Cash Report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Cash Report') }}</h4>
    <a href="{{ route('reports.export', array_merge(request()->query(), ['report' => 'cash'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export CSV') }}</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Cash In') }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($summary['cash_in'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Cash Out') }}</div>
            <div class="fs-5 fw-bold text-danger">৳{{ number_format($summary['cash_out'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Expenses') }}</div>
            <div class="fs-5 fw-bold text-warning">৳{{ number_format($summary['expenses'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Income') }}</div>
            <div class="fs-5 fw-bold text-primary">৳{{ number_format($summary['income'], 2) }}</div>
        </div></div>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">{{ __('Filter') }}</button></div>
</form>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">{{ __('Cash Transactions') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Date') }}</th><th>{{ __('Txn No') }}</th><th>{{ __('Type') }}</th><th>{{ __('Direction') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Method') }}</th><th>{{ __('Reference') }}</th><th>{{ __('By') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td>{{ $txn->created_at?->format('d-m-Y') }}</td>
                        <td>{{ $txn->txn_no }}</td>
                        <td>{{ __(ucwords(str_replace('_', ' ', $txn->type))) }}</td>
                        <td><span class="badge {{ $txn->direction === 'in' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ __(strtoupper($txn->direction)) }}</span></td>
                        <td class="amount {{ $txn->direction === 'in' ? 'text-success' : 'text-danger' }}">৳{{ number_format($txn->amount, 2) }}</td>
                        <td>{{ $txn->payment_method ? __(ucfirst($txn->payment_method)) : '—' }}</td>
                        <td>{{ $txn->reference }}</td>
                        <td>{{ $txn->creator?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No cash transactions found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection