@extends('layouts.app')

@section('title', $area->exists ? __('Edit Area') : __('Add Area'))

@section('content')
<div class="card shadow-sm" style="max-width: 700px;">
    <div class="card-header bg-white fw-semibold">{{ $area->exists ? __('Edit Area') : __('Add New Area') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $area->exists ? route('areas.update', $area) : route('areas.store') }}">
            @csrf
            @if ($area->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Area Code') }} <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code', $area->code) }}" placeholder="{{ __('e.g. CTG-01') }}">
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Area Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $area->name) }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Address / Location') }}</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $area->address) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $area->status) === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status', $area->status) === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $area->notes) }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $area->exists ? __('Update Area') : __('Create Area') }}</button>
                <a href="{{ route('areas.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection