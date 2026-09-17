@extends('layouts.app')

@section('title', $fund->name . ' - ' . __('Fund Details'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0">{{ $fund->name }}</h4>
            <span class="badge bg-secondary">{{ $fund->code }}</span>
            <span class="badge bg-info-subtle text-info">{{ __(ucfirst($fund->type)) }}</span>
            <span class="badge {{ $fund->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                {{ __(ucfirst($fund->status)) }}
            </span>
        </div>
        @if ($fund->description)
            <div class="text-muted small mt-1">{{ $fund->description }}</div>
        @endif
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#disburseModal">
            <i class="bi bi-arrow-up-right-circle me-1"></i>{{ __('Disburse from Fund') }}
        </button>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#contributeModal">
            <i class="bi bi-arrow-down-left-circle me-1"></i>{{ __('Direct Contribution') }}
        </button>
        <a href="{{ route('funds.edit', $fund) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
        </a>
        <a href="{{ route('funds.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Funds') }}
        </a>
    </div>
</div>

{{-- Linked Programs Notice --}}
@if ($fund->savingsPrograms->isNotEmpty())
    <div class="alert alert-info py-2 mb-4 d-flex align-items-center">
        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
        <div>
            <strong>{{ __('Auto-Contribution Linked Programs') }}:</strong>
            @foreach ($fund->savingsPrograms as $sp)
                <span class="badge bg-primary me-1">
                    {{ $sp->name }} ({{ $sp->code }}) &mdash; ৳{{ number_format($sp->fund_contribution, 2) }}/{{ __($sp->frequency) }} {{ __('installment') }}
                </span>
            @endforeach
        </div>
    </div>
@endif

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <div class="small opacity-75 mb-1">{{ __('Current Fund Balance') }}</div>
                <h3 class="mb-0 fw-bold">৳{{ number_format($fund->current_balance, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success-subtle text-success">
            <div class="card-body">
                <div class="text-muted small mb-1">{{ __('Total Contributions Collected') }}</div>
                <h3 class="mb-0 fw-bold">৳{{ number_format($totalCredit, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger-subtle text-danger">
            <div class="card-body">
                <div class="text-muted small mb-1">{{ __('Total Disbursed Assistance') }}</div>
                <h3 class="mb-0 fw-bold">৳{{ number_format($totalDebit, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small mb-1">{{ __('Contributing Members') }}</div>
                <h3 class="mb-0 fw-bold text-dark">{{ $contributingMembersCount }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Filter Card --}}
<div class="card shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('funds.show', $fund) }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">{{ __('From Date') }}</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">{{ __('To Date') }}</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">{{ __('Type') }}</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="contribution" @selected(request('type') === 'contribution')>{{ __('Contribution') }}</option>
                    <option value="disbursement" @selected(request('type') === 'disbursement')>{{ __('Disbursement') }}</option>
                    <option value="adjustment" @selected(request('type') === 'adjustment')>{{ __('Adjustment') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-filter me-1"></i>{{ __('Filter') }}</button>
                <a href="{{ route('funds.show', $fund) }}" class="btn btn-outline-secondary btn-sm">{{ __('Reset') }}</a>
            </div>
        </form>
    </div>
</div>

{{-- Transactions Table --}}
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>{{ __('Fund Transactions') }}</span>
        <span class="badge bg-secondary">{{ $transactions->total() }} {{ __('records') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Txn No') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Member') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-end">{{ __('Fund Balance') }}</th>
                        <th>{{ __('Payment Method') }}</th>
                        <th>{{ __('Notes / Reference') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $txn)
                        <tr>
                            <td class="fw-semibold">{{ $txn->txn_no }}</td>
                            <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                            <td>
                                @if ($txn->direction === 'credit')
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="bi bi-arrow-down-left me-1"></i>{{ __(ucfirst($txn->type)) }}
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="bi bi-arrow-up-right me-1"></i>{{ __(ucfirst($txn->type)) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($txn->member)
                                    <a href="{{ route('members.show', $txn->member) }}" class="text-decoration-none fw-semibold">
                                        {{ $txn->member->name }}
                                    </a>
                                    <span class="text-muted small">({{ $txn->member->member_no }})</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold {{ $txn->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $txn->direction === 'credit' ? '+' : '-' }}৳{{ number_format($txn->amount, 2) }}
                            </td>
                            <td class="text-end fw-semibold">৳{{ number_format($txn->balance_after, 2) }}</td>
                            <td>{{ __(ucfirst($txn->payment_method)) }}</td>
                            <td>
                                <div class="small text-truncate" style="max-width: 250px;" title="{{ $txn->notes }}">
                                    {{ $txn->notes ?? '—' }}
                                </div>
                                @if ($txn->savingsTransaction)
                                    <a href="{{ route('savings.receipts.show', ['type' => 'savings', 'id' => $txn->savingsTransaction->id]) }}" class="small text-primary text-decoration-none">
                                        <i class="bi bi-receipt me-1"></i>{{ $txn->savingsTransaction->txn_no }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ __('No transactions found for this fund.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($transactions->hasPages())
        <div class="card-footer bg-white">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

{{-- Disburse Modal --}}
<div class="modal fade" id="disburseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('funds.disburse', $fund) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-up-right-circle text-danger me-2"></i>{{ __('Disburse from :name', ['name' => $fund->name]) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border py-2 mb-3">
                        <div class="small text-muted">{{ __('Available Fund Balance') }}:</div>
                        <strong class="text-primary fs-5">৳{{ number_format($fund->current_balance, 2) }}</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Disbursement Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" max="{{ $fund->current_balance }}" name="amount" class="form-control" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Beneficiary Member') }} <span class="text-muted small">({{ __('Optional') }})</span></label>
                        <select name="member_id" class="form-select">
                            <option value="">{{ __('-- None / General Expense --') }}</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->member_no }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="bank">{{ __('Bank') }}</option>
                                <option value="bkash">{{ __('bKash') }}</option>
                                <option value="nagad">{{ __('Nagad') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="txn_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Reference / Voucher No') }}</label>
                        <input type="text" name="reference" class="form-control" placeholder="{{ __('e.g. V-102') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Purpose / Reason / Notes') }} <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('e.g. Medical emergency grant, funeral assistance, welfare payout...') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-check-lg me-1"></i>{{ __('Confirm Disbursement') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Direct Contribution Modal --}}
<div class="modal fade" id="contributeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('funds.contribute', $fund) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-down-left-circle text-success me-2"></i>{{ __('Direct Contribution to :name', ['name' => $fund->name]) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Contribution Amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="0.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Contributor Member') }} <span class="text-muted small">({{ __('Optional') }})</span></label>
                        <select name="member_id" class="form-select">
                            <option value="">{{ __('-- None / Donor / Organization --') }}</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->member_no }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="bank">{{ __('Bank') }}</option>
                                <option value="bkash">{{ __('bKash') }}</option>
                                <option value="nagad">{{ __('Nagad') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="txn_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Reference / Receipt No') }}</label>
                        <input type="text" name="reference" class="form-control" placeholder="{{ __('e.g. Receipt 123') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('e.g. Donation, annual grant, special contribution...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>{{ __('Record Contribution') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

