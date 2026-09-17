@extends('layouts.app')

@section('title', __('Field Officer Settlements'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Field Officer Settlements') }}</h4>
    @can('manage settlements')
        <a href="{{ route('collection.settlements.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('New Settlement') }}</a>
    @endcan
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Settlement No') }}</th><th>{{ __('Officer') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Savings') }}</th><th class="text-end">{{ __('Loan') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-end">{{ __('Submitted') }}</th><th class="text-end">{{ __('Remaining') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($settlements as $settlement)
                    <tr>
                        <td class="fw-semibold">{{ $settlement->settlement_no }}</td>
                        <td>{{ $settlement->officer->name }}</td>
                        <td>{{ $settlement->settlement_date->format('d-m-Y') }}</td>
                        <td class="amount">৳{{ number_format($settlement->savings_collection, 2) }}</td>
                        <td class="amount">৳{{ number_format($settlement->loan_collection, 2) }}</td>
                        <td class="amount fw-semibold">৳{{ number_format($settlement->total_collection, 2) }}</td>
                        <td class="amount text-success">৳{{ number_format($settlement->cash_submitted, 2) }}</td>
                        <td class="amount text-danger">৳{{ number_format($settlement->remaining_cash, 2) }}</td>
                        <td>
                            <span class="badge {{ match($settlement->status) { 'pending' => 'bg-secondary-subtle text-secondary', 'submitted' => 'bg-warning-subtle text-warning', 'received' => 'bg-success-subtle text-success', 'cancelled' => 'bg-danger-subtle text-danger' } }}">{{ __(ucfirst($settlement->status)) }}</span>
                        </td>
                        <td class="text-end">
                            @can('manage settlements')
                                @if ($settlement->status === 'submitted')
                                    <form method="POST" action="{{ route('collection.settlements.receive', $settlement) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success" title="{{ __('Receive cash') }}"><i class="bi bi-cash"></i></button>
                                    </form>
                                @endif
                                @if ($settlement->status !== 'received')
                                    <form method="POST" action="{{ route('collection.settlements.destroy', $settlement) }}" class="d-inline" data-confirm="{{ __('Delete this settlement?') }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">{{ __('No settlements found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $settlements->links() }}
@endsection