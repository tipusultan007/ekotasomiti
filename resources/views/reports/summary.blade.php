@extends('layouts.app')

@section('title', __('Financial Summary'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">{{ __('Financial Summary') }}</h4>
    <div class="no-print btn-group">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</button>
        <a href="{{ route('reports.summary.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-pdf me-1"></i>{{ __('Download PDF') }}</a>
    </div>
</div>

<p class="text-muted small mb-3">{{ __('Period') }}: {{ $dateFrom }} → {{ $dateTo }}</p>

<form class="row g-2 mb-3 no-print" method="GET">
    <div class="col-md-2"><label class="small text-muted">{{ __('From') }}</label><input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}"></div>
    <div class="col-md-2"><label class="small text-muted">{{ __('To') }}</label><input type="date" name="date_to" class="form-control" value="{{ $dateTo }}"></div>
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
    <div class="col-md-2">
        <label class="small text-muted">{{ __('Savings Program') }}</label>
        <select name="savings_program_id" class="form-select">
            <option value="">{{ __('All Programs') }}</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected($programId == $program->id)>{{ __($program->name) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="small text-muted">{{ __('Loan Product') }}</label>
        <select name="loan_product_id" class="form-select">
            <option value="">{{ __('All Products') }}</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected($productId == $product->id)>{{ __($product->name) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12"><button class="btn btn-outline-primary btn-sm">{{ __('Filter') }}</button></div>
</form>

@include('reports.partials.summary_body')
@endsection
