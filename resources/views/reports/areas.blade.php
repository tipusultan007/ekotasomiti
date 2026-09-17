@extends('layouts.app')

@section('title', __('Area Report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Area Report') }}</h4>
    <a href="{{ route('reports.export', array_merge(request()->query(), ['report' => 'areas'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export CSV') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Area') }}</th><th>{{ __('Code') }}</th><th class="text-end">{{ __('Members') }}</th><th class="text-end">{{ __('Savings Balance') }}</th><th class="text-end">{{ __('Loans Disbursed') }}</th><th class="text-end">{{ __('Outstanding') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($areas as $area)
                    <tr>
                        <td class="fw-semibold">{{ $area->name }}</td>
                        <td>{{ $area->code }}</td>
                        <td class="amount">{{ $area->members_count }}</td>
                        <td class="amount">৳{{ number_format($area->savings_balance, 2) }}</td>
                        <td class="amount">৳{{ number_format($area->loans_disbursed, 2) }}</td>
                        <td class="amount fw-semibold">৳{{ number_format($area->outstanding, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No areas found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection