@extends('layouts.app')

@section('title', $category->exists ? __('Edit Income Category') : __('Add Income Category'))

@section('content')
<div class="card shadow-sm" style="max-width: 600px;">
    <div class="card-header bg-white fw-semibold">{{ $category->exists ? __('Edit Income Category') : __('Add Income Category') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $category->exists ? route('cash.income-categories.update', $category) : route('cash.income-categories.store') }}">
            @csrf
            @if ($category->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $category->status) === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(old('status', $category->status) === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Description') }}</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>{{ $category->exists ? __('Update Category') : __('Create Category') }}</button>
                <a href="{{ route('cash.income-categories.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection