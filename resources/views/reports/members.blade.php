@extends('layouts.app')

@section('title', __('Member Report'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Member Report') }}</h4>
    <a href="{{ route('reports.export', array_merge(request()->query(), ['report' => 'members'])) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>{{ __('Export CSV') }}</a>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">{{ __('All Status') }}</option>
            @foreach (['active', 'inactive', 'suspended', 'closed'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst($status)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="area_id" class="form-select">
            <option value="">{{ __('All Areas') }}</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="From"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" title="To"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary">{{ __('Filter') }}</button></div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Member No') }}</th><th>{{ __('Name') }}</th><th>{{ __('Mobile') }}</th><th>{{ __('Area') }}</th><th>{{ __('Officer') }}</th><th>{{ __('Membership') }}</th><th class="text-end">{{ __('Savings Balance') }}</th><th>{{ __('Status') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>{{ $member->member_no }}</td>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->mobile }}</td>
                        <td>{{ $member->area?->name }}</td>
                        <td>{{ $member->fieldOfficer?->name }}</td>
                        <td>{{ $member->membership_date?->format('d-m-Y') }}</td>
                        <td class="amount">৳{{ number_format($member->total_savings ?? 0, 2) }}</td>
                        <td><span class="badge bg-{{ $member->status === 'active' ? 'success-subtle text-success' : 'secondary-subtle text-secondary' }}">{{ __(ucfirst($member->status)) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('No members found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection