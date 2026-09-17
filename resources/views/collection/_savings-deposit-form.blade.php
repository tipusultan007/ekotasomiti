<div class="card shadow-sm border-0 mb-4" style="border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2.5">
            <div class="p-2 rounded-3 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-wallet2 fs-5"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-dark">{{ __('Record') }} {{ __(ucfirst($frequency)) }} {{ __('Savings Deposit') }}</h6>
                <div class="text-muted small" style="font-size: 0.78rem;">{{ __('Daily field collection & counter deposit entry') }}</div>
            </div>
        </div>
        @if ($officer)
            <span class="badge bg-light text-secondary border px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center gap-1">
                <i class="bi bi-person-badge text-primary"></i>
                <span>{{ $officer->name }}</span>
            </span>
        @endif
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('collection.savings.store', $frequency) }}" id="savingsDepositForm">
            @csrf
            @if ($officer)
                <input type="hidden" name="field_officer_id" value="{{ $officer->id }}">
            @endif

            <div class="row g-3">
                {{-- Account Selection --}}
                <div class="col-12">
                    <label class="form-label fw-semibold text-secondary small mb-1">
                        {{ __('Member Account') }} <span class="text-danger">*</span>
                    </label>
                    <select name="account_id" id="savingsAccountSelect" class="form-select select2" data-placeholder="{{ __('Search & select :freq account...', ['freq' => __(ucfirst($frequency))]) }}" required>
                        <option value="">{{ __('Select account') }}</option>
                        @foreach ($accountOptions as $account)
                            <option value="{{ $account->id }}" 
                                    data-balance="{{ $account->current_balance }}"
                                    data-expected="{{ $account->expected_deposit ?? $account->min_deposit ?? 0 }}"
                                    @selected(old('account_id', $selectedAccount?->id) == $account->id)>
                                {{ $account->account_no }} — {{ $account->member->name }} ({{ $account->program->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('account_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Deposit Amount with Currency Prefix --}}
                <div class="col-12 col-sm-7">
                    <label class="form-label fw-semibold text-secondary small mb-1">
                        {{ __('Deposit Amount') }} <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-success-subtle text-success fw-bold border-end-0 px-3 fs-5">৳</span>
                        <input type="number" name="amount" id="savingsAmountInput" step="0.01" min="0.01" 
                               class="form-control form-control-lg fw-bold text-dark border-start-0 ps-1" 
                               value="{{ old('amount') }}" placeholder="0.00" required style="font-size: 1.25rem;">
                    </div>
                    {{-- Quick Amount Chips --}}
                    <div class="d-flex gap-1.5 mt-2 flex-wrap" style="gap: 4px;">
                        @foreach ([50, 100, 200, 500, 1000] as $chip)
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill small" 
                                    style="font-size: 0.75rem;" 
                                    onclick="document.getElementById('savingsAmountInput').value = '{{ $chip }}';">
                                +৳{{ $chip }}
                            </button>
                        @endforeach
                    </div>
                    @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Collection Date --}}
                <div class="col-12 col-sm-5">
                    <label class="form-label fw-semibold text-secondary small mb-1">
                        {{ __('Collection Date') }} <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="txn_date" class="form-control form-control-lg text-dark" value="{{ old('txn_date', $date) }}" required>
                    @error('txn_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                {{-- Payment Method --}}
                <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold text-secondary small mb-1">
                        {{ __('Payment Method') }}
                    </label>
                    <select name="payment_method" class="form-select">
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>💵 {{ __('Cash') }}</option>
                        <option value="bkash" @selected(old('payment_method') === 'bkash')>📱 {{ __('bKash') }}</option>
                        <option value="nagad" @selected(old('payment_method') === 'nagad')>📱 {{ __('Nagad') }}</option>
                        <option value="bank" @selected(old('payment_method') === 'bank')>🏦 {{ __('Bank Transfer') }}</option>
                        <option value="other" @selected(old('payment_method') === 'other')>📝 {{ __('Other') }}</option>
                    </select>
                </div>

                {{-- Note --}}
                <div class="col-12 col-sm-6">
                    <label class="form-label fw-semibold text-secondary small mb-1">
                        {{ __('Note / Voucher No') }}
                    </label>
                    <input type="text" name="notes" class="form-control" placeholder="{{ __('Optional voucher or receipt note') }}" value="{{ old('notes') }}">
                </div>

                {{-- Submit Button --}}
                <div class="col-12 pt-2">
                    <button type="submit" id="savingsDepositSubmitBtn" data-loading-text="{{ __('Recording Deposit...') }}" class="btn btn-success w-100 py-2.5 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <span>{{ __('Record Deposit') }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>