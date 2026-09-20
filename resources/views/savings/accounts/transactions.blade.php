@extends('layouts.app')

@section('title', __('Transactions') . ' - ' . $account->account_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Account Transactions') }}</h4>
    <div>
        @if ($account->status === 'active')
            @can('make deposits')
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#depositModal"><i class="bi bi-cash-coin me-1"></i>{{ __('Deposit') }}</button>
            @endcan
            @can('create', \App\Models\SavingsWithdrawal::class)
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal"><i class="bi bi-cash-stack me-1"></i>{{ __('Withdraw') }}</button>
            @endcan
            @can('manage savings accounts')
                <a href="{{ route('savings.accounts.close-form', $account) }}" class="btn btn-outline-secondary btn-sm">{{ __('Close Account') }}</a>
            @endcan
        @else
            <span class="badge bg-secondary">{{ __('Account Closed') }}</span>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex gap-3 align-items-start">
                @if ($account->member->photo_path)
                    <img src="{{ asset('storage/' . $account->member->photo_path) }}" class="rounded-3 border flex-shrink-0" style="width:64px;height:64px;object-fit:cover;">
                @else
                    <div class="rounded-3 border flex-shrink-0 d-flex align-items-center justify-content-center text-muted" style="width:64px;height:64px;background:#f8fafc;">
                        <i class="bi bi-person fs-3"></i>
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="fw-bold text-truncate">{{ $account->member->name }}</div>
                    <div class="text-muted small">
                        {{ $account->member->member_no }}@if ($account->member->name_bn) · {{ $account->member->name_bn }}@endif
                    </div>
                    @if ($account->member->mobile)
                        <div class="small mt-1"><i class="bi bi-telephone me-1 text-muted"></i>{{ $account->member->mobile }}</div>
                    @endif
                    <a href="{{ route('members.show', $account->member) }}" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-person-lines-fill me-1"></i>{{ __('Member Details') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="row g-3 h-100">
            <div class="col-md-4">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small">{{ __('Account No') }}</div>
                    <div class="fw-bold">{{ $account->account_no }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small">{{ __('Program') }}</div>
                    <div class="fw-bold">{{ $account->program->name }}</div>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small">{{ __('Current Balance') }}</div>
                    <div class="fw-bold text-success">৳{{ number_format($account->current_balance, 2) }}</div>
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ __('Area') }}</div>
                    <div class="fw-semibold">{{ $account->member->area?->name ?? '—' }}</div>
                    @if ($account->member->address)
                        <div class="small text-muted text-truncate" title="{{ $account->member->address }}">{{ $account->member->address }}</div>
                    @endif
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small"><i class="bi bi-person-badge me-1"></i>{{ __('Field Officer') }}</div>
                    <div class="fw-semibold">{{ $account->member->fieldOfficer?->name ?? '—' }}</div>
                    @if ($account->member->fieldOfficer?->mobile)
                        <div class="small text-muted">{{ $account->member->fieldOfficer->mobile }}</div>
                    @endif
                </div></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Txn No') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-end">{{ __('Balance') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Officer') }}</th>
                        <th>{{ __('Received By') }}</th>
                        <th>{{ __('Status') }}</th>
                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))
                            <th class="text-end">{{ __('Action') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->transactions->sortByDesc('txn_date') as $txn)
                        <tr>
                            <td>
                                @if(in_array($txn->type, ['deposit', 'withdrawal', 'account_opening']))
                                    <a href="{{ route('savings.receipts.show', ['type' => ($txn->type === 'withdrawal' ? 'withdrawal' : 'savings'), 'id' => $txn->id]) }}" class="text-decoration-none">
                                        {{ $txn->txn_no }}
                                    </a>
                                @else
                                    {{ $txn->txn_no }}
                                @endif
                            </td>
                            <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                            <td>{{ __(\App\Models\SavingsTransaction::typeLabel($txn->type)) }}</td>
                            <td class="amount {{ in_array($txn->type, ['deposit', 'account_opening']) ? 'text-success' : 'text-danger' }}">
                                {{ $txn->amount > 0 ? '৳' . number_format($txn->amount, 2) : '' }}
                            </td>
                            <td class="amount">৳{{ number_format($txn->balance_after ?? 0, 2) }}</td>
                            <td>{{ __(ucfirst($txn->payment_method)) }}</td>
                            <td>{{ $txn->fieldOfficer?->name ?? '—' }}</td>
                            <td>{{ $txn->receiver?->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $txn->status === 'posted' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ __(ucfirst($txn->status)) }}</span>
                            </td>
                            @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))
                                <td class="text-end">
                                    @if ($txn->status === 'posted' && in_array($txn->type, ['deposit', 'account_opening', 'withdrawal']))
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSavingsTxnModal"
                                            data-action="{{ route('savings.transactions.update', $txn) }}"
                                            data-txn-no="{{ $txn->txn_no }}"
                                            data-amount="{{ $txn->amount }}"
                                            data-date="{{ $txn->txn_date->toDateString() }}"
                                            data-notes="{{ $txn->notes }}" title="{{ __('Edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('savings.transactions.destroy', $txn) }}" class="d-inline" data-confirm="{{ __('Delete transaction :no? This will permanently remove the transaction, restore the account balance, and adjust cash entries.', ['no' => $txn->txn_no]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ (auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions')) ? 10 : 9 }}" class="text-center text-muted py-4">{{ __('No transactions found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('savings.deposits.store') }}" id="depositForm">
                @csrf
                <input type="hidden" name="account_id" value="{{ $account->id }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin text-success me-1"></i>{{ __('Record Savings Deposit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 py-2">
                        <div class="fw-semibold">{{ $account->account_no }} — {{ $account->member->name }}</div>
                        <div class="small text-muted">{{ __('Current Balance') }}: ৳{{ number_format($account->current_balance, 2) }}</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Transaction Date') }}</label>
                            <input type="date" name="txn_date" class="form-control" value="{{ old('txn_date', now()->toDateString()) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Payment Method') }}</label>
                            <select name="payment_method" class="form-select">
                                @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                                    <option value="{{ $method }}" @selected($method === 'cash')>{{ __(ucfirst($method)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Reference') }}</label>
                            <input type="text" name="reference" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-cash-coin me-1"></i>{{ __('Record Deposit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('savings.withdrawals.store') }}" id="withdrawForm">
                @csrf
                <input type="hidden" name="savings_account_id" value="{{ $account->id }}">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-stack text-danger me-1"></i>{{ __('Record Withdrawal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border mb-3 py-2">
                        <div class="fw-semibold">{{ $account->account_no }} — {{ $account->member->name }}</div>
                        <div class="small text-muted">{{ __('Current Balance') }}: ৳{{ number_format($account->current_balance, 2) }}</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select">
                                @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                                    <option value="{{ $method }}" @selected($method === 'cash')>{{ __(ucfirst($method)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Purpose') }}</label>
                            <input type="text" name="purpose" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Remarks') }}</label>
                            <textarea name="remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-cash-stack me-1"></i>{{ __('Record Withdrawal') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT SAVINGS TRANSACTION MODAL --}}
<div class="modal fade" id="editSavingsTxnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editSavingsTxnForm">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ __('Edit Savings Transaction') }}</h5>
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
@endsection

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
<script>
    async function submitModalForm(formId, modalId, successMessage) {
        const form = document.getElementById(formId);
        if (!form) return;
        const modal = document.getElementById(modalId);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalHtml = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Processing...') }}';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: new FormData(form),
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const msg = data.errors
                    ? Object.values(data.errors).flat().join('<br>')
                    : (data.message || '{{ __('Something went wrong.') }}');
                throw new Error(msg);
            }

            if (modal && typeof bootstrap.Modal.getInstance(modal) === 'object') {
                bootstrap.Modal.getInstance(modal).hide();
            }
            form.reset();

            Swal.fire({
                icon: 'success',
                title: data.message || successMessage,
                confirmButtonText: '{{ __('OK') }}',
            }).then(() => {
                window.location.reload();
            });
        } catch (err) {
            Swal.fire({ icon: 'error', title: err.message });
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
        }
    }

    document.getElementById('depositForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        submitModalForm('depositForm', 'depositModal', '{{ __('Deposit recorded successfully.') }}');
    });

    document.getElementById('withdrawForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        submitModalForm('withdrawForm', 'withdrawModal', '{{ __('Withdrawal request submitted successfully.') }}');
    });
</script>
@endpush