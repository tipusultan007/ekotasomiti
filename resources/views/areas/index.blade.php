@extends('layouts.app')

@section('title', __('Areas'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Areas') }}</h4>
    <a href="{{ route('areas.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Area') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Address') }}</th><th>{{ __('Members') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($areas as $area)
                    <tr>
                        <td class="fw-semibold">{{ $area->code }}</td>
                        <td>{{ $area->name }}</td>
                        <td>{{ $area->address }}</td>
                        <td>{{ $area->members_count }}</td>
                        <td>
                            <span class="badge {{ $area->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ __(ucfirst($area->status)) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('areas.edit', $area) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('areas.destroy', $area) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this area?') }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No areas found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $areas->links() }}
@endsection