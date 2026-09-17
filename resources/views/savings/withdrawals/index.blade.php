@extends('layouts.app')

@section('title', __('Savings Withdrawals'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Savings Withdrawals') }}</h4>
    <a href="{{ route('savings.withdrawals.create') }}" class="btn btn-danger btn-sm"><i class="bi bi-cash-stack me-1"></i>{{ __('New Withdrawal') }}</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __("Today's Withdrawals") }}</div>
            <div class="fs-5 fw-bold text-danger">৳{{ number_format($totalToday, 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Filtered Range Total') }}</div>
            <div class="fs-5 fw-bold">৳{{ number_format($totalRange, 2) }}</div>
        </div></div>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-3">
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
    </div>
    <div class="col-md-3">
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>{{ __('Filter') }}</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Txn No') }}</th><th>{{ __('Date') }}</th><th>{{ __('Member') }}</th><th>{{ __('Account') }}</th><th>{{ __('Program') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Method') }}</th><th>{{ __('Officer') }}</th><th>{{ __('Received By') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td><a href="{{ route('savings.receipts.show', ['type' => 'withdrawal', 'id' => $txn->id]) }}">{{ $txn->txn_no }}</a></td>
                        <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                        <td>{{ $txn->member->name }}</td>
                        <td>{{ $txn->account->account_no }}</td>
                        <td>{{ $txn->account->program->name }}</td>
                        <td class="amount fw-semibold text-danger">৳{{ number_format($txn->amount, 2) }}</td>
                        <td>{{ __(ucfirst($txn->payment_method)) }}</td>
                        <td>{{ $txn->fieldOfficer?->name }}</td>
                        <td>{{ $txn->receiver?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No withdrawals found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $transactions->links() }}
@endsection