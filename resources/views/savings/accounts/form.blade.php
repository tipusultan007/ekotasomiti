@extends('layouts.app')

@section('title', $account->exists ? __('Edit Savings Account') : __('Open Savings Account'))

@section('content')
<div class="row g-4">
    {{-- Left Side: Account Form --}}
    <div class="col-lg-7 col-xl-8">
        <div class="card shadow-sm border-0" style="border-radius: 16px;">
            <div class="card-header bg-white fw-semibold py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0 fw-bold">{{ $account->exists ? __('Edit Account') . ' ' . $account->account_no : __('Open New Savings Account') }}</h5>
                    <div class="text-muted small">{{ __('Select member and program to initialize account') }}</div>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ $account->exists ? route('savings.accounts.update', $account) : route('savings.accounts.store') }}">
                    @csrf
                    @if ($account->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Member') }} <span class="text-danger">*</span></label>
                            <select name="member_id" class="form-select select2 @error('member_id') is-invalid @enderror" required>
                                <option value="">{{ __('Select Member') }}</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" @selected(old('member_id', $account->member_id ?? request('member_id')) == $member->id)>{{ $member->name }} ({{ $member->member_no }})</option>
                                @endforeach
                            </select>
                            @error('member_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Program') }} <span class="text-danger">*</span></label>
                            <select name="savings_program_id" class="form-select select2" required>
                                <option value="">{{ __('Select Program') }}</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->id }}" data-min="{{ $program->min_deposit }}" data-expected="{{ $program->expected_deposit }}" @selected(old('savings_program_id', $account->savings_program_id) == $program->id)>{{ __($program->name) }} ({{ $program->prefix }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Area') }}</label>
                            <select name="area_id" class="form-select select2">
                                <option value="">{{ __('Select Area') }}</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}" @selected(old('area_id', $account->area_id) == $area->id)>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Opening Date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="opening_date" class="form-control" value="{{ old('opening_date', $account->opening_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Opening Balance') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="{{ old('opening_balance', $account->opening_balance ?? 0) }}" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Min Deposit') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="0" name="min_deposit" id="min_deposit" class="form-control" value="{{ old('min_deposit', $account->min_deposit) }}" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Expected Deposit') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="0" name="expected_deposit" id="expected_deposit" class="form-control" value="{{ old('expected_deposit', $account->expected_deposit) }}" placeholder="0.00">
                            </div>
                        </div>
                        @if ($account->exists)
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('Status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="active" @selected($account->status === 'active')>{{ __('Active') }}</option>
                                    <option value="closed" @selected($account->status === 'closed')>{{ __('Closed') }}</option>
                                    <option value="active" @selected(old('status', $account->status) === 'active')>{{ __('Active') }}</option>
                                    <option value="closed" @selected(old('status', $account->status) === 'closed')>{{ __('Closed') }}</option>
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="status" value="active">
                        @endif
                    </div>

                    <div class="mt-4 pt-2 border-top d-flex gap-2">
                        <button class="btn btn-primary px-4 shadow-sm"><i class="bi bi-check-lg me-1"></i>{{ $account->exists ? __('Update Account') : __('Open Account') }}</button>
                        <a href="{{ route('savings.accounts.index') }}" class="btn btn-outline-secondary px-3">{{ __('Cancel') }}</a>
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
        const programSelect = document.querySelector('select[name="savings_program_id"]');
        const minDepositInput = document.getElementById('min_deposit');
        const expectedDepositInput = document.getElementById('expected_deposit');
        const urlBase = {!! json_encode(url('savings/accounts/member-details')) !!};

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

        // Auto-fill program min & expected deposit when program changed if inputs empty
        if (programSelect) {
            $(programSelect).on('change', function () {
                const opt = this.options[this.selectedIndex];
                if (opt && opt.dataset) {
                    if (minDepositInput && (!minDepositInput.value || minDepositInput.value === '0' || minDepositInput.dataset.autoFilled === 'true')) {
                        minDepositInput.value = opt.dataset.min || '';
                        minDepositInput.dataset.autoFilled = 'true';
                    }
                    if (expectedDepositInput && (!expectedDepositInput.value || expectedDepositInput.value === '0' || expectedDepositInput.dataset.autoFilled === 'true')) {
                        expectedDepositInput.value = opt.dataset.expected || '';
                        expectedDepositInput.dataset.autoFilled = 'true';
                    }
                }
            });
        }
    })();
</script>
@endpush