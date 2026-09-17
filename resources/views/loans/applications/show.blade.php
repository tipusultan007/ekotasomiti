@extends('layouts.app')

@section('title', __('Application :no', ['no' => $application->application_no]))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Loan Application :no', ['no' => $application->application_no]) }}</h4>
    <div>
        <span class="badge fs-6 bg-success">
            {{ __(ucwords(str_replace('_', ' ', $application->status))) }}
        </span>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ __('Application Details') }}</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td style="width:35%;">{{ __('Member') }}</td><td class="fw-semibold">{{ $application->member->name }} ({{ $application->member->member_no }})</td></tr>
                    <tr><td>{{ __('Loan Product') }}</td><td>{{ $application->product->name }} ({{ $application->product->frequency }})</td></tr>
                    <tr><td>{{ __('Requested Amount') }}</td><td>৳{{ number_format($application->requested_amount, 2) }}</td></tr>
                    <tr><td>{{ __('Requested Term') }}</td><td>{{ $application->requested_term }}</td></tr>
                    <tr><td>{{ __('Installment Amount') }}</td><td class="fw-bold text-primary">৳{{ number_format($application->installment_amount ?? $application->approved_installment, 2) }}</td></tr>
                    <tr><td>{{ __('Purpose') }}</td><td>{{ $application->purpose }}</td></tr>
                    <tr><td>{{ __('Area') }}</td><td>{{ $application->area?->name }}</td></tr>
                    <tr><td>{{ __('Field Officer') }}</td><td>{{ $application->fieldOfficer?->name }}</td></tr>
                    <tr><td>{{ __('Application Date') }}</td><td>{{ $application->application_date->format('d-m-Y') }}</td></tr>
                    <tr><td>{{ __('Submitted By') }}</td><td>{{ $application->creator?->name }}</td></tr>
                    <tr><td>{{ __('Remarks') }}</td><td>{{ $application->remarks }}</td></tr>
                </table>
            </div>
        </div>

        @if ($application->guarantors->count())
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">{{ __('Guarantors') }}</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Relation') }}</th><th>{{ __('NID') }}</th><th>{{ __('Mobile') }}</th></tr></thead>
                        <tbody>
                            @foreach ($application->guarantors as $guarantor)
                                <tr><td>{{ $guarantor->name }}</td><td>{{ $guarantor->relationship }}</td><td>{{ $guarantor->nid }}</td><td>{{ $guarantor->mobile }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-5">
        @if ($application->status === 'disbursed')
            <div class="card shadow-sm mb-3 border-success">
                <div class="card-header bg-success text-white fw-semibold">{{ __('Disbursed') }}</div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>{{ __('Amount') }}</td><td>৳{{ number_format($application->approved_amount, 2) }}</td></tr>
                        <tr><td>{{ __('Interest Rate') }}</td><td>{{ $application->approved_interest_rate }}%</td></tr>
                        <tr><td>{{ __('Term') }}</td><td>{{ $application->approved_term }}</td></tr>
                        <tr><td>{{ __('Installment') }}</td><td class="fw-bold">৳{{ number_format($application->approved_installment, 2) }}</td></tr>
                        <tr><td>{{ __('Approved By') }}</td><td>{{ $application->approver?->name }}</td></tr>
                        <tr><td>{{ __('Approved At') }}</td><td>{{ $application->approved_at?->format('d-m-Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        @endif

        @if ($application->documents && $application->documents->count())
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">{{ __('Uploaded Documents') }} ({{ $application->documents->count() }})</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($application->documents as $doc)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $doc->type)) }}</span></td>
                                    <td>{{ $doc->title ?: basename($doc->file_path) }}</td>
                                    <td>
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2">
                                            <i class="bi bi-eye"></i> {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($application->loan)
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">{{ __('Disbursed Loan') }}</div>
                <div class="card-body">
                    <a href="{{ route('loans.show', $application->loan) }}" class="fw-bold">{{ $application->loan->loan_no }}</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection