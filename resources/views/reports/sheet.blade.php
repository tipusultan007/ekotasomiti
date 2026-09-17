@extends('layouts.app')

@section('title', __('Monthly Sheet'))

@push('styles')
<style>
    @media print {
        .sheet-scroll { max-height: none !important; overflow: visible !important; }
        .sheet-table { min-width: 0 !important; width: 100% !important; font-size: 6px !important; }
        .sheet-table th, .sheet-table td { padding: 1px 2px !important; font-size: 6px !important; line-height: 1.1 !important; }
        .sheet-table thead th { font-size: 6px !important; font-weight: bold; white-space: nowrap !important; }
        .sheet-table th.num, .sheet-table td.num { width: 22px !important; }
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
    <h4 class="mb-0">{{ __('Monthly Sheet') }}</h4>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
        <a href="{{ route('reports.sheet.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>{{ __('Download PDF') }}</a>
    </div>
</div>

<div class="report-header mb-3 text-center">
    <h4 class="mb-0 fw-bold">{{ $orgName }}</h4>
    @if ($orgAddress)
        <div class="text-muted small">{{ $orgAddress }}</div>
    @endif
    @if ($orgPhone)
        <div class="text-muted small">{{ __('Phone') }}: {{ $orgPhone }}</div>
    @endif
    <hr class="my-2">
    <div class="d-flex justify-content-between flex-wrap gap-2">
        <strong>{{ __('Monthly Sheet') }}</strong>
        <span class="text-muted small">{{ __('Month') }}: {{ $monthLabel }} &nbsp;|&nbsp; {{ __('Type') }}: {{ __(ucfirst($type)) }} &nbsp;|&nbsp; {{ __('Frequency') }}: {{ __(ucfirst($frequency)) }}</span>
    </div>
</div>

<form class="row g-2 mb-3 no-print" method="GET">
    <div class="col-md-2"><label class="small text-muted">{{ __('Month') }}</label><input type="month" name="month" class="form-control" value="{{ $month }}"></div>
    <div class="col-md-2">
        <label class="small text-muted">{{ __('Type') }}</label>
        <select name="type" class="form-select">
            <option value="savings" @selected($type === 'savings')>{{ __('Savings') }}</option>
            <option value="loan" @selected($type === 'loan')>{{ __('Loan') }}</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="small text-muted">{{ __('Frequency') }}</label>
        <select name="frequency" class="form-select">
            <option value="daily" @selected($frequency === 'daily')>{{ __('Daily') }}</option>
            <option value="weekly" @selected($frequency === 'weekly')>{{ __('Weekly') }}</option>
            <option value="monthly" @selected($frequency === 'monthly')>{{ __('Monthly') }}</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="small text-muted">{{ __('Area') }}</label>
        <select name="area_id" class="form-select">
            <option value="">{{ __('All Areas') }}</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected($areaId == $area->id)>{{ $area->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="small text-muted">{{ __('Field Officer') }}</label>
        <select name="officer_id" class="form-select">
            <option value="">{{ __('All Officers') }}</option>
            @foreach ($officers as $officer)
                <option value="{{ $officer->id }}" @selected($officerId == $officer->id)>{{ $officer->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary btn-sm">{{ __('Filter') }}</button></div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive sheet-scroll" style="max-height:75vh; overflow:auto;">
            <table class="table table-sm table-bordered mb-0 align-middle sheet-table" style="font-size:0.8rem; min-width: max-content;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="text-center">#</th>
                        <th>{{ __('Member Details') }}</th>
                        <th>{{ __('Account No') }}</th>
                        @for ($d = 1; $d <= $daysInMonth; $d++)
                            <th class="text-end">{{ $d }}</th>
                        @endfor
                        <th class="text-end bg-light">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $index => $row)
                        <tr>
                            <td class="text-center text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $row->member_name }}</div>
                                <div class="text-muted small">{{ $row->member_phone }}</div>
                            </td>
                            <td>{{ $row->account_no }}</td>
                            @for ($d = 1; $d <= $daysInMonth; $d++)
                                <td class="text-end">{{ $row->cells[$d] > 0 ? number_format($row->cells[$d], 0) : '' }}</td>
                            @endfor
                            <td class="text-end fw-bold bg-light">{{ number_format($row->row_total, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $daysInMonth + 4 }}" class="text-center text-muted py-4">{{ __('No accounts found.') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td></td>
                        <td colspan="2" class="fw-bold">{{ __('Total') }}</td>
                        @for ($d = 1; $d <= $daysInMonth; $d++)
                            <td class="text-end fw-semibold">{{ $columnTotals[$d] > 0 ? number_format($columnTotals[$d], 0) : '' }}</td>
                        @endfor
                        <td class="text-end fw-bold bg-light">{{ number_format($grandTotal, 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
