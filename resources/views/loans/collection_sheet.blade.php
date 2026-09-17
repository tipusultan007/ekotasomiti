@extends('layouts.app')

@section('title', __('Collection Sheet - :no', ['no' => $loan->loan_no]))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Loan Collection Sheet') }}</h4>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="row">
            <div class="col-md-3"><div class="text-muted small">{{ __('Member') }}</div><div class="fw-semibold">{{ $loan->member->name }}</div></div>
            <div class="col-md-3"><div class="text-muted small">{{ __('Loan No') }}</div><div class="fw-semibold">{{ $loan->loan_no }}</div></div>
            <div class="col-md-3"><div class="text-muted small">{{ __('Product') }}</div><div>{{ $loan->product->name }}</div></div>
            <div class="col-md-3"><div class="text-muted small">{{ __('Officer') }}</div><div>{{ $loan->fieldOfficer?->name }}</div></div>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>#</th><th>{{ __('Due Date') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Paid') }}</th><th>{{ __('Status') }}</th><th>{{ __('Signature') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($loan->schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->installment_no }}</td>
                        <td>{{ $schedule->due_date->format('d-m-Y') }}</td>
                        <td class="amount">৳{{ number_format($schedule->total, 2) }}</td>
                        <td class="amount">৳{{ number_format($schedule->paid, 2) }}</td>
                        <td>{{ __(ucfirst($schedule->status)) }}</td>
                        <td style="width:120px;"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection