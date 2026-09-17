@extends('layouts.auth')

@section('title', $title)

@section('content')
<div class="d-flex align-items-center justify-content-center min-vh-100 px-3">
    <div class="card shadow-lg border-0" style="max-width: 460px; width: 100%;">
        <div class="card-body p-4">
            <div class="text-center border-bottom pb-3 mb-3">
                <h5 class="mb-1">{{ $orgName }}</h5>
                <div class="text-muted small">{{ $orgAddress }}</div>
            </div>

            <div class="d-flex justify-content-between mb-3">
                <div>
                    <div class="text-muted small">{{ __('Receipt No') }}</div>
                    <div class="fw-bold">{{ $receiptNo }}</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">{{ __('Date') }}</div>
                    <div class="fw-bold">{{ $date->format('d-m-Y') }}</div>
                </div>
            </div>

            <table class="table table-sm">
                <tr><td style="width:40%;">{{ __('Member') }}</td><td class="fw-semibold">{{ $member->name }} ({{ $member->member_no }})</td></tr>
                <tr><td>{{ __('Account No') }}</td><td class="fw-semibold">{{ $accountNo }}</td></tr>
                <tr><td>{{ __($typeLabel) }} {{ __('Amount') }}</td><td class="fw-bold text-success">৳{{ number_format($amount, 2) }}</td></tr>
                @if (!empty($fundAmount) && $fundAmount > 0)
                    <tr><td>{{ __('Total Paid') }}</td><td class="fw-bold text-primary">৳{{ number_format($grossAmount, 2) }}</td></tr>
                    <tr><td>{{ __('Savings Deposit') }}</td><td class="fw-semibold text-success">৳{{ number_format($amount, 2) }}</td></tr>
                    <tr><td>{{ $fundName }}</td><td class="fw-semibold text-info">৳{{ number_format($fundAmount, 2) }}</td></tr>
                @else
                    <tr><td>{{ __($typeLabel) }} {{ __('Amount') }}</td><td class="fw-bold text-success">৳{{ number_format($amount, 2) }}</td></tr>
                @endif
                <tr><td>{{ __('Balance After') }}</td><td class="fw-bold">৳{{ number_format($balanceAfter, 2) }}</td></tr>
                <tr><td>{{ __('Received By') }}</td><td>{{ $receivedBy }}</td></tr>
            </table>
        </div>
    </div>
</div>
@endsection