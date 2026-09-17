@extends('layouts.app')

@section('title', __('Role Permissions'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Role Permissions') }}</h4>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-gear me-1"></i>{{ __('Users') }}</a>
</div>

<form method="POST" action="{{ route('permissions.update') }}">
    @csrf

    <div class="accordion" id="rolesAccordion">
        @foreach ($roles as $role)
            <div class="accordion-item shadow-sm mb-2 border-0 rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button {{ $role->name !== 'super_admin' ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#role{{ $role->id }}">
                        <span class="fw-semibold me-2">{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                        <span class="badge bg-primary-subtle text-primary">{{ $role->permissions->count() }} {{ __('permissions') }}</span>
                        @if ($role->name === 'super_admin')
                            <span class="badge bg-secondary-subtle text-secondary ms-2">{{ __('Locked — full access') }}</span>
                        @endif
                    </button>
                </h2>
                <div id="role{{ $role->id }}" class="accordion-collapse collapse {{ $role->name !== 'super_admin' ? 'show' : '' }}" data-bs-parent="#rolesAccordion">
                    <div class="accordion-body">
                        <input type="hidden" name="permissions[{{ $role->id }}][role_id]" value="{{ $role->id }}">
                        <div class="row g-2">
                            @foreach ($permissions as $permission)
                                <div class="col-md-4 col-lg-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="permissions[{{ $role->id }}][permission_ids][]"
                                               value="{{ $permission->id }}"
                                               id="perm-{{ $role->id }}-{{ $permission->id }}"
                                               @checked($role->permissions->contains('id', $permission->id))
                                               @disabled($role->name === 'super_admin')>
                                        <label class="form-check-label" for="perm-{{ $role->id }}-{{ $permission->id }}">
                                            {{ ucwords(str_replace('_', ' ', $permission->name)) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <button class="btn btn-primary mt-3"><i class="bi bi-check-lg me-1"></i>{{ __('Save Permissions') }}</button>
</form>
@endsection