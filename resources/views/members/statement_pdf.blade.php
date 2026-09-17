<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Statement - :name', ['name' => $member->name]) }}</title>
    <style>
        body { font-family: kalpurush, sans-serif; font-size: 12px; color: #111; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 16px; }
        .header h2 { margin: 0; font-size: 20px; font-weight: bold; }
        .header p { margin: 2px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px; }
        th { background: #eee; text-align: left; }
        .amount { text-align: right; }
        .info { margin-bottom: 16px; width: 100%; }
        .info td { border: none; padding: 3px 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ \App\Models\Setting::get('org_name', config('app.name')) }}</h2>
        <p>{{ \App\Models\Setting::get('org_address', '') }}</p>
        <p>{{ __('Member Statement') }}</p>
    </div>

    <table class="info">
        <tr>
            <td><strong>{{ __('Member') }}:</strong> {{ $member->name }}</td>
            <td><strong>{{ __('Member No') }}:</strong> {{ $member->member_no }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('Mobile') }}:</strong> {{ $member->mobile }}</td>
            <td><strong>{{ __('NID') }}:</strong> {{ $member->nid }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('Area') }}:</strong> {{ $member->area?->name }}</td>
            <td><strong>{{ __('Officer') }}:</strong> {{ $member->fieldOfficer?->name }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>{{ __('Date') }}</th><th>{{ __('Account') }}</th><th>{{ __('Type') }}</th>
                <th class="amount">{{ __('Debit') }}</th><th class="amount">{{ __('Credit') }}</th><th class="amount">{{ __('Balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ledger as $txn)
                <tr>
                    <td>{{ $txn->ledger_date->translatedFormat('d-m-Y') }}</td>
                    <td>{{ $txn->ledger_account }}</td>
                    <td>{{ $txn->ledger_type }}</td>
                    <td class="amount">{{ $txn->ledger_debit > 0 ? number_format($txn->ledger_debit, 2) : '' }}</td>
                    <td class="amount">{{ $txn->ledger_credit > 0 ? number_format($txn->ledger_credit, 2) : '' }}</td>
                    <td class="amount">{{ $txn->ledger_balance !== null ? number_format($txn->ledger_balance, 2) : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">{{ __('No transactions.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>