@extends('layouts.app')

@section('title', __('Savings Programs'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Savings Programs') }}</h4>
    <a href="{{ route('savings.programs.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Program') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Prefix') }}</th><th>{{ __('Min Deposit') }}</th><th>{{ __('Expected') }}</th><th>{{ __('Accounts') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr>
                        <td class="fw-semibold">{{ $program->code }}</td>
                        <td>{{ $program->name }}</td>
                        <td>{{ __(ucfirst($program->frequency)) }}</td>
                        <td><span class="badge bg-secondary">{{ $program->prefix }}</span></td>
                        <td>৳{{ number_format($program->min_deposit ?? 0, 2) }}</td>
                        <td>৳{{ number_format($program->expected_deposit ?? 0, 2) }}</td>
                        <td>{{ $program->accounts_count }}</td>
                        <td><span class="badge {{ $program->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ __(ucfirst($program->status)) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('savings.programs.edit', $program) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('savings.programs.destroy', $program) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this program?') }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No programs found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection