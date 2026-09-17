@extends('layouts.app')

@section('title', __('Statement - :name', ['name' => $member->name]))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Member Statement') }}</h4>
    <div>
        <a href="{{ route('members.show', $member) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}</a>
        <a href="{{ route('members.statement.pdf', $member) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>{{ __('PDF') }}</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><div class="text-muted small">{{ __('Member') }}</div><div class="fw-semibold">{{ $member->name }}</div></div>
            <div class="col-md-3"><div class="text-muted small">{{ __('Member No') }}</div><div class="fw-semibold">{{ $member->member_no }}</div></div>
            <div class="col-md-3"><div class="text-muted small">{{ __('Area') }}</div><div>{{ $member->area?->name }}</div></div>
            <div class="col-md-3"><div class="text-muted small">{{ __('Officer') }}</div><div>{{ $member->fieldOfficer?->name }}</div></div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">{{ __('Ledger') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Date') }}</th><th>{{ __('Account') }}</th><th>{{ __('Type') }}</th><th class="text-end">{{ __('Debit') }}</th><th class="text-end">{{ __('Credit') }}</th><th class="text-end">{{ __('Balance') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($ledger as $txn)
                    <tr>
                        <td>{{ $txn->ledger_date->translatedFormat('d-m-Y') }}</td>
                        <td>{{ $txn->ledger_account }}</td>
                        <td>{{ $txn->ledger_type }} <span class="text-muted">({{ $txn->txn_no ?? '' }})</span></td>
                        <td class="amount">{{ $txn->ledger_debit > 0 ? '৳' . number_format($txn->ledger_debit, 2) : '' }}</td>
                        <td class="amount">{{ $txn->ledger_credit > 0 ? '৳' . number_format($txn->ledger_credit, 2) : '' }}</td>
                        <td class="amount">{{ $txn->ledger_balance !== null ? '৳' . number_format($txn->ledger_balance, 2) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No transactions found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection