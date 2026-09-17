<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-success border-4"><div class="card-body">
            <div class="text-muted small">{{ __('Savings Collection') }}</div>
            <div class="fs-5 fw-bold text-success">৳{{ number_format($summary['collection'], 2) }}</div>
            <div class="small text-muted">{{ __('Deposits + opening balances in period') }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-danger border-4"><div class="card-body">
            <div class="text-muted small">{{ __('Savings Withdrawn') }}</div>
            <div class="fs-5 fw-bold text-danger">৳{{ number_format($summary['withdrawn'], 2) }}</div>
            <div class="small text-muted">{{ __('Net Savings') }}: ৳{{ number_format($summary['net_savings'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-primary border-4"><div class="card-body">
            <div class="text-muted small">{{ __('Savings Balance (as of end date)') }}</div>
            <div class="fs-5 fw-bold">৳{{ number_format($summary['balance_as_of_end'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-warning border-4"><div class="card-body">
            <div class="text-muted small">{{ __('Loan Provided') }}</div>
            <div class="fs-5 fw-bold text-warning">৳{{ number_format($summary['loan_provided'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-info border-4"><div class="card-body">
            <div class="text-muted small">{{ __('Loan Repaid') }}</div>
            <div class="fs-5 fw-bold text-info">৳{{ number_format($summary['loan_paid'], 2) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-start border-dark border-4"><div class="card-body">
            <div class="text-muted small">{{ __('Loan Outstanding (as of end date)') }}</div>
            <div class="fs-5 fw-bold">৳{{ number_format($summary['loan_remain'], 2) }}</div>
        </div></div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">{{ __('Savings by Program') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr>
                <th>{{ __('Program') }}</th>
                <th class="text-end">{{ __('Collected') }}</th>
                <th class="text-end">{{ __('Withdrawn') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($savingsByProgram as $row)
                    <tr>
                        <td>{{ $row->program?->name ?? __('Unknown') }}</td>
                        <td class="amount text-success">৳{{ number_format($row->collected, 2) }}</td>
                        <td class="amount text-danger">৳{{ number_format($row->withdrawn, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">{{ __('No data found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">{{ __('Loan by Product') }}</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead><tr>
                <th>{{ __('Product') }}</th>
                <th class="text-end">{{ __('Provided') }}</th>
                <th class="text-end">{{ __('Repaid') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($loanByProduct as $row)
                    <tr>
                        <td>{{ $row->product?->name ?? __('Unknown') }}</td>
                        <td class="amount text-warning">৳{{ number_format($row->provided, 2) }}</td>
                        <td class="amount text-info">৳{{ number_format($row->repaid, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">{{ __('No data found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
