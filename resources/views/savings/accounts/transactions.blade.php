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
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Txn No') }}</th><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Balance') }}</th><th>{{ __('Method') }}</th><th>{{ __('Officer') }}</th><th>{{ __('Received By') }}</th><th>{{ __('Status') }}</th>@can('reverse transactions')<th>{{ __('Action') }}</th>@endcan</tr>
            </thead>
            <tbody>
                @forelse ($account->transactions->sortByDesc('txn_date') as $txn)
                    <tr>
                        <td>{{ $txn->txn_no }}</td>
                        <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                        <td>{{ __(\App\Models\SavingsTransaction::typeLabel($txn->type)) }}</td>
                        <td class="amount {{ in_array($txn->type, ['deposit', 'account_opening']) ? 'text-success' : 'text-danger' }}">
                            {{ $txn->amount > 0 ? '৳' . number_format($txn->amount, 2) : '' }}
                        </td>
                        <td class="amount">৳{{ number_format($txn->balance_after ?? 0, 2) }}</td>
                        <td>{{ __(ucfirst($txn->payment_method)) }}</td>
                        <td>{{ $txn->fieldOfficer?->name }}</td>
                        <td>{{ $txn->receiver?->name }}</td>
                        <td>
                            <span class="badge {{ $txn->status === 'posted' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ __(ucfirst($txn->status)) }}</span>
                        </td>
                        @can('reverse transactions')
                            <td>
                                @if ($txn->status === 'posted')
                                    <form method="POST" action="{{ route('savings.transactions.reverse', $txn) }}" class="d-inline" data-confirm="{{ __('Reverse transaction :no? This restores the account balance and reverses any linked cash entry.', ['no' => $txn->txn_no]) }}">
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
                    <tr><td colspan="10" class="text-center text-muted py-4">{{ __('No transactions found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
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
@endsection

@push('scripts')
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