@extends('layouts.app')

@section('title', __('Members'))

@push('styles')
<style>
    .member-mobile-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #eef2f6;
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .member-mobile-card:active {
        transform: scale(0.99);
    }
    .member-mobile-avatar {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.15rem;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff;
        flex-shrink: 0;
        box-shadow: 0 3px 8px rgba(37, 99, 235, 0.25);
        overflow: hidden;
    }
    .member-mobile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .fo-call-btn {
        display: inline-flex;
        align-items: center;
        background: #f0fdf4;
        color: #15803d;
        font-weight: 600;
        font-size: 0.84rem;
        padding: 6px 12px;
        border-radius: 10px;
        border: 1px solid #dcfce7;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .fo-call-btn:hover, .fo-call-btn:active {
        background: #dcfce7;
        color: #166534;
    }
    .fo-view-btn {
        display: inline-flex;
        align-items: center;
        background: #eff6ff;
        color: #2563eb;
        font-weight: 600;
        font-size: 0.84rem;
        padding: 6px 13px;
        border-radius: 10px;
        border: 1px solid #dbeafe;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .fo-view-btn:hover, .fo-view-btn:active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }
    .fo-action-icon-btn {
        width: 33px;
        height: 33px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 0.88rem;
        text-decoration: none;
        transition: all 0.15s ease;
        padding: 0;
    }
    .fo-action-icon-btn:hover, .fo-action-icon-btn:active {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    .fo-delete-btn {
        background: #fef2f2;
        border-color: #fee2e2;
        color: #dc2626;
    }
    .fo-delete-btn:hover, .fo-delete-btn:active {
        background: #dc2626;
        color: #ffffff;
        border-color: #dc2626;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0 fw-bold">{{ __('Members') }}</h4>
        <div class="text-muted small">{{ __('Total :count registered members', ['count' => $members->total()]) }}</div>
    </div>
    <div class="d-flex gap-2">
        @can('manage members')
            <a href="{{ route('onboarding.create') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm fw-semibold">
                <i class="bi bi-person-plus me-1"></i>{{ __('New Onboarding') }}
            </a>
        @endcan
        <a href="{{ route('members.create') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-semibold">
            <i class="bi bi-person-plus-fill me-1"></i>{{ __('Add Member') }}
        </a>
    </div>
</div>

<form class="card shadow-sm border-0 mb-3" method="GET" style="border-radius: 16px;">
    <div class="card-body p-3">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="{{ __('Search name / member no / mobile / NID') }}" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="area_id" class="form-select form-select-sm">
                    <option value="">{{ __('All Areas') }}</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('All Status') }}</option>
                    @foreach (['active', 'inactive', 'suspended', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst($status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm rounded-pill flex-grow-1 fw-semibold"><i class="bi bi-funnel me-1"></i>{{ __('Filter') }}</button>
                <a href="{{ route('members.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">{{ __('Reset') }}</a>
            </div>
        </div>
    </div>
</form>

{{-- MOBILE VIEW: MEMBER CARDS (Smooth & Compact) --}}
<div class="d-block d-md-none mb-3">
    @forelse ($members as $member)
        <div class="member-mobile-card">
            {{-- Top Header Section --}}
            <div class="d-flex align-items-start gap-3">
                <div class="member-mobile-avatar">
                    @if ($member->photo_path)
                        <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->name }}">
                    @else
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    @endif
                </div>

                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <a href="{{ route('members.show', $member) }}" class="text-decoration-none text-dark min-w-0">
                            <h6 class="mb-0 fw-bold text-truncate" style="font-size: 0.98rem;">{{ $member->name }}</h6>
                        </a>
                        <span class="badge {{ match($member->status) { 'active' => 'bg-success-subtle text-success', 'inactive' => 'bg-secondary-subtle text-secondary', 'suspended' => 'bg-warning-subtle text-warning', 'closed' => 'bg-danger-subtle text-danger' } }} ms-1">
                            {{ __(ucfirst($member->status)) }}
                        </span>
                    </div>

                    {{-- Badges row --}}
                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                        <span class="badge bg-primary-subtle text-primary fw-bold" style="font-size: 0.73rem;">{{ $member->member_no }}</span>
                        @if ($member->area)
                            <span class="badge bg-light text-secondary border" style="font-size: 0.73rem;">{{ $member->area->name }}</span>
                        @endif
                    </div>

                    {{-- Officer --}}
                    @if ($member->fieldOfficer)
                        <div class="text-muted small mt-1 d-flex align-items-center" style="font-size: 0.78rem;">
                            <i class="bi bi-person-badge text-primary opacity-75 me-1"></i>
                            <span class="text-truncate">{{ $member->fieldOfficer->name }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Integrated Bottom Action Toolbar --}}
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light-subtle">
                @if ($member->mobile)
                    <a href="tel:{{ $member->mobile }}" class="fo-call-btn" title="{{ __('Call Member') }}">
                        <i class="bi bi-telephone-fill me-1"></i>
                        <span>{{ $member->mobile }}</span>
                    </a>
                @else
                    <span class="text-muted small fst-italic" style="font-size: 0.78rem;">{{ __('No mobile') }}</span>
                @endif

                <div class="d-flex align-items-center gap-1">
                    <a href="{{ route('members.show', $member) }}" class="fo-view-btn" title="{{ __('View Profile') }}">
                        <i class="bi bi-eye me-1"></i>{{ __('View') }}
                    </a>
                    <a href="{{ route('members.edit', $member) }}" class="fo-action-icon-btn" title="{{ __('Edit') }}">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('members.destroy', $member) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete member :name? This is permanent.', ['name' => $member->name]) }}">
                        @csrf @method('DELETE')
                        <button class="fo-action-icon-btn fo-delete-btn" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card shadow-sm border-0 text-center py-5" style="border-radius: 16px;">
            <div class="card-body">
                <i class="bi bi-people text-muted display-4 d-block mb-2"></i>
                <h6 class="fw-bold text-dark">{{ __('No members found.') }}</h6>
            </div>
        </div>
    @endforelse
</div>

{{-- DESKTOP/TABLET VIEW: DATA TABLE (Visible on Medium & Larger Screens) --}}
<div class="card shadow-sm d-none d-md-block">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Member No') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Mobile') }}</th>
                        <th>{{ __('Area') }}</th>
                        <th>{{ __('Officer') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td class="fw-semibold text-primary">{{ $member->member_no }}</td>
                            <td>
                                <a href="{{ route('members.show', $member) }}" class="text-dark fw-semibold text-decoration-none">
                                    {{ $member->name }}
                                </a>
                            </td>
                            <td>
                                @if ($member->mobile)
                                    <a href="tel:{{ $member->mobile }}" class="text-decoration-none text-muted">
                                        <i class="bi bi-telephone me-1"></i>{{ $member->mobile }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $member->area?->name ?? '—' }}</td>
                            <td>{{ $member->fieldOfficer?->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ match($member->status) { 'active' => 'bg-success-subtle text-success', 'inactive' => 'bg-secondary-subtle text-secondary', 'suspended' => 'bg-warning-subtle text-warning', 'closed' => 'bg-danger-subtle text-danger' } }}">
                                    {{ __(ucfirst($member->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('members.show', $member) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View') }}"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('members.edit', $member) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('members.destroy', $member) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete member :name? This is permanent. Members with financial accounts cannot be deleted.', ['name' => $member->name]) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No members found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $members->links() }}
</div>
@endsection