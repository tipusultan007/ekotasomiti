@extends('layouts.app')

@section('title', __('Savings Report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Savings Report') }}</h4>
    <a href="{{ route('reports.export', array_merge(request()->query(), ['report' => 'savings'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export CSV') }}</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Total Deposits (filtered)') }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($summary['deposits'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Total Withdrawals') }}</div>
            <div class="fs-5 fw-bold text-danger">৳{{ number_format($summary['withdrawals'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Transactions') }}</div>
            <div class="fs-5 fw-bold">{{ $summary['count'] }}</div>
        </div></div>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-2">
        <select name="type" class="form-select">
            <option value="">{{ __('All Types') }}</option>
            @foreach (['deposit', 'withdrawal', 'adjustment', 'correction', 'transfer', 'account_opening', 'account_closing'] as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ __(ucfirst($type)) }}</option>
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
    <div class="col-md-2">
        <select name="officer_id" class="form-select">
            <option value="">{{ __('All Officers') }}</option>
            @foreach ($officers as $officer)
                <option value="{{ $officer->id }}" @selected(request('officer_id') == $officer->id)>{{ $officer->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">{{ __('Filter') }}</button></div>
</form>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">{{ __('Transactions') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Txn No') }}</th><th>{{ __('Date') }}</th><th>{{ __('Member') }}</th><th>{{ __('Account') }}</th><th>{{ __('Type') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Area') }}</th><th>{{ __('Officer') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td>{{ $txn->txn_no }}</td>
                        <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                        <td>{{ $txn->member?->name }}</td>
                        <td>{{ $txn->account?->account_no }}</td>
                        <td>{{ __(ucfirst($txn->type)) }}</td>
                        <td class="amount {{ in_array($txn->type, ['deposit', 'account_opening']) ? 'text-success' : 'text-danger' }}">৳{{ number_format($txn->amount, 2) }}</td>
                        <td>{{ $txn->area?->name }}</td>
                        <td>{{ $txn->fieldOfficer?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No transactions found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">{{ __('Account Balances') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr><th>{{ __('Account No') }}</th><th>{{ __('Member') }}</th><th class="text-end">{{ __('Balance') }}</th></tr></thead>
            <tbody>
                @forelse ($balances as $account)
                    <tr>
                        <td>{{ $account->account_no }}</td>
                        <td>{{ $account->member->name }}</td>
                        <td class="amount fw-semibold">৳{{ number_format($account->current_balance, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">{{ __('No accounts.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection