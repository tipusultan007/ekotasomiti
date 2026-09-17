@extends('layouts.app')

@section('title', __('Reports'))

@section('content')
<h4 class="mb-4">{{ __('Reports') }}</h4>

<div class="row g-3">
    <div class="col-md-3">
        <a href="{{ route('reports.sheet') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-info bg-opacity-10 text-info rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-calendar3"></i></div>
                <h6 class="text-dark mb-0">{{ __('Monthly Sheet') }}</h6>
                <div class="text-muted small">{{ __('Member-wise daily collection matrix') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('reports.summary') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-success bg-opacity-10 text-success rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-bar-chart"></i></div>
                <h6 class="text-dark mb-0">{{ __('Financial Summary') }}</h6>
                <div class="text-muted small">{{ __('Savings & loan totals for a date range') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('reports.members') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-primary bg-opacity-10 text-primary rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-people"></i></div>
                <h6 class="text-dark mb-0">{{ __('Member Reports') }}</h6>
                <div class="text-muted small">{{ __('Member list, new members, status, statements') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('reports.savings') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-success bg-opacity-10 text-success rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-piggy-bank"></i></div>
                <h6 class="text-dark mb-0">{{ __('Savings Reports') }}</h6>
                <div class="text-muted small">{{ __('Collections, deposits, withdrawals, balances') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('reports.loans') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-warning bg-opacity-10 text-warning rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-credit-card"></i></div>
                <h6 class="text-dark mb-0">{{ __('Loan Reports') }}</h6>
                <div class="text-muted small">{{ __('Active, overdue, outstanding loans') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('reports.collections') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-info bg-opacity-10 text-info rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-cash-coin"></i></div>
                <h6 class="text-dark mb-0">{{ __('Collection Reports') }}</h6>
                <div class="text-muted small">{{ __('Daily savings & loan collections') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('reports.cash') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-danger bg-opacity-10 text-danger rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-safe"></i></div>
                <h6 class="text-dark mb-0">{{ __('Cash Reports') }}</h6>
                <div class="text-muted small">{{ __('Cash register, transactions, expenses') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('reports.areas') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-secondary bg-opacity-10 text-secondary rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-geo-alt"></i></div>
                <h6 class="text-dark mb-0">{{ __('Area Reports') }}</h6>
                <div class="text-muted small">{{ __('Area-wise members, savings, loans') }}</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('reports.officers') }}" class="card shadow-sm text-decoration-none h-100">
            <div class="card-body">
                <div class="icon bg-primary bg-opacity-10 text-primary rounded-3 mb-2" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="bi bi-person-badge"></i></div>
                <h6 class="text-dark mb-0">{{ __('Officer Reports') }}</h6>
                <div class="text-muted small">{{ __('Officer-wise collections and balances') }}</div>
            </div>
        </a>
    </div>
</div>
@endsection