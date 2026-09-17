@extends('layouts.app')

@section('title', __('Bank Accounts'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Bank Accounts') }}</h4>
    <a href="{{ route('bank-accounts.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Bank Account') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Account Name') }}</th><th>{{ __('Bank') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Account No') }}</th><th class="text-end">{{ __('Balance') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <td class="fw-semibold">{{ $account->account_name }}</td>
                        <td>{{ $account->bank_name }}</td>
                        <td>{{ $account->branch }}</td>
                        <td>{{ $account->account_number }}</td>
                        <td class="amount fw-semibold">৳{{ number_format($account->current_balance, 2) }}</td>
                        <td><span class="badge {{ $account->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ __(ucfirst($account->status)) }}</span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#txn{{ $account->id }}"><i class="bi bi-arrow-left-right"></i></button>
                            <a href="{{ route('bank-accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('bank-accounts.destroy', $account) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete bank account :name? Accounts with transactions cannot be deleted.', ['name' => $account->account_name]) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <tr class="collapse" id="txn{{ $account->id }}">
                        <td colspan="7" class="bg-light">
                            <form method="POST" action="{{ route('bank-accounts.transactions.store', $account) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('Type') }}</label>
                                    <select name="type" class="form-select">
                                        <option value="deposit">{{ __('Deposit') }}</option>
                                        <option value="withdrawal">{{ __('Withdrawal') }}</option>
                                        <option value="transfer">{{ __('Transfer') }}</option>
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
                                    <label class="form-label">{{ __('Date') }}</label>
                                    <input type="date" name="txn_date" class="form-control" value="{{ now()->toDateString() }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">{{ __('Reference') }}</label>
                                    <input type="text" name="reference" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100">{{ __('Add') }}</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No bank accounts found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection