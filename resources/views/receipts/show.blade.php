@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ $title }}</h4>
    <div>
        <a href="{{ route('savings.receipts.pdf', ['type' => request()->route('type'), 'id' => request()->route('id')]) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>{{ __('PDF') }}</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
    </div>
</div>

<div class="card shadow-sm" style="max-width: 640px;">
    <div class="card-body p-4">
        <div class="text-center border-bottom pb-3 mb-3">
            <h5 class="mb-1">{{ $orgName }}</h5>
            <div class="text-muted small">{{ $orgAddress }}</div>
            <div class="text-muted small">{{ __('Phone') }}: {{ $orgPhone }}</div>
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
            <tr><td>{{ __('Program') }}</td><td>{{ $program }}</td></tr>
            @if (!empty($fundAmount) && $fundAmount > 0)
                <tr><td>{{ __('Total Paid') }}</td><td class="fw-bold text-primary">৳{{ number_format($grossAmount, 2) }}</td></tr>
                <tr><td>{{ __('Savings Deposit') }}</td><td class="fw-semibold text-success">৳{{ number_format($amount, 2) }}</td></tr>
                <tr><td>{{ $fundName }}</td><td class="fw-semibold text-info">৳{{ number_format($fundAmount, 2) }}</td></tr>
            @else
                <tr><td>{{ __($typeLabel) }} {{ __('Amount') }}</td><td class="fw-bold text-success">৳{{ number_format($amount, 2) }}</td></tr>
            @endif
            @if (!empty($principal))
                <tr><td>{{ __('Principal Paid') }}</td><td>৳{{ number_format($principal, 2) }}</td></tr>
                <tr><td>{{ __('Interest Paid') }}</td><td>৳{{ number_format($interest, 2) }}</td></tr>
                @if ($lateFee > 0)
                    <tr><td>{{ __('Late Fee') }}</td><td>৳{{ number_format($lateFee, 2) }}</td></tr>
                @endif
            @endif
            <tr><td>{{ __('Payment Method') }}</td><td>{{ __(ucfirst($paymentMethod)) }}</td></tr>
            <tr><td>{{ __('Field Officer') }}</td><td>{{ $fieldOfficer ?? '—' }}</td></tr>
            <tr><td>{{ __('Received By') }}</td><td>{{ $receivedBy }}</td></tr>
            <tr><td>{{ __('Previous Balance') }}</td><td>৳{{ number_format($previousBalance, 2) }}</td></tr>
            <tr><td>{{ __('Balance After') }}</td><td class="fw-bold">৳{{ number_format($balanceAfter, 2) }}</td></tr>
            @if (!empty($reference))
                <tr><td>{{ __('Reference') }}</td><td>{{ $reference }}</td></tr>
            @endif
        </table>

        @if (!empty($notes))
            <div class="alert alert-light border small py-2">{{ __('Note') }}: {{ $notes }}</div>
        @endif

        <div class="d-flex justify-content-end mt-4">
            <div class="text-center" style="width:180px;">
                <div class="border-top pt-1 small">{{ __('Authorized Signature') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection