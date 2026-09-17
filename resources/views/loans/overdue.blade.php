@extends('layouts.app')

@section('title', __('Overdue Loans'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('Overdue Loans') }}</h4>
        <div class="text-muted small">{{ __('Loans with overdue installments requiring immediate follow-up and collection') }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>{{ __('Active Loans') }}</a>
        <a href="{{ route('loans.repayments.index') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-receipt me-1"></i>{{ __('Repayments') }}</a>
        <a href="{{ route('collection.loans.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-cash-stack me-1"></i>{{ __('Loan Collection') }}</a>
    </div>
</div>

{{-- Summary Stats Cards --}}
<div class="row g-3 mb-3">
    <div class="col-md-6 col-lg-6">
        <div class="card shadow-sm border-start border-danger border-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold">{{ __('Total Overdue Accounts') }}</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($totalOverdueCount) }}</div>
                    </div>
                    <div class="rounded-circle bg-danger-subtle p-3 text-danger">
                        <i class="bi bi-exclamation-octagon fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-6">
        <div class="card shadow-sm border-start border-warning border-4">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small fw-semibold">{{ __('Total Overdue Outstanding') }}</div>
                        <div class="fs-4 fw-bold text-danger">৳{{ number_format($totalOverdueOutstanding, 2) }}</div>
                    </div>
                    <div class="rounded-circle bg-warning-subtle p-3 text-warning">
                        <i class="bi bi-cash-coin fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filter Card --}}
<div class="card shadow-sm mb-3">
    <div class="card-body p-3">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('loans.overdue') }}">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1 text-muted">{{ __('Search') }}</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="{{ __('Loan no, member, phone...') }}" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1 text-muted">{{ __('Product') }}</label>
                <select name="loan_product_id" class="form-select form-select-sm">
                    <option value="">{{ __('All Products') }}</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(request('loan_product_id') == $product->id)>{{ __($product->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1 text-muted">{{ __('Frequency') }}</label>
                <select name="frequency" class="form-select form-select-sm">
                    <option value="">{{ __('All Frequencies') }}</option>
                    <option value="daily" @selected(request('frequency') === 'daily')>{{ __('Daily') }}</option>
                    <option value="weekly" @selected(request('frequency') === 'weekly')>{{ __('Weekly') }}</option>
                    <option value="monthly" @selected(request('frequency') === 'monthly')>{{ __('Monthly') }}</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1 text-muted">{{ __('Area') }}</label>
                <select name="area_id" class="form-select form-select-sm">
                    <option value="">{{ __('All Areas') }}</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            @if (!auth()->user()->isFieldOfficer())
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1 text-muted">{{ __('Field Officer') }}</label>
                <select name="field_officer_id" class="form-select form-select-sm">
                    <option value="">{{ __('All Officers') }}</option>
                    @foreach ($officers as $officer)
                        <option value="{{ $officer->id }}" @selected(request('field_officer_id') == $officer->id)>{{ $officer->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-12 {{ !auth()->user()->isFieldOfficer() ? 'col-md-1' : 'col-md-3' }} d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill" title="{{ __('Filter') }}">
                    <i class="bi bi-funnel me-1"></i>{{ __('Filter') }}
                </button>
                @if (request()->anyFilled(['search', 'loan_product_id', 'frequency', 'area_id', 'field_officer_id']))
                    <a href="{{ route('loans.overdue') }}" class="btn btn-outline-secondary btn-sm" title="{{ __('Reset Filters') }}">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Overdue Loans Table --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="min-height: 220px;">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Loan No') }}</th>
                        <th>{{ __('Member') }}</th>
                        <th>{{ __('Product') }}</th>
                        <th class="text-end">{{ __('Principal') }}</th>
                        <th class="text-end text-primary">{{ __('Installment') }}</th>
                        <th class="text-end text-danger">{{ __('Outstanding') }}</th>
                        <th>{{ __('Officer') }}</th>
                        <th>{{ __('Due Date') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        @php
                            $firstOverdue = $loan->schedules?->first(fn ($s) => in_array($s->status, ['due', 'partial', 'overdue']));
                            $dueDate = $firstOverdue?->due_date;
                            $daysOverdue = $dueDate ? max(0, (int) now()->startOfDay()->diffInDays($dueDate->startOfDay(), false) * -1) : 0;
                        @endphp
                        <tr>
                            <td class="fw-semibold">
                                <a href="{{ route('loans.show', $loan) }}" class="text-decoration-none fw-bold text-dark">
                                    {{ $loan->loan_no }}
                                </a>
                            </td>
                            <td>
                                <div>
                                    <a href="{{ route('members.show', $loan->member) }}" class="fw-semibold text-decoration-none">
                                        {{ $loan->member->name }}
                                    </a>
                                </div>
                                <div class="text-muted small" style="font-size: 11px;">
                                    {{ $loan->member->member_no }}{{ $loan->member->mobile ? ' • ' . $loan->member->mobile : '' }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $freq = strtolower($loan->product->frequency ?? $loan->frequency ?? '');
                                    $badgeClass = match($freq) {
                                        'daily' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'weekly' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'monthly' => 'border',
                                        default => 'bg-secondary-subtle text-secondary border',
                                    };
                                    $badgeIcon = match($freq) {
                                        'daily' => 'bi-calendar-day',
                                        'weekly' => 'bi-calendar-week',
                                        'monthly' => 'bi-calendar-month',
                                        default => 'bi-credit-card',
                                    };
                                    $customStyle = $freq === 'monthly' ? 'background-color: #f3e8ff !important; color: #7e22ce !important; border-color: #e9d5ff !important;' : '';
                                @endphp
                                <span class="badge {{ $badgeClass }} rounded-pill px-2.5 py-1 fw-medium" style="font-size: 0.78rem; {{ $customStyle }}">
                                    <i class="bi {{ $badgeIcon }} me-1"></i>{{ __($loan->product->name ?? 'General Loan') }}
                                </span>
                            </td>
                            <td class="amount text-end">৳{{ number_format($loan->principal_amount, 2) }}</td>
                            <td class="amount text-end fw-bold text-primary">৳{{ number_format($loan->installment_amount, 2) }}</td>
                            <td class="amount text-end text-danger fw-bold">৳{{ number_format($loan->outstanding, 2) }}</td>
                            <td>
                                <span class="small text-muted">{{ $loan->fieldOfficer?->name ?? '-' }}</span>
                            </td>
                            <td>
                                @if ($dueDate)
                                    <div class="small fw-semibold text-danger">
                                        <i class="bi bi-clock-history me-1"></i>{{ $dueDate->format('d-m-Y') }}
                                    </div>
                                    @if ($daysOverdue > 0)
                                        <div class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0" style="font-size: 10px;">
                                            {{ $daysOverdue }} {{ __('days overdue') }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View Details') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('loans.repay', $loan) }}" class="btn btn-sm btn-success text-white" title="{{ __('Collect Repayment') }}">
                                        <i class="bi bi-cash me-1"></i>{{ __('Repay') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                @if (request()->anyFilled(['search', 'loan_product_id', 'frequency', 'area_id', 'field_officer_id']))
                                    <div class="text-muted mb-2">
                                        <i class="bi bi-search fs-3 d-block text-secondary mb-2"></i>
                                        {{ __('No overdue loans found matching the search criteria.') }}
                                    </div>
                                    <a href="{{ route('loans.overdue') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-x-circle me-1"></i>{{ __('Clear Filters') }}
                                    </a>
                                @else
                                    <div class="text-success">
                                        <i class="bi bi-check-circle-fill fs-2 d-block mb-2"></i>
                                        <span class="fw-bold fs-6">{{ __('No overdue loans. Excellent!') }}</span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $loans->links() }}
</div>
@endsection