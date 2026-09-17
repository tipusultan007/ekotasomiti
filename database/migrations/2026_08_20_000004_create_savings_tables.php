<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->string('prefix');
            $table->decimal('min_deposit', 12, 2)->nullable();
            $table->decimal('expected_deposit', 12, 2)->nullable();
            $table->decimal('max_balance', 14, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_no')->unique();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('savings_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('field_officer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('opening_date');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->decimal('min_deposit', 12, 2)->nullable();
            $table->decimal('expected_deposit', 12, 2)->nullable();
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->date('closed_date')->nullable();
            $table->string('closing_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txn_no')->unique();
            $table->foreignId('savings_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('savings_program_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'adjustment', 'correction', 'transfer', 'account_opening', 'account_closing']);
            $table->decimal('amount', 14, 2);
            $table->date('txn_date');
            $table->date('collection_date')->nullable();
            $table->enum('payment_method', ['cash', 'bank', 'bkash', 'nagad', 'other'])->default('cash');
            $table->foreignId('field_officer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('balance_after', 14, 2)->nullable();
            $table->enum('status', ['posted', 'reversed', 'cancelled'])->default('posted');
            $table->foreignId('reversed_txn_id')->nullable()->constrained('savings_transactions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('savings_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->unique();
            $table->foreignId('savings_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('requested_amount', 14, 2);
            $table->string('purpose')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('approved_amount', 14, 2)->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_withdrawals');
        Schema::dropIfExists('savings_transactions');
        Schema::dropIfExists('savings_accounts');
        Schema::dropIfExists('savings_programs');
    }
};