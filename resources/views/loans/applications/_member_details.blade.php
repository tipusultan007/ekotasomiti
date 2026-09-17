<div id="member-details-card" class="card shadow-sm border-0 h-100" style="border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;" data-area-id="{{ $member->area_id ?? '' }}" data-officer-id="{{ $member->field_officer_id ?? '' }}">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 rounded-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-person-bounding-box fs-5"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-dark">{{ __('Member Details') }}</h6>
                <div class="text-muted small" style="font-size: 0.78rem;">{{ __('Profile & Financial History') }}</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge {{ $member->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} px-3 py-1 rounded-pill">
                ● {{ __(ucfirst($member->status)) }}
            </span>
            <a href="{{ route('members.show', $member) }}" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="{{ __('Open full profile in new tab') }}" style="font-size: 0.75rem;">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
        </div>
    </div>

    <div class="card-body p-3 p-md-4">
        {{-- Profile Header --}}
        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border mb-3">
            @if ($member->photo_url)
                <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="rounded-circle border" style="width: 52px; height: 52px; object-fit: cover; flex-shrink: 0;">
            @else
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 52px; height: 52px; flex-shrink: 0;">
                    {{ strtoupper(substr($member->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-grow-1 min-w-0">
                <h6 class="mb-0 fw-bold text-dark text-truncate">{{ $member->name }}</h6>
                @if ($member->name_bn)
                    <div class="text-muted small text-truncate" style="font-size: 0.8rem;">{{ $member->name_bn }}</div>
                @endif
                <div class="d-flex flex-wrap gap-1 mt-1">
                    <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.75rem;">{{ $member->member_no }}</span>
                    @if ($member->area)
                        <span class="badge bg-white text-secondary border" style="font-size: 0.75rem;"><i class="bi bi-geo-alt me-1 text-info"></i>{{ $member->area->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Credit Health / Risk Banner --}}
        @if ($overdueLoans->count() > 0)
            <div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.84rem; border-radius: 10px;">
                <i class="bi bi-exclamation-octagon-fill fs-5 text-danger flex-shrink-0"></i>
                <div>
                    <strong class="d-block text-danger">{{ __('High Risk: Overdue Loan Active!') }}</strong>
                    <span>{{ __(':count loan(s) currently in overdue status.', ['count' => $overdueLoans->count()]) }}</span>
                </div>
            </div>
        @elseif ($activeLoans->count() > 0)
            <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.84rem; border-radius: 10px;">
                <i class="bi bi-info-circle-fill fs-5 text-warning flex-shrink-0"></i>
                <div>
                    <strong class="d-block text-warning-emphasis">{{ __('Active Loans Found') }}</strong>
                    <span>{{ __('Member currently has :count active loan(s). Outstanding: ৳:amount', ['count' => $activeLoans->count(), 'amount' => number_format($totalOutstanding, 2)]) }}</span>
                </div>
            </div>
        @else
            <div class="alert alert-success py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size: 0.84rem; border-radius: 10px;">
                <i class="bi bi-check-circle-fill fs-5 text-success flex-shrink-0"></i>
                <div>
                    <strong class="d-block text-success">{{ __('Clear Loan Record') }}</strong>
                    <span>{{ __('No active or overdue loans at present.') }}</span>
                </div>
            </div>
        @endif

        {{-- Financial Summary KPI Cards --}}
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="p-3 rounded-3 bg-success-subtle border border-success-subtle text-center">
                    <div class="text-muted small mb-1" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: .03em;">{{ __('Total Savings') }}</div>
                    <div class="fs-5 fw-bold text-success">৳{{ number_format($totalSavings, 2) }}</div>
                    <div class="text-muted" style="font-size: 0.72rem;">{{ __(':count active account(s)', ['count' => $member->savingsAccounts->count()]) }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 rounded-3 {{ $totalOutstanding > 0 ? 'bg-danger-subtle border border-danger-subtle' : 'bg-light border' }} text-center">
                    <div class="text-muted small mb-1" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: .03em;">{{ __('Loan Outstanding') }}</div>
                    <div class="fs-5 fw-bold {{ $totalOutstanding > 0 ? 'text-danger' : 'text-secondary' }}">৳{{ number_format($totalOutstanding, 2) }}</div>
                    <div class="text-muted" style="font-size: 0.72rem;">{{ __(':count active loan(s)', ['count' => $activeLoans->count()]) }}</div>
                </div>
            </div>
        </div>

        {{-- Personal Info Table --}}
        <style>
            .member-info-icon {
                width: 20px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-right: 10px;
                color: #64748b;
                font-size: 0.95rem;
            }
        </style>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-borderless mb-0 align-middle" style="font-size: 0.84rem;">
                <tbody>
                    <tr class="border-bottom">
                        <td class="text-muted py-2" style="width: 44%;"><span class="member-info-icon"><i class="bi bi-telephone"></i></span>{{ __('Mobile') }}</td>
                        <td class="text-end py-2">
                            @if ($member->mobile)
                                <a href="tel:{{ $member->mobile }}" class="fw-semibold text-decoration-none">{{ $member->mobile }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="border-bottom">
                        <td class="text-muted py-2"><span class="member-info-icon"><i class="bi bi-credit-card-2-front"></i></span>{{ __('NID') }}</td>
                        <td class="text-end fw-semibold font-monospace py-2">{{ $member->nid ?: '—' }}</td>
                    </tr>
                    @if ($member->father_husband_name)
                        <tr class="border-bottom">
                            <td class="text-muted py-2"><span class="member-info-icon"><i class="bi bi-people"></i></span>{{ __('Father/Husband') }}</td>
                            <td class="text-end py-2 text-truncate" style="max-width: 150px;">{{ $member->father_husband_name }}</td>
                        </tr>
                    @endif
                    @if ($member->mother_name)
                        <tr class="border-bottom">
                            <td class="text-muted py-2"><span class="member-info-icon"><i class="bi bi-person-heart"></i></span>{{ __('Mother') }}</td>
                            <td class="text-end py-2 text-truncate" style="max-width: 150px;">{{ $member->mother_name }}</td>
                        </tr>
                    @endif
                    @if ($member->occupation)
                        <tr class="border-bottom">
                            <td class="text-muted py-2"><span class="member-info-icon"><i class="bi bi-briefcase"></i></span>{{ __('Occupation') }}</td>
                            <td class="text-end py-2">{{ $member->occupation }}</td>
                        </tr>
                    @endif
                    @if ($member->address)
                        <tr class="border-bottom">
                            <td class="text-muted py-2"><span class="member-info-icon"><i class="bi bi-house-door"></i></span>{{ __('Address') }}</td>
                            <td class="text-end py-2 text-truncate" style="max-width: 160px;" title="{{ $member->address }}">{{ $member->address }}</td>
                        </tr>
                    @endif
                    @if ($member->fieldOfficer)
                        <tr class="border-bottom">
                            <td class="text-muted py-2"><span class="member-info-icon"><i class="bi bi-person-badge"></i></span>{{ __('Officer') }}</td>
                            <td class="text-end py-2">{{ $member->fieldOfficer->name }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-muted py-2"><span class="member-info-icon"><i class="bi bi-calendar-check"></i></span>{{ __('Member Since') }}</td>
                        <td class="text-end py-2">{{ $member->membership_date?->format('d M Y') ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Active Loans List (if any) --}}
        @if ($activeLoans->count() > 0)
            <div class="mb-3">
                <div class="fw-semibold text-dark small mb-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-credit-card me-2 text-primary"></i>{{ __('Active Loans') }}</span>
                    <span class="badge bg-secondary-subtle text-secondary">{{ $activeLoans->count() }}</span>
                </div>
                <div class="list-group list-group-flush border rounded-3 overflow-hidden" style="font-size: 0.8rem;">
                    @foreach ($activeLoans as $al)
                        <div class="list-group-item p-2.5">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <a href="{{ route('loans.show', $al) }}" target="_blank" class="fw-bold text-decoration-none">{{ $al->loan_no }}</a>
                                <span class="badge {{ $al->status === 'overdue' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' }}">
                                    {{ __(ucfirst($al->status)) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span>{{ __($al->product?->name) }}</span>
                                <span class="fw-semibold text-danger">৳{{ number_format($al->outstanding, 2) }} {{ __('due') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Active Savings Accounts List (if any) --}}
        @if ($member->savingsAccounts->count() > 0)
            <div>
                <div class="fw-semibold text-dark small mb-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-wallet2 me-2 text-success"></i>{{ __('Savings Accounts') }}</span>
                    <span class="badge bg-secondary-subtle text-secondary">{{ $member->savingsAccounts->count() }}</span>
                </div>
                <div class="list-group list-group-flush border rounded-3 overflow-hidden" style="font-size: 0.8rem;">
                    @foreach ($member->savingsAccounts as $sa)
                        <div class="list-group-item p-2.5 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-semibold font-monospace">{{ $sa->account_no }}</span>
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ __($sa->program?->name) }}</div>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold text-success">৳{{ number_format($sa->current_balance, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
