@extends('layouts.app')

@section('title', __('Officer Report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Officer Report') }}</h4>
    <a href="{{ route('reports.export', array_merge(request()->query(), ['report' => 'officers'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export CSV') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Officer') }}</th><th>{{ __('Email') }}</th><th>{{ __('Area') }}</th><th class="text-end">{{ __('Members') }}</th><th class="text-end">{{ __('Savings Collected') }}</th><th class="text-end">{{ __('Loan Collected') }}</th><th class="text-end">{{ __('Outstanding') }}</th><th class="text-end">{{ __('Due Today') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($officers as $officer)
                    <tr>
                        <td class="fw-semibold">{{ $officer->name }}</td>
                        <td>{{ $officer->email }}</td>
                        <td>{{ $officer->areas->pluck('name')->implode(', ') }}</td>
                        <td class="amount">{{ $officer->members_count }}</td>
                        <td class="amount text-success">৳{{ number_format($officer->savings_collected, 2) }}</td>
                        <td class="amount text-primary">৳{{ number_format($officer->loan_collected, 2) }}</td>
                        <td class="amount fw-semibold">৳{{ number_format($officer->outstanding, 2) }}</td>
                        <td class="amount text-danger">৳{{ number_format($officer->due_today, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No officers found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection