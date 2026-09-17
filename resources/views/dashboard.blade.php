@extends('layouts.app')

@section('title', __('Dashboard'))

@push('styles')
<style>
    .dash-header {
        background: linear-gradient(135deg, #1e293b 0%, #1e3a8a 55%, #2563eb 100%);
        border-radius: 1rem;
        padding: 1.5rem 1.75rem;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .dash-header::after {
        content: '';
        position: absolute;
        right: -60px;
        top: -60px;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
    }
    .dash-header h4 { font-weight: 700; margin-bottom: .15rem; }
    .dash-header .sub { color: #cbd5e1; font-size: .9rem; }
    .dash-header .btn-light { color: #1e293b; font-weight: 600; }

    .kpi-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .06);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(15, 23, 42, .10); }
    .kpi-card .kpi-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; flex-shrink: 0;
    }
    .kpi-card .kpi-value { font-size: 1.55rem; font-weight: 700; line-height: 1.2; }
    .kpi-card .kpi-label { color: #64748b; font-size: .85rem; }

    .chart-card { border: none; border-radius: 1rem; box-shadow: 0 2px 10px rgba(15,23,42,.06); }
    .chart-card .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; border-radius: 1rem 1rem 0 0 !important; }

    .today-chip {
        border: none; border-radius: 1rem; box-shadow: 0 2px 8px rgba(15,23,42,.05);
        padding: .9rem 1.1rem;
    }
    .today-chip .chip-label { color: #64748b; font-size: .8rem; }
    .today-chip .chip-value { font-size: 1.15rem; font-weight: 700; }

    .chart-wrap { position: relative; height: 300px; }
    .chart-wrap.portrait { height: 260px; }
    .chart-wrap canvas { position: absolute; inset: 0; width: 100% !important; height: 100% !important; }

    .trans-table td, .trans-table th { vertical-align: middle; }
    .module-badge { font-weight: 600; font-size: .78rem; padding: .3em .6em; }

    .dash-section-title { font-size: 1.05rem; font-weight: 600; color: #0f172a; }
</style>
@endpush

@section('content')
@php $orgName = \App\Models\Setting::get('org_name', config('app.name')); @endphp

<div class="dash-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="mb-1">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</h4>
            <div class="sub"><i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('l, d F Y') }} — {{ $orgName }}</div>
        </div>
        @if (!($isOfficer ?? false))
            <div class="d-flex gap-2">
                <a href="{{ route('members.create') }}" class="btn btn-light btn-sm"><i class="bi bi-person-plus me-1"></i>{{ __('New Member') }}</a>
                <a href="{{ route('savings.deposits.create') }}" class="btn btn-success btn-sm"><i class="bi bi-cash-coin me-1"></i>{{ __('New Deposit') }}</a>
            </div>
        @endif
    </div>
</div>

@if ($isOfficer ?? false)
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="kpi-value">{{ $assignedMembers }}</div>
                        <div class="kpi-label">{{ __('Assigned Members') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-info bg-opacity-10 text-info"><i class="bi bi-clipboard-data"></i></div>
                    <div>
                        <div class="kpi-value">৳{{ number_format($expectedToday, 0) }}</div>
                        <div class="kpi-label">{{ __('Expected Today') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="kpi-value">৳{{ number_format($todayCollection, 0) }}</div>
                        <div class="kpi-label">{{ __('Today\'s Collection') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <div class="kpi-value">৳{{ number_format($cashInHand, 0) }}</div>
                        <div class="kpi-label">{{ __('Cash In Hand') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-piggy-bank"></i></div>
                    <div>
                        <div class="kpi-value">{{ $activeSavingsAccounts }}</div>
                        <div class="kpi-label">{{ __('Active Savings Accounts') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card chart-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="dash-section-title">{{ __('Collection Trend') }} — {{ __('Last 7 Days') }}</span>
                </div>
                <div class="card-body">
                    <div class="chart-wrap"><canvas id="officerTrendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card chart-card h-100">
                <div class="card-header"><span class="dash-section-title">{{ __('Today\'s Breakdown') }}</span></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td><span class="d-inline-block me-2" style="width:10px;height:10px;border-radius:50%;background:#0ea5e9;"></span>{{ __('Savings Collection') }}</td>
                            <td class="amount">৳{{ number_format($todaySavingsCollection, 2) }}</td>
                        </tr>
                        <tr>
                            <td><span class="d-inline-block me-2" style="width:10px;height:10px;border-radius:50%;background:#10b981;"></span>{{ __('Loan Collection') }}</td>
                            <td class="amount">৳{{ number_format($todayLoanCollection, 2) }}</td>
                        </tr>
                        <tr class="fw-bold border-top">
                            <td>{{ __('Total') }}</td>
                            <td class="amount">৳{{ number_format($todayCollection, 2) }}</td>
                        </tr>
                    </table>
                    @if ($pendingSettlement)
                        <div class="alert alert-info mb-0 mt-3">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ __('You have a pending settlement (:no).', ['no' => $pendingSettlement->settlement_no]) }}
                            <a href="{{ route('collection.settlements.index') }}" class="fw-semibold">{{ __('View') }}</a>
                        </div>
                    @else
                        <a href="{{ route('collection.settlements.create') }}" class="btn btn-primary btn-sm mt-3"><i class="bi bi-cash-coin me-1"></i>{{ __('Submit Today\'s Cash') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card chart-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="dash-section-title"><i class="bi bi-exclamation-triangle text-warning me-2"></i>{{ __('Overdue Loans') }}</span>
            <span class="badge bg-danger">{{ $overdueLoans->count() }}</span>
        </div>
        <div class="card-body p-0">
            @if ($overdueLoans->isEmpty())
                <p class="text-muted p-3 mb-0"><i class="bi bi-check-circle text-success me-1"></i>{{ __('No overdue loans. Great work!') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 trans-table">
                        <thead><tr><th>{{ __('Loan') }}</th><th>{{ __('Member') }}</th><th>{{ __('Due Date') }}</th><th class="text-end">{{ __('Outstanding') }}</th></tr></thead>
                        <tbody>
                            @foreach ($overdueLoans as $loan)
                                <tr>
                                    <td><a href="{{ route('loans.show', $loan) }}" class="fw-semibold text-decoration-none">{{ $loan->loan_no }}</a></td>
                                    <td>{{ $loan->member->name }}</td>
                                    <td>{{ optional($loan->schedules->firstWhere('status', 'overdue'))->due_date?->format('d-m-Y') ?: '—' }}</td>
                                    <td class="amount text-danger fw-semibold">৳{{ number_format($loan->outstanding, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="kpi-value">{{ $totalMembers }}</div>
                        <div class="kpi-label">{{ __('Total Members') }}</div>
                        <div class="text-success small fw-semibold mt-1"><i class="bi bi-arrow-up-right me-1"></i>{{ $activeMembers }} {{ __('active') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-success bg-opacity-10 text-success"><i class="bi bi-piggy-bank"></i></div>
                    <div>
                        <div class="kpi-value">৳{{ number_format($totalSavings, 0) }}</div>
                        <div class="kpi-label">{{ __('Total Savings') }}</div>
                        <div class="text-muted small mt-1"><i class="bi bi-calendar3 me-1"></i>{{ __('Today') }}: ৳{{ number_format($todaySavingsCollection, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-credit-card"></i></div>
                    <div>
                        <div class="kpi-value">৳{{ number_format($totalOutstanding, 0) }}</div>
                        <div class="kpi-label">{{ __('Outstanding Loans') }}</div>
                        <div class="text-muted small mt-1"><i class="bi bi-briefcase me-1"></i>{{ $activeLoans }} {{ __('active loans') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="kpi-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-safe"></i></div>
                    <div>
                        <div class="kpi-value">৳{{ number_format($cashBalance, 0) }}</div>
                        <div class="kpi-label">{{ __('Cash Balance') }}</div>
                        @if ($totalOverdue > 0)
                            <div class="text-danger small fw-semibold mt-1"><i class="bi bi-exclamation-triangle me-1"></i>{{ $totalOverdue }} {{ __('overdue loans') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card chart-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="dash-section-title">{{ __('Collection Trend') }} — {{ __('Last 7 Days') }}</span>
                </div>
                <div class="card-body">
                    <div class="chart-wrap"><canvas id="adminTrendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card chart-card h-100">
                <div class="card-header"><span class="dash-section-title">{{ __('Portfolio') }}</span></div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="chart-wrap portrait"><canvas id="portfolioChart"></canvas></div>
                    <div class="d-flex justify-content-center gap-4 mt-2">
                        <div>
                            <span class="d-inline-block me-2" style="width:10px;height:10px;border-radius:50%;background:#2563eb;"></span>
                            <span class="small">{{ __('Savings') }}</span>
                            <div class="fw-semibold text-end">৳{{ number_format($portfolio['savings'], 0) }}</div>
                        </div>
                        <div>
                            <span class="d-inline-block me-2" style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></span>
                            <span class="small">{{ __('Loans') }}</span>
                            <div class="fw-semibold text-end">৳{{ number_format($portfolio['loans'], 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="today-chip bg-success bg-opacity-10">
                <div class="chip-label">{{ __('Today\'s Savings Collection') }}</div>
                <div class="chip-value text-success">৳{{ number_format($todaySavingsCollection, 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="today-chip bg-success bg-opacity-10">
                <div class="chip-label">{{ __('Today\'s Loan Collection') }}</div>
                <div class="chip-value text-success">৳{{ number_format($todayLoanCollection, 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="today-chip bg-danger bg-opacity-10">
                <div class="chip-label">{{ __('Today\'s Disbursement') }}</div>
                <div class="chip-value text-danger">৳{{ number_format($todayDisbursement, 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="today-chip bg-danger bg-opacity-10">
                <div class="chip-label">{{ __('Today\'s Expenses') }}</div>
                <div class="chip-value text-danger">৳{{ number_format($todayExpense, 0) }}</div>
            </div>
        </div>
    </div>

    <div class="card chart-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="dash-section-title"><i class="bi bi-activity me-2 text-primary"></i>{{ __('Recent Transactions') }}</span>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Reports') }}</a>
        </div>
        <div class="card-body p-0">
            @if ($recentTransactions->isEmpty())
                <p class="text-muted p-3 mb-0">{{ __('No transactions yet.') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 trans-table">
                        <thead>
                            <tr>
                                <th>{{ __('Ref') }}</th><th>{{ __('Module') }}</th><th>{{ __('Type') }}</th><th>{{ __('Details') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentTransactions as $txn)
                                <tr>
                                    <td class="text-muted">{{ $txn->txn_ref }}</td>
                                    <td>
                                        <span class="badge module-badge {{ $txn->module === 'Savings' ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning' }}">
                                            {{ __($txn->module) }}
                                        </span>
                                    </td>
                                    <td>{{ __($txn->txn_type) }}</td>
                                    <td class="text-muted">{{ $txn->related }}</td>
                                    <td>{{ $txn->txn_date_val->format('d-m-Y') }}</td>
                                    <td class="amount fw-semibold">৳{{ number_format($txn->txn_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const t = {
            savings: '{{ __('Savings') }}',
            loans: '{{ __('Loans') }}'
        };
        const chartDefaults = {
            borderWidth: 2,
            borderRadius: 6,
            maxBarThickness: 28
        };

        @if ($isOfficer ?? false)
            new Chart(document.getElementById('officerTrendChart'), {
                type: 'bar',
                data: {
                    labels: @json($weekLabels),
                    datasets: [
                        {
                            label: t.savings,
                            data: @json($weekSavings),
                            backgroundColor: 'rgba(14,165,233,.75)',
                            ...chartDefaults
                        },
                        {
                            label: t.loans,
                            data: @json($weekLoans),
                            backgroundColor: 'rgba(16,185,129,.75)',
                            ...chartDefaults
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: v => '৳' + Number(v).toLocaleString() }
                        }
                    }
                }
            });
        @else
            new Chart(document.getElementById('adminTrendChart'), {
                type: 'bar',
                data: {
                    labels: @json($weekLabels),
                    datasets: [
                        {
                            label: t.savings,
                            data: @json($weekSavings),
                            backgroundColor: 'rgba(37,99,235,.75)',
                            ...chartDefaults
                        },
                        {
                            label: t.loans,
                            data: @json($weekLoans),
                            backgroundColor: 'rgba(16,185,129,.75)',
                            ...chartDefaults
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: v => '৳' + Number(v).toLocaleString() }
                        }
                    }
                }
            });

            new Chart(document.getElementById('portfolioChart'), {
                type: 'doughnut',
                data: {
                    labels: [t.savings, t.loans],
                    datasets: [{
                        data: [@json($portfolio['savings']), @json($portfolio['loans'])],
                        backgroundColor: ['#2563eb', '#f59e0b'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        @endif
    });
</script>
@endpush