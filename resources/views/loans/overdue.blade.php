@extends('layouts.app')

@section('title', __('Overdue Loans'))

@section('content')
<h4 class="mb-4">{{ __('Overdue Loans') }}</h4>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>{{ __('Loan No') }}</th><th>{{ __('Member') }}</th><th>{{ __('Product') }}</th><th class="text-end">{{ __('Outstanding') }}</th><th>{{ __('Officer') }}</th><th>{{ __('Last Due') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="fw-semibold">{{ $loan->loan_no }}</td>
                        <td>{{ $loan->member->name }}</td>
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
                        <td class="amount text-danger fw-bold">৳{{ number_format($loan->outstanding, 2) }}</td>
                        <td>{{ $loan->fieldOfficer?->name }}</td>
                        <td>{{ $loan->schedules?->first(fn ($s) => in_array($s->status, ['due', 'partial', 'overdue']))?->due_date?->format('d-m-Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('loans.repay', $loan) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-cash"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No overdue loans. Excellent!') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $loans->links() }}
@endsection