@extends('layouts.app')

@section('title', $officer->exists ? __('Edit Field Officer') : __('Add Field Officer'))

@section('content')
<div class="card shadow-sm" style="max-width: 800px;">
    <div class="card-header bg-white fw-semibold">{{ $officer->exists ? __('Edit Field Officer') : __('Add New Field Officer') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $officer->exists ? route('field-officers.update', $officer) : route('field-officers.store') }}">
            @csrf
            @if ($officer->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $officer->name) }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Email (Login)') }} <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $officer->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Phone') }}</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $officer->phone) }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Password') }} {!! $officer->exists ? '' : '<span class="text-danger">*</span>' !!}</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           placeholder="{{ $officer->exists ? __('Leave blank to keep current') : '' }}">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="is_active" class="form-select">
                        <option value="1" @selected(old('is_active', $officer->is_active ?? true))>{{ __('Active') }}</option>
                        <option value="0" @selected(old('is_active', $officer->is_active ?? true) == false)>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Assigned Areas') }}</label>
                    <select name="areas[]" class="form-select select2" multiple>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" @selected($officer->exists && $officer->areas->contains($area->id))>{{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $officer->exists ? __('Update Officer') : __('Create Officer') }}</button>
                <a href="{{ route('field-officers.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
