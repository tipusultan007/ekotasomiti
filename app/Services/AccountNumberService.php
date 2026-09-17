<?php

namespace App\Services;

use App\Models\AccountSequence;
use App\Models\Setting;
use App\Models\TransactionSequence;
use Illuminate\Support\Facades\DB;

class AccountNumberService
{
    public function nextAccountNumber(string $prefix, ?string $areaCode = null): string
    {
        return DB::transaction(function () use ($prefix, $areaCode) {
            $seq = AccountSequence::where('key', 'account_' . $prefix)->lockForUpdate()->first();

            if (! $seq) {
                $seq = AccountSequence::create([
                    'key' => 'account_' . $prefix,
                    'prefix' => $prefix,
                    'last_number' => 0,
                    'length' => 6,
                ]);
            }

            $seq->increment('last_number');
            $seq->refresh();

            $number = str_pad((string) $seq->last_number, $seq->length, '0', STR_PAD_LEFT);
            $format = (string) Setting::get('account_number_format', '{prefix}-{sequence}');

            $formatted = str_replace(
                ['{prefix}', '{sequence}', '{year}', '{area}'],
                [$seq->prefix, $number, now()->format('Y'), $areaCode ?? ''],
                $format
            );

            return preg_replace('/-{2,}/', '-', trim($formatted, '-'));
        });
    }

    public function nextMemberNumber(int $length = 6): string
    {
        return DB::transaction(function () use ($length) {
            $seq = AccountSequence::where('key', 'member_no')->lockForUpdate()->first();

            if (! $seq) {
                $seq = AccountSequence::create([
                    'key' => 'member_no',
                    'prefix' => '',
                    'last_number' => 0,
                    'length' => $length,
                ]);
            }

            $seq->increment('last_number');
            $seq->refresh();

            return str_pad((string) $seq->last_number, $seq->length, '0', STR_PAD_LEFT);
        });
    }

    public function nextTransactionNumber(string $key, string $prefix = null, bool $yearly = false, string $date = null): string
    {
        return DB::transaction(function () use ($key, $prefix, $yearly, $date) {
            $year = $date ? substr($date, 0, 4) : now()->format('Y');
            $rowKey = $key;
            $rowPrefix = $prefix ?? strtoupper($key) . '-';

            if ($yearly) {
                $rowKey = $key . '_' . $year;
                $rowPrefix = ($prefix ?? strtoupper($key) . '-') . $year . '-';
            }

            $seq = TransactionSequence::where('key', $rowKey)->lockForUpdate()->first();

            if (! $seq) {
                $seq = TransactionSequence::create([
                    'key' => $rowKey,
                    'prefix' => $rowPrefix,
                    'last_number' => 0,
                    'length' => 6,
                    'yearly' => $yearly,
                ]);
            }

            $seq->increment('last_number');
            $seq->refresh();

            return $seq->prefix . str_pad((string) $seq->last_number, $seq->length, '0', STR_PAD_LEFT);
        });
    }
}
