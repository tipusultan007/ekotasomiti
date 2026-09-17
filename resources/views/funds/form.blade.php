@extends('layouts.app')

@section('title', $fund->exists ? __('Edit Fund') : __('Create Fund'))

@section('content')
<div class="card shadow-sm" style="max-width: 650px;">
    <div class="card-header bg-white fw-semibold">
        {{ $fund->exists ? __('Edit Fund') : __('Create New Fund') }}
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $fund->exists ? route('funds.update', $fund) : route('funds.store') }}">
            @csrf
            @if ($fund->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">{{ __('Fund Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $fund->name) }}" placeholder="{{ __('e.g. Welfare Fund, Emergency Relief Fund') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">{{ __('Fund Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', $fund->code) }}" placeholder="{{ __('e.g. WF') }}" required>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('Fund Type') }} <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        @foreach (['welfare' => __('Welfare Fund'), 'emergency' => __('Emergency Fund'), 'reserve' => __('Reserve Fund'), 'development' => __('Development Fund'), 'other' => __('Other')] as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $fund->type) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ __('Status') }} <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" @selected(old('status', $fund->status ?? 'active') === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status', $fund->status) === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">{{ __('Description / Purpose') }}</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Describe the purpose, eligibility criteria, or guidelines for this fund...') }}">{{ old('description', $fund->description) }}</textarea>
                </div>
            </div>

            <div class="mt-4 pt-2 border-top">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i>{{ $fund->exists ? __('Update Fund') : __('Create Fund') }}
                </button>
                <a href="{{ route('funds.index') }}" class="btn btn-outline-secondary ms-1">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

