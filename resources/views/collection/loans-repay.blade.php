@extends('layouts.app')

@section('title', __('Repay') . ' — ' . __(ucfirst($frequency)) . ' ' . __('Loans'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>{{ __(ucfirst($frequency)) }} {{ __('Loan Collection') }}</h4>
    <div>
        <a href="{{ route('collection.loans.sheet', $frequency) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-ul me-1"></i>{{ __('Collection List') }}</a>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    @foreach (['daily', 'weekly', 'monthly'] as $f)
        <li class="nav-item">
            <a class="nav-link {{ $f === $frequency ? 'active' : '' }}" href="{{ route('collection.loans.repay', $f) }}">
                {{ __(ucfirst($f)) }} {{ __('Loans') }}
            </a>
        </li>
    @endforeach
</ul>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@include('collection._loans-repay-form', ['frequency' => $frequency, 'date' => $date, 'officer' => $officer, 'loanOptions' => $loanOptions, 'selectedLoan' => $selectedLoan])
@endsection