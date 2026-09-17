@extends('layouts.app')

@section('title', __('Collection Report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Collection Report') }}</h4>
    <a href="{{ route('reports.export', array_merge(request()->query(), ['report' => 'collections'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export CSV') }}</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Savings Collected') }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($summary['savings'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Loan Collected') }}</div>
            <div class="fs-5 fw-bold text-primary">৳{{ number_format($summary['loan_principal'] + $summary['loan_interest'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Late Fees') }}</div>
            <div class="fs-5 fw-bold text-warning">৳{{ number_format($summary['late_fees'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Total Collected') }}</div>
            <div class="fs-5 fw-bold">৳{{ number_format($summary['total'], 2) }}</div>
        </div></div>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-2">
        <select name="type" class="form-select">
            <option value="">{{ __('All Types') }}</option>
            <option value="savings" @selected(request('type') === 'savings')>{{ __('Savings') }}</option>
            <option value="loan" @selected(request('type') === 'loan')>{{ __('Loan') }}</option>
        </select>
    </div>
    <div class="col-md-2">
        <select name="area_id" class="form-select">
            <option value="">{{ __('All Areas') }}</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="officer_id" class="form-select">
            <option value="">{{ __('All Officers') }}</option>
            @foreach ($officers as $officer)
                <option value="{{ $officer->id }}" @selected(request('officer_id') == $officer->id)>{{ $officer->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">{{ __('Filter') }}</button></div>
</form>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">{{ __('Collections') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th>{{ __('Member') }}</th><th>{{ __('Reference') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Area') }}</th><th>{{ __('Officer') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($collections as $row)
                    <tr>
                        <td>{{ $row->date?->format('d-m-Y') }}</td>
                        <td>{{ __(ucfirst($row->type)) }}</td>
                        <td>{{ $row->member?->name }}</td>
                        <td>{{ $row->reference }}</td>
                        <td class="amount fw-semibold">৳{{ number_format($row->amount, 2) }}</td>
                        <td>{{ $row->area?->name }}</td>
                        <td>{{ $row->officer?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No collections found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection