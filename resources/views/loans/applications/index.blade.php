@extends('layouts.app')

@section('title', __('Loan Applications'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Loan Applications') }}</h4>
    <a href="{{ route('loans.applications.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('New Application') }}</a>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-4">
        <select name="status" class="form-select">
            <option value="">{{ __('All Statuses') }}</option>
            @foreach (['approved', 'disbursed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucwords($status)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <input type="text" name="member" class="form-control" placeholder="{{ __('Member name') }}" value="{{ request('member') }}">
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>{{ __('Filter') }}</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('App No') }}</th><th>{{ __('Member') }}</th><th>{{ __('Product') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Installment') }}</th><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($applications as $application)
                    <tr>
                        <td class="fw-semibold">{{ $application->application_no }}</td>
                        <td>{{ $application->member->name }}</td>
                        <td>{{ $application->product->name }}</td>
                        <td class="amount">৳{{ number_format($application->approved_amount ?? $application->requested_amount, 2) }}</td>
                        <td class="amount">{{ $application->approved_installment ? '৳' . number_format($application->approved_installment, 2) : '—' }}</td>
                        <td>{{ $application->application_date->format('d-m-Y') }}</td>
                        <td>
                            <span class="badge {{ $application->status === 'disbursed' ? 'bg-dark text-white' : 'bg-success-subtle text-success' }}">
                                {{ __(ucwords(str_replace('_', ' ', $application->status))) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('loans.applications.show', $application) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No applications found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $applications->links() }}
@endsection