@extends('layouts.app')

@section('title', __('New Loan Application'))

@section('content')
<div class="row g-4">
    {{-- Left Side: Application Form --}}
    <div class="col-lg-7 col-xl-8">
        <div class="card shadow-sm border-0" style="border-radius: 16px;">
            <div class="card-header bg-white fw-semibold py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0 fw-bold">{{ __('New Loan Application') }}</h5>
                    <div class="text-muted small">{{ __('Fill in the loan details and select member') }}</div>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('loans.applications.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Member') }} <span class="text-danger">*</span></label>
                            <select name="member_id" class="form-select select2" required>
                                <option value="">{{ __('Select Member') }}</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" @selected(old('member_id', request('member_id')) == $member->id)>{{ $member->name }} ({{ $member->member_no }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Loan Product') }} <span class="text-danger">*</span></label>
                            <select name="loan_product_id" class="form-select select2" required>
                            <select name="loan_product_id" id="loan_product_id" class="form-select select2" required>
                                <option value="">{{ __('Select Product') }}</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('loan_product_id') == $product->id)>{{ __($product->name) }} ({{ __(ucfirst($product->frequency)) }} • {{ $product->interest_rate }}%)</option>
                                    <option value="{{ $product->id }}"
                                        data-rate="{{ $product->interest_rate }}"
                                        data-type="{{ $product->interest_type ?? 'flat' }}"
                                        data-frequency="{{ $product->frequency }}"
                                        data-min-amount="{{ $product->min_amount }}"
                                        data-max-amount="{{ $product->max_amount }}"
                                        data-min-term="{{ $product->min_term }}"
                                        data-max-term="{{ $product->max_term }}"
                                        @selected(old('loan_product_id') == $product->id)>
                                        {{ __($product->name) }} ({{ __(ucfirst($product->frequency)) }} • {{ $product->interest_rate }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Requested Amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="0.01" name="requested_amount" class="form-control" value="{{ old('requested_amount') }}" placeholder="0.00" required>
                                <input type="number" step="0.01" min="0.01" name="requested_amount" id="requested_amount" class="form-control" value="{{ old('requested_amount') }}" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Requested Term') }} <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="requested_term" class="form-control" value="{{ old('requested_term') }}" placeholder="{{ __('Installments count') }}" required>
                            <input type="number" min="1" name="requested_term" id="requested_term" class="form-control" value="{{ old('requested_term') }}" placeholder="{{ __('Installments count') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Application Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="application_date" class="form-control" value="{{ old('application_date', now()->toDateString()) }}" required>
                        </div>

                        {{-- Live Loan Repayment Calculation Breakdown --}}
                        <div id="loan-calculation-summary" class="col-12" style="display: none;">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f0f7ff 0%, #e6f0fa 100%); border-radius: 12px; border: 1px solid #bfdbfe !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary text-white px-2 py-1"><i class="bi bi-calculator me-1"></i>{{ __('Repayment Summary') }}</span>
                                            <span class="text-secondary small fw-medium" id="calc-interest-rate"></span>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="bi bi-info-circle me-1"></i>{{ __('Auto-calculated based on principal and interest rate') }}
                                        </div>
                                    </div>
                                    <div class="row g-2 text-center">
                                        <div class="col-6 col-md-3">
                                            <div class="bg-white p-2 rounded-3 border">
                                                <div class="text-muted small">{{ __('Principal') }}</div>
                                                <div class="fw-bold fs-6 text-dark" id="calc-principal">৳0.00</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-white p-2 rounded-3 border">
                                                <div class="text-muted small">{{ __('Total Interest') }}</div>
                                                <div class="fw-bold fs-6 text-warning-emphasis" id="calc-total-interest">৳0.00</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-white p-2 rounded-3 border border-success">
                                                <div class="text-success fw-bold small">{{ __('Total to be Paid') }}</div>
                                                <div class="fw-bold fs-5 text-success" id="calc-total-payable">৳0.00</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-white p-2 rounded-3 border border-primary">
                                                <div class="text-primary fw-bold small">{{ __('Per Installment') }}</div>
                                                <div class="fw-bold fs-6 text-primary" id="calc-installment">৳0.00</div>
                                                <div class="text-muted" style="font-size: 11px;" id="calc-installment-note"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Purpose') }}</label>
                            <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="{{ __('e.g. Small business, Agriculture') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Area') }}</label>
                            <select name="area_id" class="form-select select2">
                                <option value="">{{ __('Select Area') }}</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}" @selected(old('area_id') == $area->id)>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('Remarks') }}</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="{{ __('Optional remarks or conditions...') }}">{{ old('remarks') }}</textarea>
                        </div>
                    </div>

                    <h6 class="text-primary mt-4 mb-3 fw-bold"><i class="bi bi-cash-stack me-1"></i>{{ __('Disbursement Details') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Disbursement Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="disbursement_date" class="form-control" value="{{ old('disbursement_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('First Due Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="first_due_date" class="form-control" value="{{ old('first_due_date', now()->addWeek()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select">
                                <option value="cash" @selected(old('payment_method') === 'cash')>{{ __('Cash') }}</option>
                                <option value="bank" @selected(old('payment_method') === 'bank')>{{ __('Bank') }}</option>
                                <option value="bkash" @selected(old('payment_method') === 'bkash')>{{ __('bKash') }}</option>
                                <option value="nagad" @selected(old('payment_method') === 'nagad')>{{ __('Nagad') }}</option>
                                <option value="other" @selected(old('payment_method') === 'other')>{{ __('Other') }}</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary mt-4 mb-3 fw-bold"><i class="bi bi-people me-1"></i>{{ __('Guarantors (Optional)') }}</h6>
                    <div id="guarantors">
                        @php $oldGuarantors = old('guarantors', [['name' => '']]); @endphp
                        @foreach ($oldGuarantors as $index => $guarantor)
                            <div class="row g-2 guarantor-row mb-2">
                                <div class="col-md-4"><input type="text" name="guarantors[{{ $index }}][name]" class="form-control" placeholder="{{ __('Guarantor Name') }}" value="{{ $guarantor['name'] ?? '' }}"></div>
                                <div class="col-md-2"><input type="text" name="guarantors[{{ $index }}][relationship]" class="form-control" placeholder="{{ __('Relation') }}" value="{{ $guarantor['relationship'] ?? '' }}"></div>
                                <div class="col-md-2"><input type="text" name="guarantors[{{ $index }}][nid]" class="form-control" placeholder="{{ __('NID') }}" value="{{ $guarantor['nid'] ?? '' }}"></div>
                                <div class="col-md-3"><input type="text" name="guarantors[{{ $index }}][mobile]" class="form-control" placeholder="{{ __('Mobile') }}" value="{{ $guarantor['mobile'] ?? '' }}"></div>
                                <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-guarantor"><i class="bi bi-x"></i></button></div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addGuarantor"><i class="bi bi-plus"></i> {{ __('Add Guarantor') }}</button>

                    <h6 class="text-primary mt-4 mb-3 fw-bold"><i class="bi bi-file-earmark-arrow-up me-1"></i>{{ __('Loan Documents (Optional)') }}</h6>
                    <div id="documents">
                        <div class="row g-2 document-row mb-2">
                            <div class="col-md-3">
                                <select name="document_types[]" class="form-select">
                                    <option value="nid">{{ __('National ID (NID)') }}</option>
                                    <option value="utility_bill">{{ __('Utility Bill') }}</option>
                                    <option value="agreement">{{ __('Loan Agreement') }}</option>
                                    <option value="income_proof">{{ __('Income Proof') }}</option>
                                    <option value="guarantor_doc">{{ __('Guarantor Document') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="document_titles[]" class="form-control" placeholder="{{ __('Document Title (optional)') }}">
                            </div>
                            <div class="col-md-4">
                                <input type="file" name="documents[]" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger remove-doc"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addDocument"><i class="bi bi-plus"></i> {{ __('Add Document') }}</button>

                    <div class="mt-4 pt-2 border-top d-flex gap-2">
                        <button class="btn btn-primary px-4 shadow-sm"><i class="bi bi-check-lg me-1"></i>{{ __('Create & Disburse Loan') }}</button>
                        <a href="{{ route('loans.applications.index') }}" class="btn btn-outline-secondary px-3">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right Side: Member Details Live Panel --}}
    <div class="col-lg-5 col-xl-4">
        <div id="member-details-panel" class="sticky-top" style="top: 80px; z-index: 10;">
            <div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 16px; border: 1px dashed #cbd5e1 !important; background: #fafafa; min-height: 440px;">
                <div class="card-body text-center text-muted py-5 d-flex flex-column align-items-center justify-content-center">
                    <div class="p-3 rounded-circle bg-white shadow-xs mb-3 border">
                        <i class="bi bi-person-lines-fill fs-2 text-primary"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">{{ __('No Member Selected') }}</h6>
                    <p class="small text-muted mb-0" style="max-width: 250px;">{{ __('Select a member from the form on the left to view profile, savings, active loans, and credit history.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Member Details Loader
    (function () {
        const select = document.querySelector('select[name="member_id"]');
        const panel = document.getElementById('member-details-panel');
        const areaSelect = document.querySelector('select[name="area_id"]');
        const urlBase = {!! json_encode(url('loans/applications/member-details')) !!};

        const textNoMember = {!! json_encode(__('No Member Selected')) !!};
        const textSelectPrompt = {!! json_encode(__('Select a member from the form on the left to view profile, savings, active loans, and credit history.')) !!};
        const textLoading = {!! json_encode(__('Loading Member Details...')) !!};
        const textFetching = {!! json_encode(__('Fetching member financial records and credit history.')) !!};
        const textFailed = {!! json_encode(__('Failed to load member details. Please try again.')) !!};

        const placeholder = '<div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 16px; border: 1px dashed #cbd5e1 !important; background: #fafafa; min-height: 440px;">' +
            '<div class="card-body text-center text-muted py-5 d-flex flex-column align-items-center justify-content-center">' +
            '<div class="p-3 rounded-circle bg-white shadow-xs mb-3 border">' +
            '<i class="bi bi-person-lines-fill fs-2 text-primary"></i>' +
            '</div>' +
            '<h6 class="fw-bold text-dark mb-1">' + textNoMember + '</h6>' +
            '<p class="small text-muted mb-0" style="max-width: 250px;">' + textSelectPrompt + '</p>' +
            '</div>' +
            '</div>';

        const loadingState = '<div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 16px; border: 1px dashed #cbd5e1 !important; background: #fafafa; min-height: 440px;">' +
            '<div class="card-body text-center text-muted py-5 d-flex flex-column align-items-center justify-content-center">' +
            '<div class="spinner-border text-primary mb-3" role="status" style="width: 2.2rem; height: 2.2rem;">' +
            '<span class="visually-hidden">Loading...</span>' +
            '</div>' +
            '<h6 class="fw-bold text-dark mb-1">' + textLoading + '</h6>' +
            '<p class="small text-muted mb-0" style="max-width: 240px;">' + textFetching + '</p>' +
            '</div>' +
            '</div>';

        if (!select || !panel) return;

        function loadMember(id) {
            if (!id) {
                panel.innerHTML = placeholder;
                return;
            }
            panel.innerHTML = loadingState;
            fetch(urlBase + '/' + encodeURIComponent(id))
                .then(res => {
                    if (!res.ok) throw new Error('Network error');
                    return res.text();
                })
                .then(html => {
                    panel.innerHTML = html;
                    const memberCard = panel.querySelector('#member-details-card');
                    if (memberCard && areaSelect) {
                        const areaId = memberCard.dataset.areaId;
                        if (areaId && (!areaSelect.value || areaSelect.dataset.autoFilled === 'true')) {
                            $(areaSelect).val(areaId).trigger('change');
                            areaSelect.dataset.autoFilled = 'true';
                        }
                    }
                })
                .catch(err => {
                    panel.innerHTML = '<div class="alert alert-danger m-3">' + textFailed + '</div>';
                });
        }

        $(select).on('change', function () {
            loadMember($(this).val());
        });

        if (select.value) {
            loadMember(select.value);
        }
    })();

    // Live Loan Repayment Calculation
    (function () {
        const productSelect = document.getElementById('loan_product_id');
        const amountInput = document.getElementById('requested_amount');
        const termInput = document.getElementById('requested_term');
        const summaryBox = document.getElementById('loan-calculation-summary');

        if (!productSelect || !amountInput || !termInput || !summaryBox) return;

        function updateCalculation() {
            const selectedOption = productSelect.options[productSelect.selectedIndex];
            const principal = parseFloat(amountInput.value) || 0;
            const term = parseInt(termInput.value, 10) || 0;

            if (!selectedOption || !selectedOption.value || principal <= 0) {
                summaryBox.style.display = 'none';
                return;
            }

            const rate = parseFloat(selectedOption.dataset.rate) || 0;
            const type = selectedOption.dataset.type || 'flat';
            const frequency = selectedOption.dataset.frequency || 'monthly';

            let totalInterest = 0;
            let totalPayable = 0;
            let installment = 0;

            if (type === 'reducing') {
                let periodDivider = 12;
                if (frequency === 'daily') periodDivider = 365;
                else if (frequency === 'weekly') periodDivider = 52;

                const periodRate = (rate / periodDivider) / 100;
                if (periodRate > 0 && term > 0) {
                    const factor = Math.pow(1 + periodRate, term);
                    installment = principal * periodRate * factor / (factor - 1);
                    totalPayable = installment * term;
                    totalInterest = totalPayable - principal;
                } else {
                    totalInterest = principal * (rate / 100);
                    totalPayable = principal + totalInterest;
                    installment = term > 0 ? (totalPayable / term) : 0;
                }
            } else {
                // Flat interest calculation: Principal * Rate%
                totalInterest = principal * (rate / 100);
                totalPayable = principal + totalInterest;
                installment = term > 0 ? (totalPayable / term) : 0;
            }

            const typeLabel = type === 'reducing' ? 'Reducing' : 'Flat';
            const freqLabel = frequency ? frequency.charAt(0).toUpperCase() + frequency.slice(1) : '';

            document.getElementById('calc-principal').textContent = '৳' + principal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('calc-interest-rate').textContent = rate.toFixed(2) + '% • ' + typeLabel + ' (' + freqLabel + ')';
            document.getElementById('calc-total-interest').textContent = '৳' + Math.max(0, totalInterest).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('calc-total-payable').textContent = '৳' + Math.max(0, totalPayable).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (term > 0) {
                document.getElementById('calc-installment').textContent = '৳' + installment.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('calc-installment-note').textContent = term + ' installments';
            } else {
                document.getElementById('calc-installment').textContent = '-';
                document.getElementById('calc-installment-note').textContent = '{{ __("Enter term") }}';
            }

            summaryBox.style.display = 'block';
        }

        $(productSelect).on('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.value && !termInput.value && opt.dataset.minTerm) {
                termInput.value = opt.dataset.minTerm;
            }
            updateCalculation();
        });

        amountInput.addEventListener('input', updateCalculation);
        amountInput.addEventListener('change', updateCalculation);
        termInput.addEventListener('input', updateCalculation);
        termInput.addEventListener('change', updateCalculation);

        if (productSelect.value && amountInput.value) {
            updateCalculation();
        }
    })();

    // Guarantors & Documents Dynamic Rows
    let guarantorIndex = {{ count(old('guarantors', [['name' => '']])) }};
    document.getElementById('addGuarantor')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 guarantor-row mb-2';
        row.innerHTML = `
            <div class="col-md-4"><input type="text" name="guarantors[${guarantorIndex}][name]" class="form-control" placeholder="{{ __('Guarantor Name') }}"></div>
            <div class="col-md-2"><input type="text" name="guarantors[${guarantorIndex}][relationship]" class="form-control" placeholder="{{ __('Relation') }}"></div>
            <div class="col-md-2"><input type="text" name="guarantors[${guarantorIndex}][nid]" class="form-control" placeholder="{{ __('NID') }}"></div>
            <div class="col-md-3"><input type="text" name="guarantors[${guarantorIndex}][mobile]" class="form-control" placeholder="{{ __('Mobile') }}"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-guarantor"><i class="bi bi-x"></i></button></div>`;
        document.getElementById('guarantors').appendChild(row);
        guarantorIndex++;
        row.querySelector('.remove-guarantor').addEventListener('click', () => row.remove());
    });
    document.querySelectorAll('.remove-guarantor').forEach(btn => btn.addEventListener('click', () => btn.closest('.guarantor-row').remove()));

    document.getElementById('addDocument')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 document-row mb-2';
        row.innerHTML = `
            <div class="col-md-3">
                <select name="document_types[]" class="form-select">
                    <option value="nid">{{ __('National ID (NID)') }}</option>
                    <option value="utility_bill">{{ __('Utility Bill') }}</option>
                    <option value="agreement">{{ __('Loan Agreement') }}</option>
                    <option value="income_proof">{{ __('Income Proof') }}</option>
                    <option value="guarantor_doc">{{ __('Guarantor Document') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="document_titles[]" class="form-control" placeholder="{{ __('Document Title (optional)') }}">
            </div>
            <div class="col-md-4">
                <input type="file" name="documents[]" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger remove-doc"><i class="bi bi-x"></i></button>
            </div>`;
        document.getElementById('documents').appendChild(row);
        row.querySelector('.remove-doc').addEventListener('click', () => row.remove());
    });
    document.querySelectorAll('.remove-doc').forEach(btn => btn.addEventListener('click', () => btn.closest('.document-row').remove()));
</script>
@endpush