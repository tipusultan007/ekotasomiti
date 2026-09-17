@extends('layouts.app')

@section('title', __('Savings Deposits'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Savings Deposits') }}</h4>
    <a href="{{ route('savings.deposits.create') }}" class="btn btn-success btn-sm"><i class="bi bi-cash-coin me-1"></i>{{ __('New Deposit') }}</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __("Today's Collection") }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($totalToday, 2) }}</div>
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
                <tr><th>{{ __('Txn No') }}</th><th>{{ __('Date') }}</th><th>{{ __('Member') }}</th><th>{{ __('Account') }}</th><th>{{ __('Program') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Method') }}</th><th>{{ __('Officer') }}</th><th>{{ __('Received By') }}</th>@if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))<th class="text-end">{{ __('Actions') }}</th>@endif</tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td><a href="{{ route('savings.receipts.show', ['type' => 'savings', 'id' => $txn->id]) }}">{{ $txn->txn_no }}</a></td>
                        <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                        <td>{{ $txn->member->name }}</td>
                        <td>{{ $txn->account->account_no }}</td>
                        <td>{{ $txn->account->program->name }}</td>
                        <td class="amount fw-semibold text-success">৳{{ number_format($txn->amount, 2) }}</td>
                        <td>{{ __(ucfirst($txn->payment_method)) }}</td>
                        <td>{{ $txn->fieldOfficer?->name }}</td>
                        <td>{{ $txn->receiver?->name }}</td>
                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSavingsTxnModal"
                                    data-action="{{ route('collection.savings.transactions.update', $txn) }}"
                                    data-txn-no="{{ $txn->txn_no }}"
                                    data-amount="{{ $txn->amount }}"
                                    data-date="{{ $txn->txn_date->toDateString() }}"
                                    data-notes="{{ $txn->notes }}" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                                <form method="POST" action="{{ route('collection.savings.transactions.destroy', $txn) }}" class="d-inline" data-confirm="{{ __('Delete collection :no? The deposit will be permanently removed, the account balance restored, and the linked cash entry removed.', ['no' => $txn->txn_no]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ (auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions')) ? 10 : 9 }}" class="text-center text-muted py-4">{{ __('No deposits found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $transactions->links() }}

{{-- EDIT SAVINGS COLLECTION MODAL --}}
<div class="modal fade" id="editSavingsTxnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editSavingsTxnForm">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ __('Edit Savings Collection') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">{{ __('Transaction No') }}</label>
                        <input type="text" class="form-control bg-light font-monospace" id="editSavingsTxnNo" disabled>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold">{{ __('Amount') }} (৳)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control fw-bold fs-5 text-success" id="editSavingsTxnAmount" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold">{{ __('Date') }}</label>
                            <input type="date" name="txn_date" class="form-control" id="editSavingsTxnDate" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label text-muted small fw-semibold">{{ __('Note') }}</label>
                        <textarea name="notes" class="form-control" id="editSavingsTxnNotes" rows="2" placeholder="{{ __('Optional remarks...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-check-lg me-1"></i>{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('editSavingsTxnForm');
        if (!form) return;
        document.querySelectorAll('button[data-bs-target="#editSavingsTxnModal"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                form.action = btn.dataset.action;
                document.getElementById('editSavingsTxnNo').value = btn.dataset.txnNo;
                document.getElementById('editSavingsTxnAmount').value = btn.dataset.amount;
                document.getElementById('editSavingsTxnDate').value = btn.dataset.date;
                document.getElementById('editSavingsTxnNotes').value = btn.dataset.notes || '';
            });
        });
    })();
</script>
@endpush
@endsection