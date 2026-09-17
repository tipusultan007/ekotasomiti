@extends('layouts.app')

@section('title', __('Loan Products'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Loan Products') }}</h4>
    <a href="{{ route('loans.products.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Add Product') }}</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Interest') }}</th><th>{{ __('Type') }}</th><th>{{ __('Range') }}</th><th>{{ __('Loans') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="fw-semibold">{{ $product->code }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ __(ucfirst($product->frequency)) }}</td>
                        <td>{{ $product->interest_rate }}%</td>
                        <td>{{ __(ucfirst($product->interest_type)) }}</td>
                        <td>৳{{ number_format($product->min_amount ?? 0, 0) }} - ৳{{ number_format($product->max_amount ?? 0, 0) }}</td>
                        <td>{{ $product->loans_count }}</td>
                        <td><span class="badge {{ $product->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ __(ucfirst($product->status)) }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('loans.products.edit', $product) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('loans.products.destroy', $product) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this product?') }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">{{ __('No loan products found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection