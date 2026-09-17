@extends('layouts.app')

@section('title', $user->exists ? __('Edit User') : __('Add User'))

@section('content')
<div class="card shadow-sm" style="max-width: 650px;">
    <div class="card-header bg-white fw-semibold">{{ $user->exists ? __('Edit User') : __('Add New User') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Phone') }}</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Password') }} {{ $user->exists ? __('(leave blank to keep)') : '' }}</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('Roles') }}</label>
                    <select name="roles[]" class="form-select select2" multiple>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked($user->exists ? $user->is_active : true)>
                        <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $user->exists ? __('Update User') : __('Create User') }}</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection