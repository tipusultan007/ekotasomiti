<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Financial Summary') }}</title>
    <style>
        body { font-family: kalpurush, sans-serif; font-size: 12px; color: #222; line-height: 1.4; }
        h2 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #666; font-size: 11px; }
        .grid { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
        .card { flex: 1 1 30%; border: 1px solid #ddd; border-left: 4px solid #888; padding: 8px 10px; border-radius: 4px; }
        .card .label { color: #666; font-size: 11px; }
        .card .value { font-size: 15px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background: #f3f3f3; }
        td.num, th.num { text-align: right; }
        .section-title { font-size: 13px; font-weight: bold; margin-top: 16px; }
    </style>
</head>
<body>
    <h2>{{ \App\Models\Setting::get('org_name', config('app.name')) }}</h2>
    <div class="muted">{{ \App\Models\Setting::get('org_address', '') }}</div>
    <div class="muted">{{ __('Financial Summary') }} &mdash; {{ $dateFrom }} → {{ $dateTo }}</div>

    <div class="grid">
        <div class="card"><div class="label">{{ __('Savings Collection') }}</div><div class="value">৳{{ number_format($summary['collection'], 2) }}</div></div>
        <div class="card"><div class="label">{{ __('Savings Withdrawn') }}</div><div class="value">৳{{ number_format($summary['withdrawn'], 2) }}</div></div>
        <div class="card"><div class="label">{{ __('Savings Balance (as of end date)') }}</div><div class="value">৳{{ number_format($summary['balance_as_of_end'], 2) }}</div></div>
        <div class="card"><div class="label">{{ __('Loan Provided') }}</div><div class="value">৳{{ number_format($summary['loan_provided'], 2) }}</div></div>
        <div class="card"><div class="label">{{ __('Loan Repaid') }}</div><div class="value">৳{{ number_format($summary['loan_paid'], 2) }}</div></div>
        <div class="card"><div class="label">{{ __('Loan Outstanding (as of end date)') }}</div><div class="value">৳{{ number_format($summary['loan_remain'], 2) }}</div></div>
    </div>

    <div class="section-title">{{ __('Savings by Program') }}</div>
    <table>
        <thead><tr><th>{{ __('Program') }}</th><th class="num">{{ __('Collected') }}</th><th class="num">{{ __('Withdrawn') }}</th></tr></thead>
        <tbody>
            @forelse ($savingsByProgram as $row)
                <tr>
                    <td>{{ $row->program?->name ?? __('Unknown') }}</td>
                    <td class="num">৳{{ number_format($row->collected, 2) }}</td>
                    <td class="num">৳{{ number_format($row->withdrawn, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('No data found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">{{ __('Loan by Product') }}</div>
    <table>
        <thead><tr><th>{{ __('Product') }}</th><th class="num">{{ __('Provided') }}</th><th class="num">{{ __('Repaid') }}</th></tr></thead>
        <tbody>
            @forelse ($loanByProduct as $row)
                <tr>
                    <td>{{ $row->product?->name ?? __('Unknown') }}</td>
                    <td class="num">৳{{ number_format($row->provided, 2) }}</td>
                    <td class="num">৳{{ number_format($row->repaid, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('No data found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
