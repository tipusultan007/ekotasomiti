@extends('layouts.app')

@section('title', __('Member - :name', ['name' => $member->name]))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0">{{ __('Member Profile') }}</h4>
    <div class="d-flex flex-wrap gap-1">
        <a href="{{ route('members.edit', $member) }}" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-pencil me-1"></i>{{ __('Edit') }}</a>
        <a href="{{ route('members.statement', $member) }}" class="btn btn-outline-info btn-sm rounded-pill"><i class="bi bi-file-earmark-text me-1"></i>{{ __('Statement') }}</a>
        <a href="{{ route('savings.accounts.create', ['member_id' => $member->id]) }}" class="btn btn-primary btn-sm rounded-pill"><i class="bi bi-wallet2 me-1"></i>{{ __('Open Account') }}</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                @if ($member->photo_path)
                    <img src="{{ asset('storage/' . $member->photo_path) }}" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;">
                @else
                    <div class="rounded-circle bg-secondary-subtle d-inline-flex align-items-center justify-content-center mb-3" style="width:120px;height:120px;">
                        <i class="bi bi-person fs-1 text-secondary"></i>
                    </div>
                @endif
                <h5 class="mb-0">{{ $member->name }}</h5>
                <div class="text-muted small">{{ $member->name_bn }}</div>
                <span class="badge mt-2 {{ match($member->status) { 'active' => 'bg-success-subtle text-success', 'inactive' => 'bg-secondary-subtle text-secondary', 'suspended' => 'bg-warning-subtle text-warning', 'closed' => 'bg-danger-subtle text-danger' } }}">
                    {{ __(ucfirst($member->status)) }}
                </span>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('Member No') }}</span><span class="fw-semibold">{{ $member->member_no }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('Mobile') }}</span><span>{{ $member->mobile }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('NID') }}</span><span>{{ $member->nid }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('Gender') }}</span><span>{{ __(ucfirst($member->gender)) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('DOB') }}</span><span>{{ $member->dob?->translatedFormat('d-m-Y') }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('Area') }}</span><span>{{ $member->area?->name }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('Officer') }}</span><span>{{ $member->fieldOfficer?->name }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('Occupation') }}</span><span>{{ $member->occupation }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('Membership') }}</span><span>{{ $member->membership_date?->translatedFormat('d-m-Y') }}</span></li>
            </ul>
        </div>
    </div>

    <div class="col-md-8">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('Total Savings') }}</div>
                        <div class="fs-5 fw-bold">৳{{ number_format($member->savingsAccounts->sum('current_balance'), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('Loan Outstanding') }}</div>
                        <div class="fs-5 fw-bold text-danger">৳{{ number_format($member->loans->whereIn('status', ['disbursed', 'active', 'overdue'])->sum('outstanding'), 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('Accounts') }}</div>
                        <div class="fs-5 fw-bold">{{ __(':savings_count savings / :loans_count loans', ['savings_count' => $member->savingsAccounts->count(), 'loans_count' => $member->loans->count()]) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ __('Savings Accounts') }}</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>{{ __('Account No') }}</th><th>{{ __('Program') }}</th><th>{{ __('Opened') }}</th><th class="text-end">{{ __('Balance') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @forelse ($member->savingsAccounts as $account)
                            <tr>
                                <td><a href="{{ route('savings.accounts.transactions', $account) }}">{{ $account->account_no }}</a></td>
                                <td>{{ $account->program->name }}</td>
                                <td>{{ $account->opening_date->translatedFormat('d-m-Y') }}</td>
                                <td class="amount">৳{{ number_format($account->current_balance, 2) }}</td>
                                <td><span class="badge {{ $account->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ __(ucfirst($account->status)) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No savings accounts.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ __('Loans') }}</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>{{ __('Loan No') }}</th><th>{{ __('Product') }}</th><th>{{ __('Principal') }}</th><th class="text-end">{{ __('Outstanding') }}</th><th>{{ __('Status') }}</th></tr></thead>
                    <tbody>
                        @forelse ($member->loans as $loan)
                            <tr>
                                <td><a href="{{ route('loans.show', $loan) }}">{{ $loan->loan_no }}</a></td>
                                <td>{{ $loan->product->name }}</td>
                                <td class="amount">৳{{ number_format($loan->principal_amount, 2) }}</td>
                                <td class="amount">৳{{ number_format($loan->outstanding, 2) }}</td>
                                <td><span class="badge bg-{{ match($loan->status) { 'completed' => 'success-subtle text-success', 'overdue' => 'danger-subtle text-danger', 'active' => 'info-subtle text-info', default => 'secondary-subtle text-secondary' } }}">{{ __(ucfirst($loan->status)) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No loans.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">{{ __('Nominees') }}</span>
                @can('update', $member)
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addNomineeModal">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Add Nominee') }}
                    </button>
                @endcan
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Nominee name') }}</th>
                            <th>{{ __('Relation') }}</th>
                            <th>{{ __('NID') }}</th>
                            <th>{{ __('Mobile') }}</th>
                            <th class="text-end">{{ __('Percentage') }}</th>
                            @can('update', $member)
                                <th class="text-end">{{ __('Action') }}</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($member->nominees as $nominee)
                            <tr>
                                <td class="fw-semibold">{{ $nominee->name }}</td>
                                <td>{{ $nominee->relationship ?: '-' }}</td>
                                <td>{{ $nominee->nid ?: '-' }}</td>
                                <td>{{ $nominee->mobile ?: '-' }}</td>
                                <td class="text-end">{{ $nominee->percentage !== null ? $nominee->percentage . '%' : '-' }}</td>
                                @can('update', $member)
                                    <td class="text-end">
                                        <form action="{{ route('members.nominees.destroy', $nominee) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this nominee?') }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can('update', $member) ? 6 : 5 }}" class="text-center text-muted py-3">
                                    <div class="mb-2">{{ __('No nominees added yet.') }}</div>
                                    @can('update', $member)
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addNomineeModal">
                                            <i class="bi bi-plus-lg me-1"></i>{{ __('Add Nominee') }}
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">{{ __('Documents') }}</span>
                <form method="POST" action="{{ route('members.documents.store', $member) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
                    @csrf
                    <select name="type" class="form-select form-select-sm w-auto">
                        @foreach (['nid', 'photo', 'signature', 'other'] as $type)
                            <option value="{{ $type }}">{{ __(ucfirst($type)) }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="title" class="form-control form-control-sm" placeholder="{{ __('Title') }}" style="max-width:140px;">
                    <input type="file" name="file" class="form-control form-control-sm" style="max-width:200px;" required>
                    <button class="btn btn-sm btn-primary rounded-pill px-3">{{ __('Upload') }}</button>
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>{{ __('Type') }}</th><th>{{ __('Title') }}</th><th>{{ __('File') }}</th><th class="text-end">{{ __('Action') }}</th></tr></thead>
                    <tbody>
                        @forelse ($member->documents as $document)
                            <tr>
                                <td>{{ __(ucfirst($document->type)) }}</td>
                                <td>{{ $document->title }}</td>
                                <td><a href="{{ asset('storage/' . $document->file_path) }}" target="_blank">{{ __('View') }}</a></td>
                                <td class="text-end">
                                    <form action="{{ route('members.documents.destroy', $document) }}" method="POST" class="d-inline" data-confirm="{{ __('Delete this document?') }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">{{ __('No documents.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@can('update', $member)
<div class="modal fade" id="addNomineeModal" tabindex="-1" aria-labelledby="addNomineeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('members.nominees.store', $member) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addNomineeModalLabel">{{ __('Add Nominee') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">{{ __('Nominee name') }}</label>
                        <input type="text" name="name" class="form-control" required placeholder="{{ __('Nominee name') }}">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Relation') }}</label>
                            <input type="text" name="relationship" class="form-control" placeholder="{{ __('Relation') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Percentage') }} (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="percentage" class="form-control" placeholder="%">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('NID') }}</label>
                            <input type="text" name="nid" class="form-control" placeholder="{{ __('NID') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Mobile') }}</label>
                            <input type="text" name="mobile" class="form-control" placeholder="{{ __('Mobile') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Add Nominee') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection