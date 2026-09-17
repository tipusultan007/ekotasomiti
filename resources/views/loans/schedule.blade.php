@extends('layouts.app')

@section('title', __('Schedule - :no', ['no' => $loan->loan_no]))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Repayment Schedule - :no', ['no' => $loan->loan_no]) }}</h4>
    <div>
        <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>{{ __('Back') }}</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        {{ $loan->member->name }} ({{ $loan->member->member_no }}) — {{ $loan->product->name }} | {{ __('Installment') }}: ৳{{ number_format($loan->installment_amount, 2) }}
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>#</th><th>{{ __('Due Date') }}</th><th class="text-end">{{ __('Principal') }}</th><th class="text-end">{{ __('Interest') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-end">{{ __('Paid') }}</th><th>{{ __('Status') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($loan->schedules as $schedule)
                    <tr class="{{ $schedule->status === 'overdue' ? 'table-danger' : ($schedule->status === 'paid' ? 'table-success' : '') }}">
                        <td>{{ $schedule->installment_no }}</td>
                        <td>{{ $schedule->due_date->format('d-m-Y') }}</td>
                        <td class="amount">৳{{ number_format($schedule->principal, 2) }}</td>
                        <td class="amount">৳{{ number_format($schedule->interest, 2) }}</td>
                        <td class="amount">৳{{ number_format($schedule->total, 2) }}</td>
                        <td class="amount">৳{{ number_format($schedule->paid, 2) }}</td>
                        <td><span class="badge {{ match($schedule->status) { 'paid' => 'bg-success-subtle text-success', 'partial' => 'bg-warning-subtle text-warning', 'overdue' => 'bg-danger-subtle text-danger', 'due' => 'bg-secondary-subtle text-secondary', 'waived' => 'bg-info-subtle text-info' } }}">{{ __(ucfirst($schedule->status)) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection