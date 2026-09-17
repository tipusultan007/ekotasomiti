@extends('layouts.app')

@section('title', __('Active Loans'))

@push('styles')
<style>
    .dropdown-toggle-no-caret::after {
        display: none !important;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Active Loans') }}</h4>
    <div class="d-flex gap-2">
        @can('manage loan applications')
            <a href="{{ route('loans.applications.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-text me-1"></i>{{ __('Applications') }}</a>
        @endcan
        <a href="{{ route('loans.applications.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('New Application') }}</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Active Loans') }}</div>
            <div class="fs-5 fw-bold">{{ $stats['totalDisbursed'] }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Total Outstanding') }}</div>
            <div class="fs-5 fw-bold text-danger">৳{{ number_format($stats['totalOutstanding'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Overdue Loans') }}</div>
            <div class="fs-5 fw-bold text-warning">{{ $stats['totalOverdue'] }}</div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="min-height: 220px;">
            <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>{{ __('Loan No') }}</th><th>{{ __('Member') }}</th><th>{{ __('Product') }}</th><th class="text-end">{{ __('Principal') }}</th><th class="text-end">{{ __('Outstanding') }}</th><th>{{ __('Due Date') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="fw-semibold">{{ $loan->loan_no }}</td>
                        <td><a href="{{ route('members.show', $loan->member) }}">{{ $loan->member->name }}</a></td>
                        <td>
                            @php
                                $freq = strtolower($loan->product->frequency ?? '');
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
                                <i class="bi {{ $badgeIcon }} me-1"></i>{{ __($loan->product->name) }}
                            </span>
                        </td>
                        <td class="amount">৳{{ number_format($loan->principal_amount, 2) }}</td>
                        <td class="amount fw-semibold {{ $loan->status === 'overdue' ? 'text-danger' : '' }}">৳{{ number_format($loan->outstanding, 2) }}</td>
                        <td>{{ $loan->schedules?->first(fn ($s) => in_array($s->status, ['due', 'partial', 'overdue']))?->due_date?->format('d-m-Y') }}</td>
                        <td><span class="badge {{ match($loan->status) { 'completed' => 'bg-success-subtle text-success', 'overdue' => 'bg-danger-subtle text-danger', 'active' => 'bg-info-subtle text-info', 'written_off' => 'bg-dark-subtle text-dark', default => 'bg-secondary-subtle text-secondary' } }}">{{ __(ucfirst($loan->status)) }}</span></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle dropdown-toggle-no-caret px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Actions') }}">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 180px; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1) !important;">
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('loans.show', $loan) }}">
                                            <i class="bi bi-eye text-primary"></i>
                                            <span>{{ __('View Details') }}</span>
                                        </a>
                                    </li>
                                    @if (in_array($loan->status, ['disbursed', 'active', 'overdue']))
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('loans.repay', $loan) }}">
                                                <i class="bi bi-cash-stack text-success"></i>
                                                <span>{{ __('Collect Repayment') }}</span>
                                            </a>
                                        </li>
                                    @endif
                                    @can('update', $loan)
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('loans.edit', $loan) }}">
                                                <i class="bi bi-pencil text-secondary"></i>
                                                <span>{{ __('Edit Loan') }}</span>
                                            </a>
                                        </li>
                                    @endcan
                                    @can('writeOff', $loan)
                                        @if (in_array($loan->status, ['disbursed', 'active', 'overdue']))
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('loans.show', $loan) }}#write-off">
                                                    <i class="bi bi-x-octagon text-warning"></i>
                                                    <span>{{ __('Write Off') }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endcan
                                    @can('delete', $loan)
                                        <li><hr class="dropdown-divider my-1"></li>
                                        <li>
                                            <form method="POST" action="{{ route('loans.destroy', $loan) }}"
                                                  data-confirm="{{ __('Delete loan :no permanently? This will remove all associated schedules, repayments, disbursements, and adjust cash books.', ['no' => $loan->loan_no]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger">
                                                    <i class="bi bi-trash"></i>
                                                    <span>{{ __('Delete Loan') }}</span>
                                                </button>
                                            </form>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No active loans found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>

{{ $loans->links() }}
@endsection