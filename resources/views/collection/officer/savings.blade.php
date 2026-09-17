@extends('layouts.app')

@section('title', __(ucfirst($frequency)) . ' ' . __('Savings Collection'))

@push('styles')
<style>
    .fo-desktop-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        border-radius: 14px;
        color: #fff;
        padding: 16px 20px;
        margin-bottom: 16px;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.2);
    }
    .fo-freq-pill {
        border-radius: 10px;
        padding: 8px 16px;
        font-weight: 600;
        font-size: 0.9rem;
        background: #fff;
        color: #475569;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    .fo-freq-pill:hover {
        background: #f8fafc;
        color: #1e293b;
        border-color: #cbd5e1;
    }
    .fo-freq-pill.active {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .fo-stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 14px 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        height: 100%;
    }
    .fo-member-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 16px;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .fo-member-card:hover {
        box-shadow: 0 6px 16px rgba(0,0,0,0.07);
        border-color: #cbd5e1;
    }
    .fo-member-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        background: #eff6ff;
        color: #2563eb;
        flex-shrink: 0;
    }
    .fo-chip {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 50rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        user-select: none;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
        transition: all 0.15s ease;
    }
    .fo-chip:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }
    .fo-chip.active {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }
    .fo-quick-btn {
        min-height: 44px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.95rem;
    }
    .fo-amount-pill {
        border-radius: 8px;
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        padding: 6px 12px;
        font-weight: 600;
        font-size: 0.85rem;
        color: #334155;
        cursor: pointer;
    }
    .fo-amount-pill:hover, .fo-amount-pill:active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1d4ed8;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    {{-- Top Header Banner --}}
    <div class="fo-desktop-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-white text-primary text-uppercase px-2.5 py-1">{{ __(ucfirst($frequency)) }}</span>
                    <h4 class="mb-0 fw-bold">{{ __('Savings Collection Sheet') }}</h4>
                </div>
                <div class="text-white-50 small">
                    <span><i class="bi bi-person-badge me-1"></i>{{ $officer->name }}</span>
                    <span class="mx-2">•</span>
                    <span><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y, l') }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('collection.savings.print', $frequency) }}?date={{ $date }}" class="btn btn-sm btn-light text-primary rounded-pill px-3 fw-semibold">
                    <i class="bi bi-printer me-1"></i>{{ __('Print Sheet') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Top Navigation & Date Filter Bar --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-2 p-md-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <div class="d-flex gap-2 overflow-x-auto pb-1" style="scrollbar-width: none;">
                        @foreach (['daily', 'weekly', 'monthly'] as $f)
                            <a href="{{ route('collection.savings.sheet', $f) }}?date={{ $date }}" class="fo-freq-pill flex-fill text-center {{ $f === $frequency ? 'active' : '' }}">
                                <i class="bi {{ $f === 'daily' ? 'bi-calendar-day' : ($f === 'weekly' ? 'bi-calendar-week' : 'bi-calendar-month') }}"></i>
                                <span>{{ __(ucfirst($f)) }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    @php
                        $prevDate = \Carbon\Carbon::parse($date)->subDay()->toDateString();
                        $nextDate = \Carbon\Carbon::parse($date)->addDay()->toDateString();
                        $isToday = $date === now()->toDateString();
                    @endphp
                    <form method="GET" class="d-flex gap-1 align-items-center justify-content-md-end">
                        <a href="{{ route('collection.savings.sheet', $frequency) }}?date={{ $prevDate }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Previous Day') }}">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <input type="date" name="date" class="form-control form-control-sm" style="max-width: 155px;" value="{{ $date }}" onchange="this.form.submit()">
                        <a href="{{ route('collection.savings.sheet', $frequency) }}?date={{ $nextDate }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Next Day') }}">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        @if (! $isToday)
                            <a href="{{ route('collection.savings.sheet', $frequency) }}?date={{ now()->toDateString() }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                {{ __('Today') }}
                            </a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary KPI Grid --}}
    @php
        $percent = $totals['expected'] > 0 ? min(100, round(($totals['collected'] / $totals['expected']) * 100)) : 0;
    @endphp
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="fo-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">{{ __('Target / Expected') }}</span>
                    <span class="badge bg-primary-subtle text-primary"><i class="bi bi-bullseye"></i></span>
                </div>
                <div class="fs-4 fw-bold text-dark">৳{{ number_format($totals['expected'], 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="fo-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">{{ __('Collected Today') }}</span>
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle"></i></span>
                </div>
                <div class="fs-4 fw-bold text-success">৳{{ number_format($totals['collected'], 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="fo-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">{{ __('Due Today') }}</span>
                    <span class="badge bg-danger-subtle text-danger"><i class="bi bi-hourglass-bottom"></i></span>
                </div>
                <div class="fs-4 fw-bold text-danger">৳{{ number_format($totals['due'], 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="fo-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">{{ __('Progress') }}</span>
                    <span class="badge bg-info-subtle text-info">{{ $totals['collectedCount'] }} / {{ $rows->count() }}</span>
                </div>
                <div class="fs-4 fw-bold text-primary">{{ $percent }}%</div>
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-2 p-md-3">
            <div class="d-flex justify-content-between align-items-center small mb-1">
                <span class="fw-semibold text-muted">{{ __('Collection Progress') }}</span>
                <span class="fw-bold text-success">৳{{ number_format($totals['collected'], 0) }} / ৳{{ number_format($totals['expected'], 0) }} ({{ $percent }}%)</span>
            </div>
            <div class="progress" style="height: 7px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    {{-- Navigation Tabs: Due List vs Collected Today --}}
    <ul class="nav nav-pills nav-fill bg-white p-1 rounded-3 shadow-sm mb-3 border">
        <li class="nav-item">
            <button class="nav-link active fw-bold py-2" id="tab-due-btn" data-bs-toggle="pill" data-bs-target="#tab-due" type="button">
                <i class="bi bi-list-check me-1"></i>{{ __('Members Due') }} ({{ $rows->count() }})
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold py-2" id="tab-txns-btn" data-bs-toggle="pill" data-bs-target="#tab-txns" type="button">
                <i class="bi bi-receipt me-1"></i>{{ __('Collected Today') }} ({{ $transactions->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- TAB 1: MEMBERS DUE LIST --}}
        <div class="tab-pane fade show active" id="tab-due">

            {{-- Search, Filters, & View Mode Switcher --}}
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-2 p-md-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="memberSearchInput" class="form-control border-start-0" placeholder="{{ __('Search by member, mobile, account...') }}">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn" style="display:none;"><i class="bi bi-x"></i></button>
                            </div>
                        </div>
                        <div class="col-8 col-md-5">
                            <div class="d-flex gap-1 overflow-x-auto pb-1" style="scrollbar-width: none;">
                                <span class="fo-chip active" data-filter="all">{{ __('All') }} ({{ $rows->count() }})</span>
                                <span class="fo-chip" data-filter="due">{{ __('Due') }} ({{ $rows->where('status', 'due')->count() }})</span>
                                <span class="fo-chip" data-filter="paid">{{ __('Collected') }} ({{ $rows->where('status', 'paid')->count() }})</span>
                            </div>
                        </div>
                        <div class="col-4 col-md-2 text-end">
                            <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('View Mode') }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnViewTable" onclick="setViewMode('table')" title="{{ __('Table View (Desktop)') }}">
                                    <i class="bi bi-table"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnViewGrid" onclick="setViewMode('grid')" title="{{ __('Card Grid View') }}">
                                    <i class="bi bi-grid-3x3-gap"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 1A. TABLE VIEW (Optimized for Desktop) --}}
            <div id="savingsTableView" class="card shadow-sm border-0 mb-3">
                <div class="card-body p-0">
                    <div class="table-responsive" style="min-height: 280px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 45px;">#</th>
                                    <th>{{ __('Member') }}</th>
                                    <th>{{ __('Account No') }}</th>
                                    <th>{{ __('Area') }}</th>
                                    <th class="text-end">{{ __('Expected') }}</th>
                                    <th class="text-end">{{ __('Collected') }}</th>
                                    <th class="text-end">{{ __('Due Today') }}</th>
                                    <th class="text-end">{{ __('Balance') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-end pe-3">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $index => $row)
                                    @php
                                        $acc = $row->account;
                                        $mem = $acc->member;
                                        $isPaid = $row->status === 'paid';
                                        $isPartial = $row->status === 'partial';
                                    @endphp
                                    <tr class="member-item member-table-row"
                                        data-name="{{ strtolower($mem->name) }}"
                                        data-account="{{ strtolower($acc->account_no) }}"
                                        data-mobile="{{ $mem->mobile }}"
                                        data-area="{{ strtolower($acc->area?->name ?? '') }}"
                                        data-status="{{ $row->status }}">
                                        <td class="ps-3 text-muted small">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="fo-member-avatar" style="width: 32px; height: 32px; font-size: 0.9rem; border-radius: 8px;">
                                                    {{ strtoupper(substr($mem->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <a href="{{ route('members.show', $mem) }}" class="fw-bold text-dark text-decoration-none d-block">{{ $mem->name }}</a>
                                                    @if ($mem->mobile)
                                                        <small class="text-muted"><i class="bi bi-phone"></i> {{ $mem->mobile }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-primary font-monospace">{{ $acc->account_no }}</span>
                                            <small class="text-muted d-block">{{ $acc->program->name }}</small>
                                        </td>
                                        <td>{{ $acc->area?->name ?? '—' }}</td>
                                        <td class="text-end fw-semibold">৳{{ number_format($row->expected, 2) }}</td>
                                        <td class="text-end text-success fw-bold">৳{{ number_format($row->collected, 2) }}</td>
                                        <td class="text-end {{ $row->due > 0 ? 'text-danger fw-bold' : 'text-muted' }}">৳{{ number_format($row->due, 2) }}</td>
                                        <td class="text-end fw-semibold text-primary">৳{{ number_format($row->current_balance, 2) }}</td>
                                        <td class="text-center">
                                            @if ($isPaid)
                                                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Collected') }}</span>
                                            @elseif ($isPartial)
                                                <span class="badge bg-warning-subtle text-warning"><i class="bi bi-hourglass-split me-1"></i>{{ __('Partial') }}</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">{{ __('Due') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end align-items-center gap-1">
                                                @if ($mem->mobile)
                                                    <a href="tel:{{ $mem->mobile }}" class="btn btn-sm btn-outline-secondary rounded-circle px-2" title="{{ __('Call') }}">
                                                        <i class="bi bi-telephone"></i>
                                                    </a>
                                                @endif
                                                @if (! $isPaid)
                                                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold fo-open-collect-modal text-nowrap"
                                                        data-account-id="{{ $acc->id }}"
                                                        data-account-no="{{ $acc->account_no }}"
                                                        data-member-name="{{ $mem->name }}"
                                                        data-expected="{{ $row->expected }}"
                                                        data-due="{{ $row->due > 0 ? $row->due : $row->expected }}"
                                                        data-balance="{{ $row->current_balance }}">
                                                        <i class="bi bi-plus-circle me-1"></i>{{ __('Collect') }} ৳{{ number_format($row->due > 0 ? $row->due : $row->expected, 0) }}
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold fo-open-collect-modal text-nowrap"
                                                        data-account-id="{{ $acc->id }}"
                                                        data-account-no="{{ $acc->account_no }}"
                                                        data-member-name="{{ $mem->name }}"
                                                        data-expected="{{ $row->expected }}"
                                                        data-due="{{ $row->expected }}"
                                                        data-balance="{{ $row->current_balance }}">
                                                        <i class="bi bi-check2-circle me-1"></i>{{ __('Add More') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center py-5 text-muted">{{ __('No savings accounts found for this frequency.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 1B. GRID / CARD VIEW (Responsive multi-column on desktop, single on mobile) --}}
            <div id="savingsGridView" class="d-none mb-3">
                <div class="row g-3" id="memberListContainer">
                    @forelse ($rows as $row)
                        @php
                            $acc = $row->account;
                            $mem = $acc->member;
                            $isPaid = $row->status === 'paid';
                            $isPartial = $row->status === 'partial';
                        @endphp
                        <div class="col-12 col-md-6 col-xl-4 member-item member-grid-card"
                            data-name="{{ strtolower($mem->name) }}"
                            data-account="{{ strtolower($acc->account_no) }}"
                            data-mobile="{{ $mem->mobile }}"
                            data-area="{{ strtolower($acc->area?->name ?? '') }}"
                            data-status="{{ $row->status }}">
                            
                            <div class="fo-member-card h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-start gap-3 mb-2">
                                        <div class="fo-member-avatar">
                                            {{ strtoupper(substr($mem->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark text-truncate">{{ $mem->name }}</h6>
                                                    <div class="text-muted small">
                                                        <span class="fw-semibold text-primary">{{ $acc->account_no }}</span>
                                                        @if ($acc->area)
                                                            <span class="badge bg-light text-secondary ms-1">{{ $acc->area->name }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if ($isPaid)
                                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Collected') }}</span>
                                                @elseif ($isPartial)
                                                    <span class="badge bg-warning-subtle text-warning"><i class="bi bi-hourglass-split me-1"></i>{{ __('Partial') }}</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ __('Due') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2 mt-2 pt-2 border-top small">
                                        <div class="col-4">
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ __('Expected') }}</div>
                                            <div class="fw-bold text-dark">৳{{ number_format($row->expected, 0) }}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ __('Collected') }}</div>
                                            <div class="fw-bold text-success">৳{{ number_format($row->collected, 0) }}</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ __('Balance') }}</div>
                                            <div class="fw-bold text-primary">৳{{ number_format($row->current_balance, 0) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-3 pt-2 border-top">
                                    @if ($mem->mobile)
                                        <a href="tel:{{ $mem->mobile }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="{{ __('Call Member') }}">
                                            <i class="bi bi-telephone-fill"></i>
                                        </a>
                                    @endif

                                    @if (! $isPaid)
                                        <button type="button" class="btn btn-primary btn-sm flex-grow-1 rounded-pill fw-bold fo-open-collect-modal"
                                            data-account-id="{{ $acc->id }}"
                                            data-account-no="{{ $acc->account_no }}"
                                            data-member-name="{{ $mem->name }}"
                                            data-expected="{{ $row->expected }}"
                                            data-due="{{ $row->due > 0 ? $row->due : $row->expected }}"
                                            data-balance="{{ $row->current_balance }}">
                                            <i class="bi bi-plus-circle me-1"></i>{{ __('Collect ৳:amt', ['amt' => number_format($row->due > 0 ? $row->due : $row->expected, 0)]) }}
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-success btn-sm flex-grow-1 rounded-pill fw-bold fo-open-collect-modal"
                                            data-account-id="{{ $acc->id }}"
                                            data-account-no="{{ $acc->account_no }}"
                                            data-member-name="{{ $mem->name }}"
                                            data-expected="{{ $row->expected }}"
                                            data-due="{{ $row->expected }}"
                                            data-balance="{{ $row->current_balance }}">
                                            <i class="bi bi-check2-circle me-1"></i>{{ __('Add More') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card shadow-sm border-0 text-center py-5">
                                <div class="card-body">
                                    <i class="bi bi-inbox text-muted display-4 d-block mb-2"></i>
                                    <h6 class="fw-bold text-dark">{{ __('No savings accounts found for this frequency.') }}</h6>
                                    <p class="text-muted small mb-0">{{ __('Make sure accounts exist and are assigned to your areas.') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div id="noSearchResults" class="card shadow-sm border-0 text-center py-5" style="display:none;">
                <div class="card-body">
                    <i class="bi bi-search text-muted display-6 d-block mb-2"></i>
                    <h6 class="fw-bold text-dark">{{ __('No matching members found') }}</h6>
                    <p class="text-muted small mb-0">{{ __('Try checking spelling or clearing the search filter.') }}</p>
                </div>
            </div>
        </div>

        {{-- TAB 2: TODAY'S COLLECTED TRANSACTIONS --}}
        <div class="tab-pane fade" id="tab-txns">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 90px;">{{ __('Time') }}</th>
                                    <th>{{ __('Receipt No') }}</th>
                                    <th>{{ __('Member') }}</th>
                                    <th>{{ __('Account No') }}</th>
                                    <th class="text-end">{{ __('Amount') }}</th>
                                    <th>{{ __('Method') }}</th>
                                    <th>{{ __('Notes') }}</th>
                                    <th class="text-end pe-3">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $txn)
                                    <tr>
                                        <td class="ps-3 text-muted small">
                                            <i class="bi bi-clock me-1"></i>{{ $txn->created_at->format('h:i A') }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border fw-medium">{{ $txn->txn_no }}</span>
                                        </td>
                                        <td class="fw-bold text-dark">{{ $txn->account->member->name }}</td>
                                        <td class="text-primary font-monospace">{{ $txn->account->account_no }}</td>
                                        <td class="text-end fw-bold text-success">৳{{ number_format($txn->amount, 2) }}</td>
                                        <td><span class="badge bg-success-subtle text-success">{{ ucfirst($txn->payment_method) }}</span></td>
                                        <td class="text-muted small">{{ $txn->notes ?? '—' }}</td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end align-items-center gap-1">
                                                <a href="{{ route('savings.receipts.show', ['type' => 'savings', 'id' => $txn->id]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1" title="{{ __('Receipt') }}">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                                @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 edit-savings-txn-btn" data-bs-toggle="modal" data-bs-target="#editSavingsTxnModal"
                                                        data-action="{{ route('collection.savings.transactions.update', $txn) }}"
                                                        data-txn-no="{{ $txn->txn_no }}"
                                                        data-amount="{{ $txn->amount }}"
                                                        data-date="{{ $txn->txn_date->toDateString() }}"
                                                        data-notes="{{ $txn->notes }}" title="{{ __('Edit') }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('collection.savings.transactions.destroy', $txn) }}" class="d-inline" onsubmit="return confirm('{{ __('Delete collection :no? The deposit will be permanently removed, the account balance restored, and the linked cash entry removed.', ['no' => $txn->txn_no]) }}');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-1" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bi bi-receipt text-muted display-6 d-block mb-2"></i>
                                            <h6 class="fw-bold text-dark mb-1">{{ __('No deposits collected yet today.') }}</h6>
                                            <p class="small mb-0">{{ __('Use the Members Due tab to record collections.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TOUCH-FRIENDLY INSTANT COLLECTION MODAL --}}
<div class="modal fade" id="foCollectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white">
                <div>
                    <h5 class="modal-title fw-bold" id="foModalTitle">{{ __('Deposit Collection') }}</h5>
                    <div class="text-white-50 small" id="foModalSubtitle"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('collection.savings.store', $frequency) }}" id="foDepositForm">
                @csrf
                <input type="hidden" name="account_id" id="modalAccountId">
                <input type="hidden" name="txn_date" value="{{ $date }}">
                <input type="hidden" name="field_officer_id" value="{{ $officer->id }}">

                <div class="modal-body p-4">
                    {{-- Quick Member Summary Pill --}}
                    <div class="bg-light p-3 rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">{{ __('Member') }}:</span>
                            <span class="fw-bold text-dark" id="modalMemberName"></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">{{ __('Account No') }}:</span>
                            <span class="fw-bold text-primary font-monospace" id="modalAccountNo"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">{{ __('Current Balance') }}:</span>
                            <span class="fw-bold text-success" id="modalCurrentBalance"></span>
                        </div>
                    </div>

                    {{-- Amount Input Field --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark fs-6">{{ __('Collection Amount (৳)') }} <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white fw-bold">৳</span>
                            <input type="number" step="0.01" min="1" name="amount" id="modalAmountInput" class="form-control form-control-lg fw-bold text-success" placeholder="0.00" required autofocus>
                        </div>
                    </div>

                    {{-- Quick Amount Increments --}}
                    <div class="mb-3">
                        <div class="text-muted small mb-1 fw-semibold">{{ __('Quick Select Amount') }}:</div>
                        <div class="d-flex flex-wrap gap-2" id="quickAmountPills">
                            <span class="fo-amount-pill" id="pillExactExpected">{{ __('Expected') }}</span>
                            <span class="fo-amount-pill" onclick="setQuickAmount(50)">+50</span>
                            <span class="fo-amount-pill" onclick="setQuickAmount(100)">+100</span>
                            <span class="fo-amount-pill" onclick="setQuickAmount(200)">+200</span>
                            <span class="fo-amount-pill" onclick="setQuickAmount(500)">+500</span>
                            <span class="fo-amount-pill" onclick="setQuickAmount(1000)">+1000</span>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">{{ __('Payment Method') }}</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash" selected>💵 {{ __('Cash') }}</option>
                            <option value="bkash">📱 {{ __('bKash') }}</option>
                            <option value="nagad">📱 {{ __('Nagad') }}</option>
                            <option value="bank">🏦 {{ __('Bank') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </select>
                    </div>

                    {{-- Note --}}
                    <div class="mb-2">
                        <label class="form-label fw-semibold small text-muted">{{ __('Note (Optional)') }}</label>
                        <input type="text" name="notes" class="form-control" placeholder="{{ __('Optional installment remarks...') }}">
                    </div>
                </div>

                <div class="modal-footer p-3 bg-light border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold flex-grow-1 fo-quick-btn" id="modalSubmitBtn">
                        <i class="bi bi-check-circle-fill me-1"></i>{{ __('Confirm Deposit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let expectedAmount = 0;
    let currentFilter = 'all';
    let currentView = localStorage.getItem('fo_savings_view') || (window.innerWidth >= 768 ? 'table' : 'grid');

    const searchInput = document.getElementById('memberSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const chips = document.querySelectorAll('.fo-chip');
    const noResults = document.getElementById('noSearchResults');

    function setViewMode(mode) {
        currentView = mode;
        localStorage.setItem('fo_savings_view', mode);

        const tableView = document.getElementById('savingsTableView');
        const gridView = document.getElementById('savingsGridView');
        const btnTable = document.getElementById('btnViewTable');
        const btnGrid = document.getElementById('btnViewGrid');

        if (mode === 'table') {
            if (tableView) tableView.classList.remove('d-none');
            if (gridView) gridView.classList.add('d-none');
            btnTable?.classList.add('active', 'btn-primary');
            btnTable?.classList.remove('btn-outline-secondary');
            btnGrid?.classList.remove('active', 'btn-primary');
            btnGrid?.classList.add('btn-outline-secondary');
        } else {
            if (tableView) tableView.classList.add('d-none');
            if (gridView) gridView.classList.remove('d-none');
            btnGrid?.classList.add('active', 'btn-primary');
            btnGrid?.classList.remove('btn-outline-secondary');
            btnTable?.classList.remove('active', 'btn-primary');
            btnTable?.classList.add('btn-outline-secondary');
        }
        filterMembers();
    }

    function filterMembers() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;

        if (clearBtn) {
            clearBtn.style.display = query ? 'block' : 'none';
        }

        const activeContainer = currentView === 'table' 
            ? document.getElementById('savingsTableView') 
            : document.getElementById('savingsGridView');

        const items = document.querySelectorAll('.member-item');

        items.forEach(el => {
            const name = el.getAttribute('data-name') || '';
            const account = el.getAttribute('data-account') || '';
            const mobile = el.getAttribute('data-mobile') || '';
            const area = el.getAttribute('data-area') || '';
            const status = el.getAttribute('data-status') || '';

            const matchesQuery = !query || name.includes(query) || account.includes(query) || mobile.includes(query) || area.includes(query);
            let matchesFilter = true;

            if (currentFilter === 'due') matchesFilter = (status === 'due' || status === 'partial');
            if (currentFilter === 'paid') matchesFilter = (status === 'paid');

            const isMatch = matchesQuery && matchesFilter;
            el.style.display = isMatch ? '' : 'none';

            if (isMatch && activeContainer && activeContainer.contains(el)) {
                visibleCount++;
            }
        });

        if (noResults) {
            noResults.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
        }
    }

    searchInput?.addEventListener('input', filterMembers);
    clearBtn?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        filterMembers();
    });

    chips.forEach(chip => {
        chip.addEventListener('click', function () {
            chips.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter') || 'all';
            filterMembers();
        });
    });

    // Instant Collect Modal Handler (works on both Table & Grid buttons)
    document.querySelectorAll('.fo-open-collect-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const accId = this.getAttribute('data-account-id');
            const accNo = this.getAttribute('data-account-no');
            const memName = this.getAttribute('data-member-name');
            const expected = parseFloat(this.getAttribute('data-expected')) || 0;
            const due = parseFloat(this.getAttribute('data-due')) || expected;
            const balance = parseFloat(this.getAttribute('data-balance')) || 0;

            expectedAmount = due;

            document.getElementById('modalAccountId').value = accId;
            document.getElementById('modalAccountNo').textContent = accNo;
            document.getElementById('modalMemberName').textContent = memName;
            document.getElementById('modalCurrentBalance').textContent = '৳' + balance.toLocaleString();
            document.getElementById('modalAmountInput').value = due > 0 ? due : expected;
            document.getElementById('foModalSubtitle').textContent = accNo + ' — ' + memName;

            const exactPill = document.getElementById('pillExactExpected');
            if (exactPill) {
                exactPill.textContent = '{{ __("Exact Due") }}: ৳' + due.toLocaleString();
                exactPill.onclick = () => { document.getElementById('modalAmountInput').value = due; };
            }

            const modal = new bootstrap.Modal(document.getElementById('foCollectModal'));
            modal.show();
        });
    });

    function setQuickAmount(extra) {
        const input = document.getElementById('modalAmountInput');
        const current = parseFloat(input.value) || 0;
        input.value = (current + extra);
    }

    // Initialize View Mode on Load
    setViewMode(currentView);

    // Edit Savings Transaction Modal Handler
    const editSavingsForm = document.getElementById('editSavingsTxnForm');
    if (editSavingsForm) {
        document.querySelectorAll('.edit-savings-txn-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                editSavingsForm.action = this.dataset.action;
                document.getElementById('editSavingsTxnNo').value = this.dataset.txnNo;
                document.getElementById('editSavingsTxnAmount').value = this.dataset.amount;
                document.getElementById('editSavingsTxnDate').value = this.dataset.date;
                document.getElementById('editSavingsTxnNotes').value = this.dataset.notes || '';
            });
        });
    }
</script>
@endpush

{{-- EDIT SAVINGS COLLECTION MODAL --}}
<div class="modal fade" id="editSavingsTxnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editSavingsTxnForm">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ __('Edit Savings Collection') }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">{{ __('Transaction No') }}</label>
                        <input type="text" class="form-control bg-light font-monospace" id="editSavingsTxnNo" disabled>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold">{{ __('Amount') }} (৳)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control fw-bold fs-5 text-success" id="editSavingsTxnAmount" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small fw-semibold">{{ __('Date') }}</label>
                            <input type="date" name="txn_date" class="form-control" id="editSavingsTxnDate" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label text-muted small fw-semibold">{{ __('Note') }}</label>
                        <textarea name="notes" class="form-control" id="editSavingsTxnNotes" rows="2" placeholder="{{ __('Optional remarks...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-check-lg me-1"></i>{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
