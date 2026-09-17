@extends('layouts.app')

@section('title', __('Savings Accounts'))

@push('styles')
<style>
    .dropdown-toggle-no-caret::after {
        display: none !important;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Savings Accounts') }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('savings.deposits.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cash-coin me-1"></i>{{ __('Deposits') }}</a>
        <a href="{{ route('savings.withdrawals.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cash-stack me-1"></i>{{ __('Withdrawals') }}</a>
        <a href="{{ route('savings.accounts.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('Open Account') }}</a>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="{{ __('Search account no / member') }}" value="{{ request('search') }}">
    </div>
    <div class="col-md-3">
        <select name="program_id" class="form-select">
            <option value="">{{ __('All Programs') }}</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ __($program->name) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="area_id" class="form-select">
            <option value="">{{ __('All Areas') }}</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" style="min-height: 250px;">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>{{ __('Account No') }}</th><th>{{ __('Member') }}</th><th>{{ __('Program') }}</th><th>{{ __('Area') }}</th><th class="text-end">{{ __('Balance') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Actions') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td class="fw-semibold">{{ $account->account_no }}</td>
                            <td><a href="{{ route('members.show', $account->member) }}">{{ $account->member->name }}</a></td>
                            <td>
                                @php
                                    $freq = strtolower($account->program->frequency ?? '');
                                    $badgeClass = match($freq) {
                                        'daily' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'weekly' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'monthly' => 'border',
                                        default => 'bg-secondary-subtle text-secondary border',
                                    };
                                    $badgeIcon = match($freq) {
                                        'daily' => 'bi-calendar-day',
                                        'weekly' => 'bi-calendar-week',
                                        'monthly' => 'bi-calendar-month',
                                        default => 'bi-wallet2',
                                    };
                                    $customStyle = $freq === 'monthly' ? 'background-color: #f3e8ff !important; color: #7e22ce !important; border-color: #e9d5ff !important;' : '';
                                @endphp
                                <span class="badge {{ $badgeClass }} rounded-pill px-2.5 py-1 fw-medium" style="font-size: 0.78rem; {{ $customStyle }}">
                                    <i class="bi {{ $badgeIcon }} me-1"></i>{{ __($account->program->name) }}
                                </span>
                            </td>
                            <td>{{ $account->area?->name }}</td>
                            <td class="amount fw-semibold">৳{{ number_format($account->current_balance, 2) }}</td>
                            <td><span class="badge {{ $account->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ __(ucfirst($account->status)) }}</span></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle dropdown-toggle-no-caret px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Actions') }}">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 190px; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1) !important;">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('savings.accounts.transactions', $account) }}">
                                                <i class="bi bi-clock-history text-primary"></i>
                                                <span>{{ __('Transaction History') }}</span>
                                            </a>
                                        </li>
                                        @if ($account->status === 'active')
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('savings.deposits.create', ['account_id' => $account->id]) }}">
                                                    <i class="bi bi-cash-coin text-success"></i>
                                                    <span>{{ __('Deposit') }}</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('savings.withdrawals.create', ['account_id' => $account->id]) }}">
                                                    <i class="bi bi-cash-stack text-info"></i>
                                                    <span>{{ __('Withdraw') }}</span>
                                                </a>
                                            </li>
                                        @endif
                                        @can('update', $account)
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('savings.accounts.edit', $account) }}">
                                                    <i class="bi bi-pencil text-secondary"></i>
                                                    <span>{{ __('Edit Account') }}</span>
                                                </a>
                                            </li>
                                            @if ($account->status !== 'closed')
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-dark" href="{{ route('savings.accounts.close-form', $account) }}">
                                                        <i class="bi bi-slash-circle text-warning"></i>
                                                        <span>{{ __('Close Account') }}</span>
                                                    </a>
                                                </li>
                                            @endif
                                        @endcan
                                        @can('delete', $account)
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <form action="{{ route('savings.accounts.destroy', $account) }}" method="POST"
                                                      data-confirm="{{ __('Delete savings account :no? This is permanent. Accounts with transactions cannot be deleted.', ['no' => $account->account_no]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger">
                                                        <i class="bi bi-trash"></i>
                                                        <span>{{ __('Delete Account') }}</span>
                                                    </button>
                                                </form>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No savings accounts found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{ $accounts->links() }}
@endsection