<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Monthly Sheet') }}</title>
    <style>
        @page { size: A4 landscape; margin: 5mm; }
        body { font-family: kalpurush, sans-serif; font-size: 7px; color: #222; }
        h3 { font-size: 13px; margin: 0 0 2px; }
        .muted { color: #666; font-size: 7px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        th, td { border: 1px solid #ccc; padding: 1px 2px; text-align: left; word-wrap: break-word; line-height: 1.1; }
        th { background: #f3f3f3; text-align: right; font-size: 6px; white-space: nowrap; }
        th.txt, td.txt { text-align: left; }
        td.num, th.num { text-align: right; width: 2.4%; }
        th.txt, td.txt { width: auto; }
        tfoot td { font-weight: bold; background: #f9f9f9; }
    </style>
</head>
<body>
    <div style="text-align:center; border-bottom:1px solid #999; padding-bottom:4px; margin-bottom:6px;">
        <h3 style="margin:0;">{{ $orgName }}</h3>
        @if ($orgAddress)<div class="muted">{{ $orgAddress }}</div>@endif
        @if ($orgPhone)<div class="muted">{{ __('Phone') }}: {{ $orgPhone }}</div>@endif
    </div>
    <div class="muted" style="display:flex; justify-content:space-between; margin-bottom:4px;">
        <strong>{{ __('Monthly Sheet') }}</strong>
        <span>{{ __('Month') }}: {{ $monthLabel }} &mdash; {{ __('Type') }}: {{ __(ucfirst($type)) }} &mdash; {{ __('Frequency') }}: {{ __(ucfirst($frequency)) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="txt">#</th>
                <th class="txt">{{ __('Member Details') }}</th>
                <th class="txt">{{ __('Account No') }}</th>
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    <th class="num">{{ $d }}</th>
                @endfor
                <th class="num">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td class="txt">{{ $index + 1 }}</td>
                    <td class="txt">{{ $row->member_name }} ({{ $row->member_phone }})</td>
                    <td class="txt">{{ $row->account_no }}</td>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        <td class="num">{{ $row->cells[$d] > 0 ? number_format($row->cells[$d], 0) : '' }}</td>
                    @endfor
                    <td class="num">{{ number_format($row->row_total, 0) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $daysInMonth + 4 }}">{{ __('No accounts found.') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="txt"></td>
                <td class="txt" colspan="2">{{ __('Total') }}</td>
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    <td class="num">{{ $columnTotals[$d] > 0 ? number_format($columnTotals[$d], 0) : '' }}</td>
                @endfor
                <td class="num">{{ number_format($grandTotal, 0) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
