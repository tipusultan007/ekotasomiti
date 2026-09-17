<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: kalpurush, sans-serif; font-size: 12px; color: #111; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 16px; }
        .header h3 { margin: 0; }
        .header p { margin: 2px 0; color: #444; }
        .receipt-box { max-width: 460px; margin: 0 auto; }
        table { width: 100%; }
        .details td { padding: 3px 0; }
        .details td:first-child { width: 38%; color: #555; }
        .amount-row { border-top: 1px solid #aaa; border-bottom: 2px solid #333; font-weight: bold; }
        .signature { text-align: right; margin-top: 40px; }
        .signature .line { display: inline-block; border-top: 1px solid #333; width: 180px; text-align: center; padding-top: 4px; font-size: 11px; }
        .badge-label { font-size: 11px; color: #555; }
    </style>
</head>
<body>
    <div class="receipt-box">
        <div class="header">
            <h3>{{ $orgName }}</h3>
            <p>{{ $orgAddress }}</p>
            <p>{{ __('Phone') }}: {{ $orgPhone }}</p>
        </div>

        <table style="margin-bottom: 14px;">
            <tr>
                <td class="badge-label">{{ __('Receipt No') }}</td>
                <td><strong>{{ $receiptNo }}</strong></td>
                <td class="badge-label" style="text-align:right;">{{ __('Date') }}</td>
                <td style="text-align:right;"><strong>{{ $date->format('d-m-Y') }}</strong></td>
            </tr>
        </table>

        <table class="details">
            <tr><td>{{ __('Member') }}</td><td><strong>{{ $member->name }}</strong> ({{ $member->member_no }})</td></tr>
            <tr><td>{{ __('Account No') }}</td><td><strong>{{ $accountNo }}</strong></td></tr>
            <tr><td>{{ __('Program') }}</td><td>{{ $program }}</td></tr>
            @if (!empty($fundAmount) && $fundAmount > 0)
                <tr class="amount-row"><td>{{ __('Total Paid') }}</td><td style="text-align:right;">৳ {{ number_format($grossAmount, 2) }}</td></tr>
                <tr><td>{{ __('Savings Deposit') }}</td><td style="text-align:right;">৳ {{ number_format($amount, 2) }}</td></tr>
                <tr><td>{{ $fundName }}</td><td style="text-align:right;">৳ {{ number_format($fundAmount, 2) }}</td></tr>
            @else
                <tr class="amount-row"><td>{{ __($typeLabel) }} {{ __('Amount') }}</td><td style="text-align:right;">৳ {{ number_format($amount, 2) }}</td></tr>
            @endif
            @if (!empty($principal))
                <tr><td>{{ __('Principal Paid') }}</td><td style="text-align:right;">৳ {{ number_format($principal, 2) }}</td></tr>
                <tr><td>{{ __('Interest Paid') }}</td><td style="text-align:right;">৳ {{ number_format($interest, 2) }}</td></tr>
                @if ($lateFee > 0)
                    <tr><td>{{ __('Late Fee') }}</td><td style="text-align:right;">৳ {{ number_format($lateFee, 2) }}</td></tr>
                @endif
            @endif
            <tr><td>{{ __('Payment Method') }}</td><td>{{ __(ucfirst($paymentMethod)) }}</td></tr>
            <tr><td>{{ __('Field Officer') }}</td><td>{{ $fieldOfficer ?? '—' }}</td></tr>
            <tr><td>{{ __('Received By') }}</td><td>{{ $receivedBy }}</td></tr>
            <tr><td>{{ __('Previous Balance') }}</td><td style="text-align:right;">৳ {{ number_format($previousBalance, 2) }}</td></tr>
            <tr><td>{{ __('Balance After') }}</td><td style="text-align:right;"><strong>৳ {{ number_format($balanceAfter, 2) }}</strong></td></tr>
        </table>

        <div class="signature">
            <span class="line">{{ __('Authorized Signature') }}</span>
        </div>
    </div>
</body>
</html>