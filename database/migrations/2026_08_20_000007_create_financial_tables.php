<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->string('bank_name');
            $table->string('branch')->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txn_no')->unique();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'transfer']);
            $table->enum('direction', ['in', 'out']);
            $table->decimal('amount', 14, 2);
            $table->date('txn_date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_no')->unique();
            $table->date('expense_date');
            $table->string('category')->nullable();
            $table->decimal('amount', 14, 2);
            $table->enum('payment_method', ['cash', 'bank', 'bkash', 'nagad', 'other'])->default('cash');
            $table->string('payee')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['posted', 'reversed', 'cancelled'])->default('posted');
            $table->foreignId('reversed_txn_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('income_no')->unique();
            $table->date('income_date');
            $table->string('category')->nullable();
            $table->decimal('amount', 14, 2);
            $table->enum('payment_method', ['cash', 'bank', 'bkash', 'nagad', 'other'])->default('cash');
            $table->string('source')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['posted', 'reversed', 'cancelled'])->default('posted');
            $table->foreignId('reversed_txn_id')->nullable()->constrained('incomes')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
    }
};