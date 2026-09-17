@extends('layouts.app')

@section('title', __(ucfirst($frequency)) . ' ' . __('Savings Collection'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __(ucfirst($frequency)) }} {{ __('Savings Collection') }}</h4>
    <div>
        <a href="{{ route('collection.savings.print', $frequency) }}?date={{ $date }}&area_id={{ request('area_id') }}&account_id={{ request('account_id') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>{{ __('Print') }}</a>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    @foreach (['daily', 'weekly', 'monthly'] as $f)
        <li class="nav-item">
            <a class="nav-link {{ $f === $frequency ? 'active' : '' }}" href="{{ route('collection.savings.sheet', $f) }}">
                <i class="bi {{ $f === 'daily' ? 'bi-calendar-day' : ($f === 'weekly' ? 'bi-calendar-week' : 'bi-calendar-month') }} me-1"></i>{{ __(ucfirst($f)) }}
            </a>
        </li>
    @endforeach
</ul>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        @include('collection._savings-deposit-form', ['frequency' => $frequency, 'date' => $date, 'officer' => $officer, 'accountOptions' => $accountOptions, 'selectedAccount' => $selectedAccount])
    </div>
    <div class="col-lg-6">
        <div id="account-details-panel" class="h-100">
            <div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 16px; border: 1px dashed #cbd5e1 !important; background: #fafafa; min-height: 380px;">
                <div class="card-body text-center text-muted py-5 d-flex flex-column align-items-center justify-content-center">
                    <div class="p-3 rounded-circle bg-white shadow-xs mb-3 border">
                        <i class="bi bi-wallet2 fs-2 text-primary"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">{{ __('No Account Selected') }}</h6>
                    <p class="small text-muted mb-0" style="max-width: 250px;">{{ __('Choose a member account from the left form to view live balance and dues.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-3">
        <input type="date" name="date" class="form-control" value="{{ $date }}">
    </div>
    <div class="col-md-3">
        <select name="account_id" class="form-select select2" data-placeholder="{{ __('All :freq accounts', ['freq' => __(ucfirst($frequency))]) }}">
            <option value="">{{ __('All :freq accounts', ['freq' => __(ucfirst($frequency))]) }}</option>
            @foreach ($accountOptions as $account)
                <option value="{{ $account->id }}" @selected(request('account_id') == $account->id)>
                    {{ $account->account_no }} — {{ $account->member->name }}
                </option>
            @endforeach
        </select>
    </div>
    @if (!auth()->user()->hasRole('field_officer'))
        <div class="col-md-3">
            <select name="area_id" class="form-select">
                <option value="">{{ __('All Areas') }}</option>
                @foreach ($areas as $area)
                    <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-md-2">
        <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>{{ __('Load') }}</button>
    </div>
</form>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row g-3 mb-3">
    <div class="col-6 col-md">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Expected') }}</div>
            <div class="fs-5 fw-bold">৳{{ number_format($totals['expected'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Collected') }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($totals['collected'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Due') }}</div>
            <div class="fs-5 fw-bold text-danger">৳{{ number_format($totals['due'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md">
        <div class="card shadow-sm"><div class="card-body">
            <div class="text-muted small">{{ __('Collected') }}</div>
            <div class="fs-5 fw-bold">{{ $totals['collectedCount'] }} / {{ $rows->count() }}</div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white"><span class="fw-semibold">{{ __('Savings Collection List') }}</span></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Account No') }}</th>
                    <th>{{ __('Member') }}</th>
                    <th class="text-end">{{ __('Deposit') }}</th>
                    <th>{{ __('Note') }}</th>
                    <th>{{ __('Officer') }}</th>
                    @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))<th class="text-end">{{ __('Actions') }}</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $txn)
                    <tr>
                        <td>{{ $txn->txn_date->format('d-m-Y') }}</td>
                        <td class="fw-semibold">{{ $txn->account->account_no }}</td>
                        <td>{{ $txn->account->member->name }}</td>
                        <td class="amount text-success">৳{{ number_format($txn->amount, 2) }}</td>
                        <td class="text-muted small">{{ $txn->notes }}</td>
                        <td>{{ $txn->fieldOfficer?->name }}</td>
                        @if(auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions'))
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSavingsTxnModal"
                                    data-action="{{ route('collection.savings.transactions.update', $txn) }}"
                                    data-txn-no="{{ $txn->txn_no }}"
                                    data-amount="{{ $txn->amount }}"
                                    data-date="{{ $txn->txn_date->toDateString() }}"
                                    data-notes="{{ $txn->notes }}" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></button>
                                <form method="POST" action="{{ route('collection.savings.transactions.destroy', $txn) }}" class="d-inline" data-confirm="{{ __('Delete collection :no? The deposit will be permanently removed, the account balance restored, and the linked cash entry removed.', ['no' => $txn->txn_no]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ (auth()->user()->hasAnyRole(['admin', 'super_admin', 'manager']) || auth()->user()->can('reverse transactions')) ? 7 : 6 }}" class="text-center text-muted py-4">{{ __('No collections recorded for this date.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="editSavingsTxnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="editSavingsTxnForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Savings Collection') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Transaction No') }}</label>
                        <input type="text" class="form-control" id="editSavingsTxnNo" disabled>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">{{ __('Amount') }}</label>
                            <input type="number" name="amount" step="0.01" min="0.01" class="form-control" id="editSavingsTxnAmount" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">{{ __('Date') }}</label>
                            <input type="date" name="txn_date" class="form-control" id="editSavingsTxnDate" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">{{ __('Note') }}</label>
                        <textarea name="notes" class="form-control" id="editSavingsTxnNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const select = document.querySelector('select[name="account_id"]');
        const panel = document.getElementById('account-details-panel');
        const url = @json(route('collection.savings.details', $frequency));
        const date = @json($date);
        const placeholder = '<div class="card shadow-sm border-0 mb-4 h-100" style="border-radius: 16px; border: 1px dashed #cbd5e1 !important; background: #fafafa; min-height: 380px;"><div class="card-body text-center text-muted py-5 d-flex flex-column align-items-center justify-content-center"><div class="p-3 rounded-circle bg-white shadow-xs mb-3 border"><i class="bi bi-wallet2 fs-2 text-primary"></i></div><h6 class="fw-bold text-dark mb-1">' + @json(__('No Account Selected')) + '</h6><p class="small text-muted mb-0" style="max-width: 250px;">' + @json(__('Choose a member account from the left form to view live balance and dues.')) + '</p></div></div>';

        if (!select || !panel) return;

        function load(id) {
            if (!id) { panel.innerHTML = placeholder; return; }
            fetch(url + '?account_id=' + encodeURIComponent(id) + '&date=' + encodeURIComponent(date))
                .then(r => r.text())
                .then(html => { panel.innerHTML = html; });
        }

        $(select).on('change', function () { load($(this).val()); });
        if (select.value) load(select.value);
    })();
</script>
<script>
    (function () {
        const form = document.getElementById('editSavingsTxnForm');
        if (!form) return;
        document.querySelectorAll('button[data-bs-target="#editSavingsTxnModal"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                form.action = btn.dataset.action;
                document.getElementById('editSavingsTxnNo').value = btn.dataset.txnNo;
                document.getElementById('editSavingsTxnAmount').value = btn.dataset.amount;
                document.getElementById('editSavingsTxnDate').value = btn.dataset.date;
                document.getElementById('editSavingsTxnNotes').value = btn.dataset.notes || '';
            });
        });
    })();
</script>
@endpush
@endsection