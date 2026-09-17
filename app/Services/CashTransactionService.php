<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CashTransactionService
{
    public function openRegister(string $date = null): CashRegister
    {
        $date = $date ?? now()->toDateString();

        $register = CashRegister::where('register_date', $date)->where('status', 'open')->first();

        if ($register) {
            return $register;
        }

        $previous = CashRegister::where('register_date', '<', $date)->orderByDesc('register_date')->first();
        $opening = $previous?->closing_balance ?? $previous?->opening_balance ?? 0;

        return CashRegister::firstOrCreate(
            ['register_date' => $date],
            [
                'opening_balance' => $opening,
                'total_in' => 0,
                'total_out' => 0,
                'opened_by' => auth()->id(),
                'opened_at' => now(),
                'status' => 'open',
            ]
        );
    }

    public function record(
        ?CashRegister $register,
        string $type,
        string $direction,
        float $amount,
        ?Model $source = null,
        ?int $fieldOfficerId = null,
        ?string $paymentMethod = 'cash',
        ?string $reference = null,
        ?string $notes = null,
        ?string $txnNo = null,
    ): CashTransaction {
        return DB::transaction(function () use (
            $register, $type, $direction, $amount, $source, $fieldOfficerId,
            $paymentMethod, $reference, $notes, $txnNo
        ) {
            if (! $register) {
                $register = $this->openRegister();
            }

            $txnNo ??= app(AccountNumberService::class)->nextTransactionNumber('cash_txn', 'CT-', true);

            $txn = CashTransaction::create([
                'txn_no' => $txnNo,
                'cash_register_id' => $register->id,
                'type' => $type,
                'direction' => $direction,
                'amount' => $amount,
                'source_type' => $source ? $source::class : null,
                'source_id' => $source?->id,
                'field_officer_id' => $fieldOfficerId,
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => auth()->id(),
                'status' => 'posted',
            ]);

            if ($direction === 'in') {
                $register->increment('total_in', $amount);
            } else {
                $register->increment('total_out', $amount);
            }

            return $txn;
        });
    }

    public function reverseSource(Model $source): void
    {
        CashTransaction::where('source_type', $source::class)
            ->where('source_id', $source->id)
            ->where('status', 'posted')
            ->get()
            ->each(function (CashTransaction $txn) {
                $this->reverse($txn);
            });
    }

    public function deleteSource(Model $source): void
    {
        CashTransaction::where('source_type', $source::class)
            ->where('source_id', $source->id)
            ->where('status', 'posted')
            ->get()
            ->each(function (CashTransaction $txn) {
                $this->delete($txn);
            });
    }

    public function delete(CashTransaction $txn): void
    {
        if ($txn->status !== 'posted') {
            throw new \RuntimeException('Only posted cash transactions can be deleted.');
        }

        DB::transaction(function () use ($txn) {
            $register = $txn->register;
            if ($txn->direction === 'in') {
                $register->decrement('total_in', $txn->amount);
            } else {
                $register->decrement('total_out', $txn->amount);
            }
            $txn->delete();
        });
    }

    public function updateSourceAmount(Model $source, float $newAmount): void
    {
        CashTransaction::where('source_type', $source::class)
            ->where('source_id', $source->id)
            ->where('status', 'posted')
            ->get()
            ->each(function (CashTransaction $txn) use ($newAmount) {
                $diff = $newAmount - (float) $txn->amount;
                if (abs($diff) < 0.0001) {
                    return;
                }
                $register = $txn->register;
                if ($txn->direction === 'in') {
                    $register->increment('total_in', $diff);
                } else {
                    $register->increment('total_out', $diff);
                }
                $txn->update(['amount' => $newAmount]);
            });
    }

    public function reverse(CashTransaction $txn): CashTransaction
    {
        if ($txn->status !== 'posted') {
            throw new \RuntimeException('Only posted cash transactions can be reversed.');
        }

        return DB::transaction(function () use ($txn) {
            $register = $txn->register;

            $reversal = CashTransaction::create([
                'txn_no' => app(AccountNumberService::class)->nextTransactionNumber('cash_reversal', 'CR-', true),
                'cash_register_id' => $register->id,
                'type' => 'adjustment',
                'direction' => $txn->direction === 'in' ? 'out' : 'in',
                'amount' => $txn->amount,
                'field_officer_id' => $txn->field_officer_id,
                'payment_method' => $txn->payment_method,
                'reference' => $txn->txn_no,
                'notes' => "Reversal of {$txn->txn_no}",
                'created_by' => auth()->id(),
                'status' => 'posted',
                'reversed_txn_id' => $txn->id,
            ]);

            if ($txn->direction === 'in') {
                $register->decrement('total_in', $txn->amount);
            } else {
                $register->decrement('total_out', $txn->amount);
            }

            $txn->update(['status' => 'reversed']);

            return $reversal;
        });
    }

    public function closeRegister(CashRegister $register, float $physicalCash = null): CashRegister
    {
        if ($register->status === 'closed') {
            throw new \RuntimeException('Register is already closed.');
        }

        return DB::transaction(function () use ($register, $physicalCash) {
            $calculated = (float) $register->opening_balance + (float) $register->total_in - (float) $register->total_out;

            $register->closing_balance = $physicalCash ?? $calculated;
            $register->total_in = $register->total_in;
            $register->total_out = $register->total_out;
            $register->closed_by = auth()->id();
            $register->closed_at = now();
            $register->status = 'closed';
            $register->save();

            return $register;
        });
    }
}
