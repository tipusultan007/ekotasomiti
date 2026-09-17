@extends('layouts.app')

@section('title', __('Spend from Fund / Disburse'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">{{ __('Spend / Disburse from Fund') }}</h4>
        <div class="text-muted small">{{ __('Disburse assistance, welfare payouts, or spend from the central fund.') }}</div>
    </div>
    <a href="{{ route('funds.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Funds') }}
    </a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex align-items-center">
                <i class="bi bi-arrow-up-right-circle text-danger me-2"></i>
                {{ __('Fund Disbursement Form') }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('funds.global-disburse') }}" id="disburseForm">
                    @csrf

                    {{-- Fund Selection --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Select Fund') }} <span class="text-danger">*</span></label>
                        <select name="fund_id" id="fundSelect" class="form-select form-select-lg @error('fund_id') is-invalid @enderror" required>
                            @foreach ($funds as $f)
                                <option value="{{ $f->id }}"
                                    data-balance="{{ (float) $f->current_balance }}"
                                    data-name="{{ $f->name }}"
                                    @selected($selectedFundId == $f->id)>
                                    {{ $f->name }} ({{ $f->code }}) &mdash; {{ __('Balance') }}: ৳{{ number_format($f->current_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('fund_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Live Balance Display Box --}}
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">{{ __('Available Balance in') }} <span id="fundNameDisplay" class="fw-semibold">Welfare Fund</span>:</span>
                            </div>
                            <div>
                                <span class="fs-5 fw-bold text-primary" id="fundBalanceDisplay">৳0.00</span>
                            </div>
                        </div>
                    </div>

                    {{-- Amount --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Disbursement Amount') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="1" name="amount" id="amountInput"
                                class="form-control form-control-lg @error('amount') is-invalid @enderror"
                                placeholder="0.00" value="{{ old('amount') }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-text small" id="amountHelper">{{ __('Cannot exceed available fund balance.') }}</div>
                    </div>

                    {{-- Beneficiary Member --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('Beneficiary Member') }} <span class="text-muted small">({{ __('Optional — leave blank for general welfare/expenses') }})</span></label>
                        <select name="member_id" class="form-select @error('member_id') is-invalid @enderror">
                            <option value="">{{ __('-- None / General Expense --') }}</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}" @selected(old('member_id') == $m->id)>
                                    {{ $m->name }} ({{ $m->member_no }})
                                </option>
                            @endforeach
                        </select>
                        @error('member_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Payment Method & Date --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                @foreach (['cash' => __('Cash'), 'bank' => __('Bank'), 'bkash' => __('bKash'), 'nagad' => __('Nagad'), 'other' => __('Other')] as $methodKey => $methodLabel)
                                    <option value="{{ $methodKey }}" @selected(old('payment_method', 'cash') === $methodKey)>{{ $methodLabel }}</option>
                                @endforeach
                            </select>
                            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="txn_date" class="form-control @error('txn_date') is-invalid @enderror"
                                value="{{ old('txn_date', now()->toDateString()) }}" required>
                            @error('txn_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Reference / Voucher --}}
                    <div class="mb-3">
                        <label class="form-label">{{ __('Reference / Voucher No') }}</label>
                        <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror"
                            placeholder="{{ __('e.g. V-102, Voucher 45') }}" value="{{ old('reference') }}">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Purpose / Reason / Notes --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ __('Purpose / Reason / Notes') }} <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3"
                            placeholder="{{ __('e.g. Medical emergency assistance, funeral aid, member relief grant, community welfare expense...') }}" required>{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-danger btn-lg flex-grow-1">
                            <i class="bi bi-check-lg me-1"></i>{{ __('Confirm Disbursement') }}
                        </button>
                        <a href="{{ route('funds.index') }}" class="btn btn-outline-secondary btn-lg">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Info Sidebar --}}
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-3 bg-primary-subtle text-primary">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i>{{ __('Global Fund Rule') }}</h6>
                <p class="small mb-2">
                    {{ __('For all monthly savings accounts, each installment contributes ৳50 into the Welfare Fund.') }}
                </p>
                <p class="small mb-0">
                    {{ __('When any amount is spent or disbursed using this form, it immediately deducts from the central fund balance and registers cash outflow in the daily cash register.') }}
                </p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">
                {{ __('All Funds Balance Overview') }}
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Fund') }}</th>
                            <th class="text-end">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($funds as $f)
                            <tr>
                                <td>
                                    <a href="{{ route('funds.show', $f) }}" class="text-decoration-none fw-semibold">
                                        {{ $f->name }}
                                    </a>
                                    <span class="badge bg-secondary ms-1">{{ $f->code }}</span>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    ৳{{ number_format($f->current_balance, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fundSelect = document.getElementById('fundSelect');
    const balanceDisplay = document.getElementById('fundBalanceDisplay');
    const nameDisplay = document.getElementById('fundNameDisplay');
    const amountInput = document.getElementById('amountInput');

    function updateFundDetails() {
        const selected = fundSelect.options[fundSelect.selectedIndex];
        if (selected) {
            const balance = parseFloat(selected.dataset.balance || 0);
            const name = selected.dataset.name || selected.text;
            balanceDisplay.textContent = '৳' + balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            nameDisplay.textContent = name;
            amountInput.max = balance;
        }
    }

    fundSelect.addEventListener('change', updateFundDetails);
    updateFundDetails();
});
</script>
@endpush
@endsection
