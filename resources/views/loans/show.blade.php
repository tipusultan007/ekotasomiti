@extends('layouts.app')

@section('title', __('Loan - :no', ['no' => $loan->loan_no]))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Loan :no', ['no' => $loan->loan_no]) }}</h4>
    <div class="d-flex gap-2">
        @if (in_array($loan->status, ['disbursed', 'active', 'overdue']))
            <a href="{{ route('loans.repay', $loan) }}" class="btn btn-success btn-sm"><i class="bi bi-cash-coin me-1"></i>{{ __('Collect Payment') }}</a>
        @endif
        <a href="{{ route('loans.schedule', $loan) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-range me-1"></i>{{ __('Schedule') }}</a>
        <a href="{{ route('loans.collection-sheet', $loan) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>{{ __('Collection Sheet') }}</a>
        @can('update', $loan)
            <a href="{{ route('loans.edit', $loan) }}" class="btn btn-outline-dark btn-sm"><i class="bi bi-pencil me-1"></i>{{ __('Edit Loan') }}</a>
        @endcan
        @can('delete', $loan)
            <form method="POST" action="{{ route('loans.destroy', $loan) }}" class="d-inline"
                  data-confirm="{{ __('Delete loan :no permanently? This will remove all associated schedules, repayments, disbursements, and adjust cash books.', ['no' => $loan->loan_no]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                </button>
            </form>
        @endcan
    </div>
</div>

@can('writeOff', $loan)
    @if (in_array($loan->status, ['disbursed', 'active', 'overdue']))
        <div class="card shadow-sm mb-3 border-danger" id="write-off">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-exclamation-octagon text-danger me-1"></i>{{ __('Write Off Loan') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('loans.write-off', $loan) }}" class="row g-3" data-confirm="{{ __('Write off this loan permanently? The outstanding amount will be recorded as a loss and the loan closed.') }}">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Write-off Amount') }}</label>
                        <input type="number" step="0.01" min="0.01" name="write_off_amount" class="form-control" value="{{ $loan->outstanding }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Reason') }}</label>
                        <input type="text" name="write_off_reason" class="form-control" placeholder="{{ __('e.g. Member defaulted, deceased, business failure') }}" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-danger w-100"><i class="bi bi-x-octagon me-1"></i>{{ __('Write Off') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan

<div class="row g-3">
    <div class="col-md-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ __('Loan Details') }}</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td style="width:40%;">{{ __('Member') }}</td><td class="fw-semibold"><a href="{{ route('members.show', $loan->member) }}">{{ $loan->member->name }}</a></td></tr>
                    <tr><td>{{ __('Product') }}</td><td>{{ $loan->product->name }}</td></tr>
                    <tr><td>{{ __('Principal') }}</td><td>৳{{ number_format($loan->principal_amount, 2) }}</td></tr>
                    <tr><td>{{ __('Interest Rate') }}</td><td>{{ $loan->interest_rate }}% ({{ $loan->interest_type }})</td></tr>
                    <tr><td>{{ __('Term / Frequency') }}</td><td>{{ $loan->term }} {{ $loan->frequency }}</td></tr>
                    <tr><td>{{ __('Installment') }}</td><td class="fw-bold">৳{{ number_format($loan->installment_amount, 2) }}</td></tr>
                    <tr><td>{{ __('Total Payable') }}</td><td>৳{{ number_format($loan->total_payable, 2) }}</td></tr>
                    <tr><td>{{ __('Total Paid') }}</td><td class="text-success">৳{{ number_format($loan->total_paid, 2) }}</td></tr>
                    <tr><td>{{ __('Outstanding') }}</td><td class="text-danger fw-bold">৳{{ number_format($loan->outstanding, 2) }}</td></tr>
                    <tr><td>{{ __('Disbursement') }}</td><td>{{ $loan->disbursement_date->format('d-m-Y') }}</td></tr>
                    <tr><td>{{ __('First Due') }}</td><td>{{ $loan->first_due_date?->format('d-m-Y') }}</td></tr>
                    <tr><td>{{ __('Area') }}</td><td>{{ $loan->area?->name }}</td></tr>
                    <tr><td>{{ __('Officer') }}</td><td>{{ $loan->fieldOfficer?->name }}</td></tr>
                    <tr><td>{{ __('Status') }}</td><td><span class="badge {{ match($loan->status) { 'completed' => 'bg-success', 'overdue' => 'bg-danger', 'active' => 'bg-info', default => 'bg-secondary' } }}">{{ __(ucfirst($loan->status)) }}</span></td></tr>
                    @if ($loan->written_off_at)
                        <tr><td>{{ __('Written Off') }}</td><td class="text-danger">৳{{ number_format($loan->written_off_amount ?? 0, 2) }} — {{ $loan->write_off_reason }}<br><small class="text-muted">{{ $loan->written_off_at->format('d-m-Y H:i') }}</small></td></tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">{{ __('Transactions') }}</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>{{ __('Ref') }}</th><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
                    <tbody>
                        @forelse ($loan->transactions->sortByDesc('txn_date') as $txn)
                            <tr>
                                <td><a href="{{ route('savings.receipts.show', ['type' => 'loan', 'id' => $txn->id]) }}">{{ $txn->txn_no }}</a></td>
                                <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                                <td>{{ __(ucfirst($txn->type)) }}</td>
                                <td class="amount {{ $txn->type === 'repayment' ? 'text-success' : 'text-danger' }}">৳{{ number_format($txn->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">{{ __('No transactions.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">{{ __('Repayment Schedule') }}</div>
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
    </div>
</div>
@endsection