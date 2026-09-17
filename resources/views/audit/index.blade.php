@extends('layouts.app')

@section('title', __('Audit Log'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Audit Log') }}</h4>
    <form class="d-flex gap-2" method="GET">
        <select name="action" class="form-select">
            <option value="">{{ __('All Actions') }}</option>
            @foreach (['create', 'update', 'delete', 'login', 'logout', 'approve', 'disburse', 'reverse', 'deposit', 'withdraw', 'collect', 'open', 'close', 'receive', 'post'] as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ __(ucfirst($action)) }}</option>
            @endforeach
        </select>
        <input type="text" name="user" class="form-control" placeholder="{{ __('User name') }}" value="{{ request('user') }}">
        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
        <button class="btn btn-outline-primary">{{ __('Filter') }}</button>
    </form>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr><th>{{ __('Time') }}</th><th>{{ __('User') }}</th><th>{{ __('Action') }}</th><th>{{ __('Model') }}</th><th>{{ __('Entity') }}</th><th>{{ __('Description') }}</th><th>{{ __('IP') }}</th></tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d-m-Y H:i') }}</td>
                        <td>{{ $log->user?->name ?? __('System') }}</td>
                        <td><span class="badge {{ match($log->action) { 'create' => 'bg-success-subtle text-success', 'update' => 'bg-primary-subtle text-primary', 'delete' => 'bg-danger-subtle text-danger', 'login', 'logout' => 'bg-secondary-subtle text-secondary', default => 'bg-info-subtle text-info' } }}">{{ __(ucfirst($log->action)) }}</span></td>
                        <td>{{ $log->loggable_type }}</td>
                        <td>{{ $log->loggable_id }}</td>
                        <td>{{ $log->description }}</td>
                        <td>{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No audit entries found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $logs->links() }}
@endsection