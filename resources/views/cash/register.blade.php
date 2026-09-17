@extends('layouts.app')

@section('title', __('Cash Register'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Daily Cash Register') }}</h4>
    <form class="d-flex gap-2" method="GET">
        <input type="date" name="date" class="form-control" value="{{ $date }}">
        <button class="btn btn-outline-primary">{{ __('Load') }}</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Opening Cash') }}</div>
            <div class="fs-5 fw-bold">৳{{ number_format($register->opening_balance ?? 0, 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Total In') }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($register->total_in ?? 0, 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Total Out') }}</div>
            <div class="fs-5 fw-bold text-danger">৳{{ number_format($register->total_out ?? 0, 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Current Cash') }}</div>
            <div class="fs-5 fw-bold">৳{{ number_format(($register->opening_balance ?? 0) + ($register->total_in ?? 0) - ($register->total_out ?? 0), 2) }}</div>
            <span class="badge {{ $register->status === 'open' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ __(ucfirst($register->status ?? 'closed')) }}</span>
        </div></div>
    </div>
</div>

@if ($register->exists)
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">{{ __('Cash Transactions') }}</span>
            <span class="badge bg-primary-subtle text-primary">{{ __(':count transactions', ['count' => $register->transactions_count ?? $register->transactions->count()]) }}</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>{{ __('Txn No') }}</th><th>{{ __('Type') }}</th><th>{{ __('Direction') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Method') }}</th><th>{{ __('Reference') }}</th><th>{{ __('Notes') }}</th><th>{{ __('By') }}</th>@can('reverse transactions')<th>{{ __('Action') }}</th>@endcan</tr>
                </thead>
                <tbody>
                    @forelse ($register->transactions->sortByDesc('id') as $txn)
                        <tr>
                            <td>{{ $txn->txn_no }}</td>
                            <td>{{ __(ucwords(str_replace('_', ' ', $txn->type))) }}</td>
                            <td><span class="badge {{ $txn->direction === 'in' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ __(strtoupper($txn->direction)) }}</span></td>
                            <td class="amount {{ $txn->direction === 'in' ? 'text-success' : 'text-danger' }}">৳{{ number_format($txn->amount, 2) }}</td>
                            <td>{{ $txn->payment_method ? __(ucfirst($txn->payment_method)) : '—' }}</td>
                            <td>{{ $txn->reference }}</td>
                            <td>{{ $txn->notes }}</td>
                            <td>{{ $txn->creator?->name }}</td>
                            @can('reverse transactions')
                                <td>
                                    @if ($txn->status === 'posted' && $txn->source_type === null)
                                        <form method="POST" action="{{ route('cash.register.transactions.reverse', $txn) }}" class="d-inline" data-confirm="{{ __('Reverse cash transaction :no?', ['no' => $txn->txn_no]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-counterclockwise"></i></button>
                                        </form>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-3">{{ __('No transactions for this date.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($register->status === 'open')
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">{{ __('Record Cash Transaction') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('cash.register.transactions.store') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="register_date" value="{{ $date }}">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Type') }}</label>
                        <select name="type" class="form-select">
                            @foreach (['receive', 'payment', 'other_income', 'expense', 'adjustment'] as $type)
                                <option value="{{ $type }}">{{ __(ucwords(str_replace('_', ' ', $type))) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Direction') }}</label>
                        <select name="direction" class="form-select">
                            <option value="in">{{ __('In') }}</option>
                            <option value="out">{{ __('Out') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Method') }}</label>
                        <select name="payment_method" class="form-select">
                            @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                                <option value="{{ $method }}">{{ __(ucfirst($method)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('Officer (optional)') }}</label>
                        <select name="field_officer_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($officers as $officer)
                                <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Reference') }}</label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <input type="text" name="notes" class="form-control">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('Record Transaction') }}</button>
                    </div>
                </form>
            </div>
        </div>

        @can('close cash register')
            <form method="POST" action="{{ route('cash.register.close') }}" class="card shadow-sm mb-4" data-confirm="{{ __('Close the register for this date? Once closed, normal users cannot modify it.') }}">
                @csrf
                <input type="hidden" name="register_date" value="{{ $date }}">
                <div class="card-header bg-white fw-semibold">{{ __('Close Register') }}</div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Physical Cash Count (leave blank to use calculated)') }}</label>
                            <input type="number" step="0.01" name="physical_cash" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">{{ __('Calculated closing:') }} <strong>৳{{ number_format(($register->opening_balance ?? 0) + ($register->total_in ?? 0) - ($register->total_out ?? 0), 2) }}</strong></div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-danger w-100">
                                <i class="bi bi-lock me-1"></i>{{ __('Close Register') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endcan
    @endif
@else
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">{{ __('Open Register for :date', ['date' => $date]) }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('cash.register.open') }}" class="row g-3">
                @csrf
                <input type="hidden" name="register_date" value="{{ $date }}">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Opening Balance') }}</label>
                    <input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="{{ $register->opening_balance }}" required>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary"><i class="bi bi-unlock me-1"></i>{{ __('Open Register') }}</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection