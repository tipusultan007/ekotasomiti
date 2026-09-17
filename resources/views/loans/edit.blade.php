@extends('layouts.app')

@section('title', __('Edit Loan - :no', ['no' => $loan->loan_no]))

@section('content')
<div class="card shadow-sm" style="max-width: 800px;">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">{{ __('Edit Loan — :no', ['no' => $loan->loan_no]) }}</span>
        <span class="badge {{ match($loan->status) { 'completed' => 'bg-success', 'overdue' => 'bg-danger', 'active' => 'bg-info', 'written_off' => 'bg-dark', default => 'bg-secondary' } }}">
            {{ __(ucfirst($loan->status)) }}
        </span>
    </div>
    <div class="card-body">
        <div class="alert alert-light border mb-4">
            <div class="row g-2">
                <div class="col-sm-6">
                    <span class="text-muted">{{ __('Member') }}:</span> <strong>{{ $loan->member->name }}</strong> ({{ $loan->member->account_no ?? $loan->member->member_no ?? 'ID: '.$loan->member_id }})
                </div>
                <div class="col-sm-6">
                    <span class="text-muted">{{ __('Product') }}:</span> <strong>{{ $loan->product->name }}</strong> ({{ ucfirst($loan->frequency) }})
                </div>
                <div class="col-sm-6">
                    <span class="text-muted">{{ __('Total Paid') }}:</span> <strong class="text-success">৳{{ number_format($loan->total_paid, 2) }}</strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted">{{ __('Outstanding') }}:</span> <strong class="text-danger">৳{{ number_format($loan->outstanding, 2) }}</strong>
                </div>
            </div>
            @if ($hasRepayments)
                <div class="mt-2 text-warning small">
                    <i class="bi bi-info-circle me-1"></i>{{ __('Repayments have already been collected for this loan. Principal, rate, and term cannot be altered without deleting or reversing repayments first.') }}
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('loans.update', $loan) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">{{ __('Area') }}</label>
                    <select name="area_id" class="form-select select2">
                        <option value="">{{ __('Select Area') }}</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected(old('area_id', $loan->area_id) == $area->id)>{{ $area->name }} ({{ $area->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Principal Amount') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input type="number" step="0.01" min="1" name="principal_amount" class="form-control @error('principal_amount') is-invalid @enderror"
                               value="{{ old('principal_amount', $loan->principal_amount) }}"
                               @if($hasRepayments) readonly @else required @endif>
                    </div>
                    @error('principal_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Interest Rate (%)') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" name="interest_rate" class="form-control @error('interest_rate') is-invalid @enderror"
                               value="{{ old('interest_rate', $loan->interest_rate) }}"
                               @if($hasRepayments) readonly @else required @endif>
                        <span class="input-group-text">%</span>
                    </div>
                    @error('interest_rate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Term (Installments)') }} <span class="text-danger">*</span></label>
                    <input type="number" min="1" name="term" class="form-control @error('term') is-invalid @enderror"
                           value="{{ old('term', $loan->term) }}"
                           @if($hasRepayments) readonly @else required @endif>
                    @error('term') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Disbursement Date') }} <span class="text-danger">*</span></label>
                    <input type="date" name="disbursement_date" class="form-control @error('disbursement_date') is-invalid @enderror"
                           value="{{ old('disbursement_date', $loan->disbursement_date?->format('Y-m-d')) }}" required>
                    @error('disbursement_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('First Due Date') }}</label>
                    <input type="date" name="first_due_date" class="form-control @error('first_due_date') is-invalid @enderror"
                           value="{{ old('first_due_date', $loan->first_due_date?->format('Y-m-d')) }}">
                    @error('first_due_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Status') }} <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @foreach (['disbursed' => 'Disbursed', 'active' => 'Active', 'overdue' => 'Overdue', 'completed' => 'Completed', 'written_off' => 'Written Off', 'cancelled' => 'Cancelled'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('status', $loan->status) === $val)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4 pt-2 border-top d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ __('Save Changes') }}</button>
                <a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
