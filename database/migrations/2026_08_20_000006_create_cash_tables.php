<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->date('register_date')->unique();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('closing_balance', 14, 2)->nullable();
            $table->decimal('total_in', 14, 2)->default(0);
            $table->decimal('total_out', 14, 2)->default(0);
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txn_no')->unique();
            $table->foreignId('cash_register_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'savings_collection', 'loan_collection', 'other_income', 'withdrawal_payment',
                'loan_disbursement', 'expense', 'bank_deposit', 'bank_withdrawal',
                'officer_submission', 'receive', 'payment', 'adjustment', 'opening', 'closing',
            ])->default('receive');
            $table->enum('direction', ['in', 'out'])->default('in');
            $table->decimal('amount', 14, 2);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('field_officer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('payment_method', ['cash', 'bank', 'bkash', 'nagad', 'other'])->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['posted', 'reversed', 'cancelled'])->default('posted');
            $table->foreignId('reversed_txn_id')->nullable()->constrained('cash_transactions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('field_officer_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_no')->unique();
            $table->foreignId('field_officer_id')->constrained()->cascadeOnDelete();
            $table->date('settlement_date');
            $table->decimal('savings_collection', 14, 2)->default(0);
            $table->decimal('loan_collection', 14, 2)->default(0);
            $table->decimal('other_collection', 14, 2)->default(0);
            $table->decimal('total_collection', 14, 2)->default(0);
            $table->decimal('cash_submitted', 14, 2)->default(0);
            $table->decimal('remaining_cash', 14, 2)->default(0);
            $table->foreignId('cash_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->enum('status', ['pending', 'submitted', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_officer_settlements');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('cash_registers');
    }
};