@extends('layouts.app')

@section('title', __('Member Onboarding'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <div>
        <h4 class="mb-1">{{ __('Member Onboarding') }}</h4>
        <div class="text-muted small">{{ __('Register a member and open their savings and loan accounts in one step.') }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('members.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}</a>
        <button type="submit" form="onboardingForm" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ __('Create Member & Accounts') }}</button>
    </div>
</div>

<form method="POST" action="{{ route('onboarding.store') }}" enctype="multipart/form-data" id="onboardingForm">
    @csrf

    <div class="row g-4">
        <div class="col-lg-8">
            {{-- Member Information --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-person-vcard text-primary"></i>
                    <span>{{ __('Member Information') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Membership Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="member[membership_date]" class="form-control" value="{{ old('member.membership_date', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Member Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="member[name]" class="form-control @error('member.name') is-invalid @enderror" value="{{ old('member.name') }}" placeholder="{{ __('Full name in English') }}">
                            @error('member.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Name (Bangla)') }}</label>
                            <input type="text" name="member[name_bn]" class="form-control" value="{{ old('member.name_bn') }}" placeholder="{{ __('সদস্যের নাম') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Father / Husband Name') }}</label>
                            <input type="text" name="member[father_husband_name]" class="form-control" value="{{ old('member.father_husband_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Mother Name') }}</label>
                            <input type="text" name="member[mother_name]" class="form-control" value="{{ old('member.mother_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Date of Birth') }}</label>
                            <input type="date" name="member[dob]" class="form-control" value="{{ old('member.dob') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Gender') }}</label>
                            <select name="member[gender]" class="form-select">
                                @foreach (['male', 'female', 'other'] as $gender)
                                    <option value="{{ $gender }}" @selected(old('member.gender', 'male') === $gender)>{{ __(ucfirst($gender)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Occupation') }}</label>
                            <input type="text" name="member[occupation]" class="form-control" value="{{ old('member.occupation') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Mobile') }}</label>
                            <input type="text" name="member[mobile]" class="form-control" value="{{ old('member.mobile') }}" placeholder="01XXXXXXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('NID') }}</label>
                            <input type="text" name="member[nid]" class="form-control" value="{{ old('member.nid') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Address') }}</label>
                            <textarea name="member[address]" class="form-control" rows="2">{{ old('member.address') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="member[status]" class="form-select">
                                @foreach (['active', 'inactive', 'suspended', 'closed'] as $status)
                                    <option value="{{ $status }}" @selected(old('member.status', 'active') === $status)>{{ __(ucfirst($status)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Nominees --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-people text-warning"></i>
                        <span>{{ __('Nominees') }}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addNominee"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Nominee') }}</button>
                </div>
                <div class="card-body">
                    <div id="nominees">
                        @php $oldNominees = old('nominees', [['name' => '']]); @endphp
                        @php $oldNominees = old('nominees', []); @endphp
                        @foreach ($oldNominees as $index => $nominee)
                            <div class="row g-2 nominee-row mb-2 align-items-center">
                                <div class="col-md-3"><input type="text" name="nominees[{{ $index }}][name]" class="form-control" placeholder="{{ __('Nominee name') }}" value="{{ $nominee['name'] ?? '' }}"></div>
                                <div class="col-md-2"><input type="text" name="nominees[{{ $index }}][relationship]" class="form-control" placeholder="{{ __('Relation') }}" value="{{ $nominee['relationship'] ?? '' }}"></div>
                                <div class="col-md-2"><input type="text" name="nominees[{{ $index }}][nid]" class="form-control" placeholder="{{ __('NID') }}" value="{{ $nominee['nid'] ?? '' }}"></div>
                                <div class="col-md-2"><input type="text" name="nominees[{{ $index }}][mobile]" class="form-control" placeholder="{{ __('Mobile') }}" value="{{ $nominee['mobile'] ?? '' }}"></div>
                                <div class="col-md-2"><div class="input-group"><input type="number" step="0.01" min="0" max="100" name="nominees[{{ $index }}][percentage]" class="form-control" placeholder="%" value="{{ $nominee['percentage'] ?? '' }}"><span class="input-group-text">%</span></div></div>
                                <div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-nominee" title="{{ __('Remove') }}"><i class="bi bi-x-lg"></i></button></div>
                            </div>
                        @endforeach
                    </div>
                    <div id="nomineeEmpty" class="text-muted small {{ count($oldNominees) ? 'd-none' : '' }}"><i class="bi bi-info-circle me-1"></i>{{ __('No nominees added yet.') }}</div>
                </div>
            </div>

            {{-- Savings Account --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-wallet2 text-success"></i>
                    <span>{{ __('Savings Account') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Program') }} <span class="text-danger">*</span></label>
                            <select name="savings[savings_program_id]" class="form-select select2 @error('savings.savings_program_id') is-invalid @enderror">
                                <option value="">{{ __('Select Program') }}</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}" @selected(old('savings.savings_program_id') == $program->id)>{{ __($program->name) }} ({{ $program->prefix }})</option>
                                @endforeach
                            </select>
                            @error('savings.savings_program_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Opening Date') }}</label>
                            <input type="date" name="savings[opening_date]" class="form-control" value="{{ old('savings.opening_date', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Opening Balance') }}</label>
                            <input type="number" step="0.01" min="0" name="savings[opening_balance]" class="form-control" value="{{ old('savings.opening_balance') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Min Deposit') }}</label>
                            <input type="number" step="0.01" name="savings[min_deposit]" class="form-control" value="{{ old('savings.min_deposit') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Expected Deposit') }}</label>
                            <input type="number" step="0.01" name="savings[expected_deposit]" class="form-control" value="{{ old('savings.expected_deposit') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="savings[status]" class="form-select">
                                <option value="active" @selected(old('savings.status', 'active') === 'active')>{{ __('Active') }}</option>
                                <option value="closed" @selected(old('savings.status') === 'closed')>{{ __('Closed') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Loan Account (optional) --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cash-stack text-danger"></i>
                        <span>{{ __('Loan Account') }}</span>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="createLoanToggle" name="loan[create_loan]" value="1" {{ old('loan.create_loan') ? 'checked' : '' }}>
                        <label class="form-check-label" for="createLoanToggle">{{ __('Also create a loan account') }}</label>
                    </div>
                </div>
                <div class="card-body" id="loanSection" style="{{ old('loan.create_loan') ? '' : 'display:none;' }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Loan Product') }} <span class="text-danger">*</span></label>
                            <select name="loan[loan_product_id]" class="form-select select2">
                            <select name="loan[loan_product_id]" id="onboard_loan_product_id" class="form-select select2">
                                <option value="">{{ __('Select Product') }}</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('loan.loan_product_id') == $product->id)>{{ __($product->name) }}</option>
                                    <option value="{{ $product->id }}"
                                        data-rate="{{ $product->interest_rate }}"
                                        data-type="{{ $product->interest_type ?? 'flat' }}"
                                        data-frequency="{{ $product->frequency }}"
                                        data-min-amount="{{ $product->min_amount }}"
                                        data-max-amount="{{ $product->max_amount }}"
                                        data-min-term="{{ $product->min_term }}"
                                        data-max-term="{{ $product->max_term }}"
                                        @selected(old('loan.loan_product_id') == $product->id)>
                                        {{ __($product->name) }} ({{ __(ucfirst($product->frequency)) }} • {{ $product->interest_rate }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Requested Amount') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="loan[requested_amount]" class="form-control" value="{{ old('loan.requested_amount') }}">
                            <input type="number" step="0.01" min="0.01" name="loan[requested_amount]" id="onboard_loan_amount" class="form-control" value="{{ old('loan.requested_amount') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Requested Term') }} <span class="text-danger">*</span></label>
                            <input type="number" min="1" name="loan[requested_term]" class="form-control" value="{{ old('loan.requested_term') }}">
                            <input type="number" min="1" name="loan[requested_term]" id="onboard_loan_term" class="form-control" value="{{ old('loan.requested_term') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Application Date') }}</label>
                            <input type="date" name="loan[application_date]" class="form-control" value="{{ old('loan.application_date', now()->toDateString()) }}">
                        </div>

                        {{-- Live Loan Calculation Breakdown --}}
                        <div id="onboard-loan-calculation-summary" class="col-12" style="display: none;">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f0f7ff 0%, #e6f0fa 100%); border-radius: 12px; border: 1px solid #bfdbfe !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary text-white px-2 py-1"><i class="bi bi-calculator me-1"></i>{{ __('Repayment Summary') }}</span>
                                            <span class="text-secondary small fw-medium" id="onboard-calc-rate"></span>
                                        </div>
                                        <div class="small text-muted">
                                            <i class="bi bi-info-circle me-1"></i>{{ __('Auto-calculated based on principal and interest rate') }}
                                        </div>
                                    </div>
                                    <div class="row g-2 text-center">
                                        <div class="col-6 col-md-3">
                                            <div class="bg-white p-2 rounded-3 border">
                                                <div class="text-muted small">{{ __('Principal') }}</div>
                                                <div class="fw-bold fs-6 text-dark" id="onboard-calc-principal">৳0.00</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-white p-2 rounded-3 border">
                                                <div class="text-muted small">{{ __('Total Interest') }}</div>
                                                <div class="fw-bold fs-6 text-warning-emphasis" id="onboard-calc-interest">৳0.00</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-white p-2 rounded-3 border border-success">
                                                <div class="text-success fw-bold small">{{ __('Total to be Paid') }}</div>
                                                <div class="fw-bold fs-5 text-success" id="onboard-calc-payable">৳0.00</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-white p-2 rounded-3 border border-primary">
                                                <div class="text-primary fw-bold small">{{ __('Per Installment') }}</div>
                                                <div class="fw-bold fs-6 text-primary" id="onboard-calc-installment">৳0.00</div>
                                                <div class="text-muted" style="font-size: 11px;" id="onboard-calc-note"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ __('Disbursement Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="loan[disbursement_date]" class="form-control" value="{{ old('loan.disbursement_date', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('First Due Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="loan[first_due_date]" class="form-control" value="{{ old('loan.first_due_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                            <select name="loan[payment_method]" class="form-select">
                                @foreach (['cash', 'bank', 'bkash', 'nagad', 'other'] as $method)
                                    <option value="{{ $method }}" @selected(old('loan.payment_method', 'cash') === $method)>{{ __(ucfirst($method)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Purpose') }}</label>
                            <input type="text" name="loan[purpose]" class="form-control" value="{{ old('loan.purpose') }}" placeholder="{{ __('Loan purpose or business description') }}">
                        </div>
                    </div>

                    <h6 class="text-primary mt-4 mb-3">{{ __('Guarantors') }}</h6>
                    <div id="guarantors">
                        @php $oldGuarantors = old('loan.guarantors', [['name' => '']]); @endphp
                        @foreach ($oldGuarantors as $index => $guarantor)
                            <div class="row g-2 guarantor-row mb-2">
                                <div class="col-md-4"><input type="text" name="loan[guarantors][{{ $index }}][name]" class="form-control" placeholder="{{ __('Name') }}" value="{{ $guarantor['name'] ?? '' }}"></div>
                                <div class="col-md-2"><input type="text" name="loan[guarantors][{{ $index }}][relationship]" class="form-control" placeholder="{{ __('Relation') }}" value="{{ $guarantor['relationship'] ?? '' }}"></div>
                                <div class="col-md-2"><input type="text" name="loan[guarantors][{{ $index }}][nid]" class="form-control" placeholder="{{ __('NID') }}" value="{{ $guarantor['nid'] ?? '' }}"></div>
                                <div class="col-md-2"><input type="text" name="loan[guarantors][{{ $index }}][mobile]" class="form-control" placeholder="{{ __('Mobile') }}" value="{{ $guarantor['mobile'] ?? '' }}"></div>
                                <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-guarantor"><i class="bi bi-x"></i></button></div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addGuarantor"><i class="bi bi-plus"></i> {{ __('Add Guarantor') }}</button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="position-sticky" style="top: 84px;">
                {{-- Assignment --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt text-info"></i>
                        <span>{{ __('Area & Assignment') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Area') }} <span class="text-danger">*</span></label>
                            <select name="member[area_id]" id="memberAreaSelect" class="form-select select2" required>
                                <option value="">{{ __('Select Area') }}</option>
                                @foreach ($areas as $area)
                                    @php
                                        $officers = $area->fieldOfficers;
                                        $officerNames = $officers->isNotEmpty() ? $officers->pluck('name')->join(', ') : __('No officer assigned');
                                    @endphp
                                    <option value="{{ $area->id }}"
                                        data-officer="{{ $officerNames }}"
                                        @selected(old('member.area_id') == $area->id)>
                                        {{ $area->name }} ({{ $area->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0" id="assignedOfficerBox">
                            <label class="form-label text-muted small mb-1">{{ __('Assigned Field Officer') }}</label>
                            <div class="p-2 bg-light rounded-2 border d-flex align-items-center gap-2 small">
                                <i class="bi bi-person-badge text-primary"></i>
                                <span id="assignedOfficerText" class="fw-medium text-dark">
                                    {{ __('Auto-assigned based on selected Area') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Photo & Notes --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="bi bi-image text-danger"></i>
                        <span>{{ __('Photo & Notes') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div id="photoPreview">
                                <div class="border rounded-3 d-inline-flex align-items-center justify-content-center text-muted" style="width:110px;height:110px;background:#f8fafc;">
                                    <i class="bi bi-person fs-1"></i>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Photo') }}</label>
                            <input type="file" name="member[photo]" id="photoInput" class="form-control" accept="image/*">
                            <div class="form-text">{{ __('JPG or PNG, max 2MB') }}</div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea name="member[notes]" class="form-control" rows="3">{{ old('member.notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card">
                    <div class="card-body d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-check-lg me-1"></i>{{ __('Create Member & Accounts') }}</button>
                        <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    // Loan section toggle
    const loanToggle = document.getElementById('createLoanToggle');
    const loanSection = document.getElementById('loanSection');
    function toggleLoan() {
        loanSection.style.display = loanToggle.checked ? '' : 'none';
    }
    loanToggle?.addEventListener('change', toggleLoan);
    toggleLoan();

    // Nominees
    let nomineeIndex = {{ count(old('nominees', [['name' => '']])) }};
    let nomineeIndex = {{ count($oldNominees) }};
    function toggleNomineeEmpty() {
        document.getElementById('nomineeEmpty')?.classList.toggle('d-none', document.querySelectorAll('.nominee-row').length > 0);
    }
    document.getElementById('addNominee')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 nominee-row mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-md-3"><input type="text" name="nominees[${nomineeIndex}][name]" class="form-control" placeholder="{{ __('Nominee name') }}"></div>
            <div class="col-md-2"><input type="text" name="nominees[${nomineeIndex}][relationship]" class="form-control" placeholder="{{ __('Relation') }}"></div>
            <div class="col-md-2"><input type="text" name="nominees[${nomineeIndex}][nid]" class="form-control" placeholder="{{ __('NID') }}"></div>
            <div class="col-md-2"><input type="text" name="nominees[${nomineeIndex}][mobile]" class="form-control" placeholder="{{ __('Mobile') }}"></div>
            <div class="col-md-2"><div class="input-group"><input type="number" step="0.01" min="0" max="100" name="nominees[${nomineeIndex}][percentage]" class="form-control" placeholder="%"><span class="input-group-text">%</span></div></div>
            <div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-nominee"><i class="bi bi-x-lg"></i></button></div>`;
        document.getElementById('nominees').appendChild(row);
        nomineeIndex++;
        row.querySelector('.remove-nominee').addEventListener('click', () => { row.remove(); toggleNomineeEmpty(); });
        toggleNomineeEmpty();
    });
    document.querySelectorAll('.remove-nominee').forEach(btn => btn.addEventListener('click', () => { btn.closest('.nominee-row').remove(); toggleNomineeEmpty(); }));

    // Guarantors
    let guarantorIndex = {{ count(old('loan.guarantors', [['name' => '']])) }};
    document.getElementById('addGuarantor')?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-2 guarantor-row mb-2';
        row.innerHTML = `
            <div class="col-md-4"><input type="text" name="loan[guarantors][${guarantorIndex}][name]" class="form-control" placeholder="{{ __('Name') }}"></div>
            <div class="col-md-2"><input type="text" name="loan[guarantors][${guarantorIndex}][relationship]" class="form-control" placeholder="{{ __('Relation') }}"></div>
            <div class="col-md-2"><input type="text" name="loan[guarantors][${guarantorIndex}][nid]" class="form-control" placeholder="{{ __('NID') }}"></div>
            <div class="col-md-2"><input type="text" name="loan[guarantors][${guarantorIndex}][mobile]" class="form-control" placeholder="{{ __('Mobile') }}"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-guarantor"><i class="bi bi-x"></i></button></div>`;
        document.getElementById('guarantors').appendChild(row);
        guarantorIndex++;
        row.querySelector('.remove-guarantor').addEventListener('click', () => row.remove());
    });
    document.querySelectorAll('.remove-guarantor').forEach(btn => btn.addEventListener('click', () => btn.closest('.guarantor-row').remove()));

    // Photo preview
    document.getElementById('photoInput')?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('photoPreview').innerHTML = '<img src="' + e.target.result + '" class="rounded-3 border" style="width:110px;height:110px;object-fit:cover;">';
        };
        reader.readAsDataURL(file);
    });

    // Area change -> update assigned officer display
    const memberAreaSelect = document.getElementById('memberAreaSelect');
    if (memberAreaSelect) {
        const updateOfficerDisplay = () => {
            const selectedOption = memberAreaSelect.options[memberAreaSelect.selectedIndex];
            const officer = selectedOption?.getAttribute('data-officer') || "{{ __('Auto-assigned based on selected Area') }}";
            const officerText = document.getElementById('assignedOfficerText');
            if (officerText) {
                officerText.textContent = officer;
            }
        };
        memberAreaSelect.addEventListener('change', updateOfficerDisplay);
        if (window.jQuery) {
            $(memberAreaSelect).on('select2:select', updateOfficerDisplay);
        }
    }

    // Live Loan Repayment Calculation for Onboarding
    (function () {
        const productSelect = document.getElementById('onboard_loan_product_id');
        const amountInput = document.getElementById('onboard_loan_amount');
        const termInput = document.getElementById('onboard_loan_term');
        const summaryBox = document.getElementById('onboard-loan-calculation-summary');

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
                totalInterest = principal * (rate / 100);
                totalPayable = principal + totalInterest;
                installment = term > 0 ? (totalPayable / term) : 0;
            }

            const typeLabel = type === 'reducing' ? 'Reducing' : 'Flat';
            const freqLabel = frequency ? frequency.charAt(0).toUpperCase() + frequency.slice(1) : '';

            document.getElementById('onboard-calc-principal').textContent = '৳' + principal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('onboard-calc-rate').textContent = rate.toFixed(2) + '% • ' + typeLabel + ' (' + freqLabel + ')';
            document.getElementById('onboard-calc-interest').textContent = '৳' + Math.max(0, totalInterest).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('onboard-calc-payable').textContent = '৳' + Math.max(0, totalPayable).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (term > 0) {
                document.getElementById('onboard-calc-installment').textContent = '৳' + installment.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('onboard-calc-note').textContent = term + ' installments';
            } else {
                document.getElementById('onboard-calc-installment').textContent = '-';
                document.getElementById('onboard-calc-note').textContent = '{{ __("Enter term") }}';
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
</script>
@endpush