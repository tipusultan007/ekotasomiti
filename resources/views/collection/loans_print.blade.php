<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __(ucfirst($frequency)) }} {{ __('Loan Collection') }} - {{ $date }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h2 { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #666; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        .amount { text-align: right; }
        .sig { width: 100px; }
        .totals td { font-weight: bold; background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ \App\Models\Setting::get('org_name', config('app.name')) }}</h2>
        <p>{{ __(ucfirst($frequency)) }} {{ __('Loan Collection Sheet') }} — {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr><th>{{ __('Loan No') }}</th><th>{{ __('Member') }}</th><th class="amount">{{ __('Due Today') }}</th><th class="amount">{{ __('Overdue') }}</th><th class="amount">{{ __('Collected') }}</th><th class="amount">{{ __('Total Due') }}</th><th>{{ __('Signature') }}</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row->loan->loan_no }}</td>
                    <td>{{ $row->loan->member->name }}</td>
                    <td class="amount">{{ number_format($row->due_today, 2) }}</td>
                    <td class="amount">{{ number_format($row->overdue, 2) }}</td>
                    <td class="amount">{{ number_format($row->collected, 2) }}</td>
                    <td class="amount">{{ number_format($row->total_due, 2) }}</td>
                    <td class="sig"></td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">{{ __('Total') }}</td>
                <td class="amount">{{ number_format($totals['due_today'], 2) }}</td>
                <td class="amount">{{ number_format($totals['overdue'], 2) }}</td>
                <td class="amount">{{ number_format($totals['collected'], 2) }}</td>
                <td class="amount">{{ number_format($totals['total_due'], 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>