@extends('layouts.app')

@section('title', __('Funds & Welfare'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">{{ __('Funds & Welfare Management') }}</h4>
        <div class="text-muted small">{{ __('Manage central welfare fund, track ৳50 installment deductions, and spend assistance globally.') }}</div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#globalDisburseModal">
            <i class="bi bi-arrow-up-right-circle me-1"></i>{{ __('Spend / Disburse from Fund') }}
        </button>
        <a href="{{ route('funds.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Create Fund') }}
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small mb-1">{{ __('Total Active Funds') }}</div>
                <h4 class="mb-0 fw-bold">{{ $funds->where('status', 'active')->count() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary-subtle text-primary">
            <div class="card-body">
                <div class="text-muted small mb-1">{{ __('Total Central Fund Balance') }}</div>
                <h4 class="mb-0 fw-bold">৳{{ number_format($funds->sum('current_balance'), 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success-subtle text-success">
            <div class="card-body">
                <div class="text-muted small mb-1">{{ __('Total Collected (৳50/mo)') }}</div>
                <h4 class="mb-0 fw-bold">৳{{ number_format($funds->sum('total_contributions'), 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger-subtle text-danger">
            <div class="card-body">
                <div class="text-muted small mb-1">{{ __('Total Disbursed / Spent') }}</div>
                <h4 class="mb-0 fw-bold">৳{{ number_format($funds->sum('total_disbursements'), 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Fund Name') }}</th>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th class="text-end">{{ __('Current Balance') }}</th>
                    <th class="text-end">{{ __('Total Collected') }}</th>
                    <th class="text-end">{{ __('Total Disbursed') }}</th>
                    <th>{{ __('Linked Programs') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($funds as $fund)
                    <tr>
                        <td>
                            <a href="{{ route('funds.show', $fund) }}" class="fw-bold text-decoration-none text-dark">
                                {{ $fund->name }}
                            </a>
                            @if ($fund->description)
                                <div class="text-muted small text-truncate" style="max-width: 250px;">{{ $fund->description }}</div>
                            @endif
                        </td>
                        <td><span class="badge bg-secondary">{{ $fund->code }}</span></td>
                        <td><span class="badge bg-info-subtle text-info">{{ __(ucfirst($fund->type)) }}</span></td>
                        <td class="text-end fw-bold text-primary fs-6">৳{{ number_format($fund->current_balance, 2) }}</td>
                        <td class="text-end text-success">৳{{ number_format($fund->total_contributions ?? 0, 2) }}</td>
                        <td class="text-end text-danger">৳{{ number_format($fund->total_disbursements ?? 0, 2) }}</td>
                        <td>
                            @if ($fund->savings_programs_count > 0)
                                <span class="badge bg-primary-subtle text-primary">{{ $fund->savings_programs_count }} {{ __('programs') }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $fund->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ __(ucfirst($fund->status)) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#globalDisburseModal" onclick="selectDisburseFund({{ $fund->id }})" title="{{ __('Spend from this fund') }}">
                                <i class="bi bi-arrow-up-right-circle me-1"></i>{{ __('Spend') }}
                            </button>
                            <a href="{{ route('funds.show', $fund) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View Details') }}">
                                <i class="bi bi-eye"></i> {{ __('Details') }}
                            </a>
                            <a href="{{ route('funds.edit', $fund) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if (($fund->transactions_count ?? 0) === 0)
                                <form action="{{ route('funds.destroy', $fund) }}" method="POST" class="d-inline" data-confirm="{{ __('Are you sure you want to delete this fund?') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-piggy-bank display-6 text-secondary d-block mb-2"></i>
                            {{ __('No funds found. Click "Create Fund" to add one.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Global Disburse Modal --}}
<div class="modal fade" id="globalDisburseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('funds.global-disburse') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-up-right-circle text-danger me-2"></i>{{ __('Spend / Disburse from Fund') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- Fund Selector --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Select Fund') }} <span class="text-danger">*</span></label>
                        <select name="fund_id" id="modalFundSelect" class="form-select" required>
                            @foreach ($funds as $f)
                                <option value="{{ $f->id }}" data-balance="{{ (float) $f->current_balance }}" data-name="{{ $f->name }}">
                                    {{ $f->name }} ({{ $f->code }}) &mdash; {{ __('Balance') }}: ৳{{ number_format($f->current_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="alert alert-light border py-2 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small text-muted">{{ __('Available Balance') }}:</span>
                            <div class="fw-semibold small" id="modalFundNameDisplay">Welfare Fund</div>
                        </div>
                        <strong class="text-primary fs-5" id="modalFundBalanceDisplay">৳0.00</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Disbursement Amount') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="1" name="amount" id="modalAmountInput" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Beneficiary Member') }} <span class="text-muted small">({{ __('Optional — leave blank for general expense') }})</span></label>
                        <select name="member_id" class="form-select">
                            <option value="">{{ __('-- None / General Expense --') }}</option>
                            @foreach ($members ?? [] as $m)
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
                        <label class="form-label fw-semibold">{{ __('Purpose / Reason / Notes') }} <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('e.g. Medical assistance, emergency grant, funeral aid, welfare expense...') }}" required></textarea>
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

@push('scripts')
<script>
function selectDisburseFund(fundId) {
    const select = document.getElementById('modalFundSelect');
    if (select) {
        select.value = fundId;
        updateModalFundDetails();
    }
}

function updateModalFundDetails() {
    const select = document.getElementById('modalFundSelect');
    const balanceDisplay = document.getElementById('modalFundBalanceDisplay');
    const nameDisplay = document.getElementById('modalFundNameDisplay');
    const amountInput = document.getElementById('modalAmountInput');

    if (select && select.selectedIndex >= 0) {
        const option = select.options[select.selectedIndex];
        const balance = parseFloat(option.dataset.balance || 0);
        const name = option.dataset.name || option.text;
        balanceDisplay.textContent = '৳' + balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        nameDisplay.textContent = name;
        amountInput.max = balance;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('modalFundSelect');
    if (select) {
        select.addEventListener('change', updateModalFundDetails);
        updateModalFundDetails();
    }
});
</script>
@endpush
@endsection
