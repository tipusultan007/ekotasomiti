@extends('layouts.app')

@section('title', __('Expense Categories'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Expense Categories') }}</h4>
    <a href="{{ route('cash.expense-categories.create') }}" class="btn btn-danger btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Category') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Description') }}</th><th class="text-end">{{ __('Expenses') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td class="fw-semibold">{{ $category->name }}</td>
                        <td>{{ $category->description }}</td>
                        <td class="text-end">{{ $category->expenses_count }}</td>
                        <td><span class="badge {{ $category->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ __(ucfirst($category->status)) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('cash.expense-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('cash.expense-categories.destroy', $category) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete category :name? Categories with expenses cannot be deleted.', ['name' => $category->name]) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No expense categories found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection