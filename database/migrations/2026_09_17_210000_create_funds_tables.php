<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txn_no')->unique();
            $table->foreignId('fund_id')->constrained('funds')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('savings_account_id')->nullable()->constrained('savings_accounts')->nullOnDelete();
            $table->foreignId('savings_transaction_id')->nullable()->constrained('savings_transactions')->nullOnDelete();
            $table->enum('type', ['contribution', 'disbursement', 'adjustment']);
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->date('txn_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('payment_method', ['cash', 'bank', 'bkash', 'nagad', 'other'])->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transactions');
        Schema::dropIfExists('funds');
    }
};

