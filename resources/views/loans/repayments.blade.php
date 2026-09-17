@extends('layouts.app')

@section('title', __('Loan Repayments'))

@section('content')
<h4 class="mb-4">{{ __('Loan Repayments') }}</h4>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Today\'s Loan Collection') }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($transactions->where('txn_date', now()->toDateString())->sum('amount'), 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Filtered Total') }}</div>
            <div class="fs-5 fw-bold">৳{{ number_format($transactions->sum('amount'), 2) }}</div>
        </div></div>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
    <div class="col-md-3"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">{{ __('Filter') }}</button></div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Txn No') }}</th><th>{{ __('Date') }}</th><th>{{ __('Member') }}</th><th>{{ __('Loan') }}</th><th class="text-end">{{ __('Principal') }}</th><th class="text-end">{{ __('Interest') }}</th><th class="text-end">{{ __('Total') }}</th><th>{{ __('Officer') }}</th><th>{{ __('Status') }}</th>@if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))<th class="text-end">{{ __('Actions') }}</th>@endif</tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td><a href="{{ route('savings.receipts.show', ['type' => 'loan', 'id' => $txn->id]) }}">{{ $txn->txn_no }}</a></td>
                        <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                        <td>{{ $txn->member->name }}</td>
                        <td><a href="{{ route('loans.show', $txn->loan) }}">{{ $txn->loan->loan_no }}</a></td>
                        <td class="amount">৳{{ number_format($txn->principal_paid, 2) }}</td>
                        <td class="amount">৳{{ number_format($txn->interest_paid, 2) }}</td>
                        <td class="amount fw-semibold text-success">৳{{ number_format($txn->amount, 2) }}</td>
                        <td>{{ $txn->fieldOfficer?->name }}</td>
                        <td>
                            <span class="badge {{ $txn->status === 'posted' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ __(ucfirst($txn->status)) }}</span>
                        </td>
                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))
                            <td class="text-end">
                                @if ($txn->status === 'posted')
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editLoanTxnModal"
                                        data-action="{{ route('collection.loans.transactions.update', $txn) }}"
                                        data-txn-no="{{ $txn->txn_no }}"
                                        data-amount="{{ $txn->amount }}"
                                        data-date="{{ $txn->txn_date->toDateString() }}"
                                        data-notes="{{ $txn->notes }}" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('loans.repayments.reverse', $txn) }}" class="d-inline" data-confirm="{{ __('Reverse this repayment?') }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" title="{{ __('Reverse') }}"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </form>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ (auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions')) ? 10 : 9 }}" class="text-center text-muted py-4">{{ __('No repayments found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $transactions->links() }}

{{-- EDIT LOAN REPAYMENT MODAL --}}
<div class="modal fade" id="editLoanTxnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editLoanTxnForm">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ __('Edit Loan Repayment') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">{{ __('Transaction No') }}</label>
                        <input type="text" class="form-control bg-light font-monospace" id="editLoanTxnNo" disabled>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold">{{ __('Amount') }} (৳)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control fw-bold fs-5 text-success" id="editLoanTxnAmount" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold">{{ __('Collection Date') }}</label>
                            <input type="date" name="collection_date" class="form-control" id="editLoanTxnDate" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label text-muted small fw-semibold">{{ __('Note') }}</label>
                        <textarea name="notes" class="form-control" id="editLoanTxnNotes" rows="2" placeholder="{{ __('Optional remarks...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold"><i class="bi bi-check-lg me-1"></i>{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('editLoanTxnForm');
        if (!form) return;
        document.querySelectorAll('button[data-bs-target="#editLoanTxnModal"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                form.action = btn.dataset.action;
                document.getElementById('editLoanTxnNo').value = btn.dataset.txnNo;
                document.getElementById('editLoanTxnAmount').value = btn.dataset.amount;
                document.getElementById('editLoanTxnDate').value = btn.dataset.date;
                document.getElementById('editLoanTxnNotes').value = btn.dataset.notes || '';
            });
        });
    })();
</script>
@endpush
@endsection