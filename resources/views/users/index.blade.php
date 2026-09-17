@extends('layouts.app')

@section('title', __('Users'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Users') }}</h4>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Add User') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Roles') }}</th><th>{{ __('Status') }}</th><th>{{ __('Last Login') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>
                            @foreach ($user->roles as $role)
                                <span class="badge bg-primary-subtle text-primary me-1">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                {{ $user->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </td>
                        <td>{{ $user->last_login_at?->format('d-m-Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @if ($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this user?') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No users found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $users->links() }}
@endsection