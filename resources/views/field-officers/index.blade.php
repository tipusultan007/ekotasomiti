@extends('layouts.app')

@section('title', __('Field Officers'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Field Officers') }}</h4>
    <a href="{{ route('field-officers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Field Officer') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Areas') }}</th><th>{{ __('Members') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($officers as $officer)
                    <tr>
                        <td class="fw-semibold">{{ $officer->name }}</td>
                        <td>{{ $officer->email }}</td>
                        <td>{{ $officer->phone ?: '—' }}</td>
                        <td>{{ $officer->areas->pluck('name')->join(', ') ?: '—' }}</td>
                        <td>{{ $officer->members_count }}</td>
                        <td>
                            <span class="badge {{ $officer->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ $officer->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('field-officers.edit', $officer) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('field-officers.destroy', $officer) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this officer?') }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No field officers found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $officers->links() }}
@endsection
