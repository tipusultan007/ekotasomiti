<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->string('prefix');
            $table->decimal('min_amount', 14, 2)->nullable();
            $table->decimal('max_amount', 14, 2)->nullable();
            $table->decimal('interest_rate', 8, 2)->default(0);
            $table->enum('interest_type', ['flat', 'reducing'])->default('flat');
            $table->decimal('processing_fee', 12, 2)->nullable();
            $table->decimal('insurance_fee', 12, 2)->nullable();
            $table->integer('min_term')->nullable();
            $table->integer('max_term')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_product_id')->constrained()->cascadeOnDelete();
            $table->decimal('requested_amount', 14, 2);
            $table->integer('requested_term');
            $table->string('purpose')->nullable();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('field_officer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('application_date');
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'disbursed', 'cancelled'])->default('draft');
            $table->text('verification_info')->nullable();
            $table->text('guarantor_info')->nullable();
            $table->text('documents_info')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('approved_amount', 14, 2)->nullable();
            $table->decimal('approved_interest_rate', 8, 2)->nullable();
            $table->integer('approved_term')->nullable();
            $table->decimal('approved_installment', 14, 2)->nullable();
            $table->text('approval_remarks')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('nid')->nullable();
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_no')->unique();
            $table->foreignId('application_id')->nullable()->constrained('loan_applications')->nullOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('field_officer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('principal_amount', 14, 2);
            $table->decimal('interest_rate', 8, 2)->default(0);
            $table->enum('interest_type', ['flat', 'reducing'])->default('flat');
            $table->integer('term');
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->decimal('installment_amount', 14, 2);
            $table->decimal('processing_fee', 12, 2)->nullable();
            $table->decimal('insurance_fee', 12, 2)->nullable();
            $table->decimal('total_interest', 14, 2)->default(0);
            $table->decimal('total_payable', 14, 2)->default(0);
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->decimal('outstanding', 14, 2)->default(0);
            $table->date('disbursement_date');
            $table->date('first_due_date');
            $table->enum('status', ['approved', 'disbursed', 'active', 'overdue', 'completed', 'written_off', 'cancelled'])->default('approved');
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->string('closing_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->integer('installment_no');
            $table->date('due_date');
            $table->decimal('principal', 14, 2)->default(0);
            $table->decimal('interest', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('paid', 14, 2)->default(0);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->enum('status', ['due', 'partial', 'paid', 'overdue', 'waived'])->default('due');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();
            $table->unique(['loan_id', 'installment_no']);
        });

        Schema::create('loan_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txn_no')->unique();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['disbursement', 'repayment', 'interest', 'fee', 'adjustment', 'reversal']);
            $table->decimal('amount', 14, 2);
            $table->decimal('principal_paid', 14, 2)->default(0);
            $table->decimal('interest_paid', 14, 2)->default(0);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->date('txn_date');
            $table->date('collection_date')->nullable();
            $table->enum('payment_method', ['cash', 'bank', 'bkash', 'nagad', 'other'])->default('cash');
            $table->foreignId('field_officer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['posted', 'reversed', 'cancelled'])->default('posted');
            $table->foreignId('reversed_txn_id')->nullable()->constrained('loan_transactions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('loan_disbursements', function (Blueprint $table) {
            $table->id();
            $table->string('disbursement_no')->unique();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->enum('payment_method', ['cash', 'bank', 'bkash', 'nagad', 'other'])->default('cash');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->foreignId('disbursed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('disbursed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('loan_transactions')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->decimal('principal_paid', 14, 2)->default(0);
            $table->decimal('interest_paid', 14, 2)->default(0);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->date('collection_date');
            $table->timestamps();
        });

        Schema::create('loan_repayment_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_repayment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_schedule_id')->constrained()->cascadeOnDelete();
            $table->decimal('principal_paid', 14, 2)->default(0);
            $table->decimal('interest_paid', 14, 2)->default(0);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['loan_repayment_id', 'loan_schedule_id'], 'lr_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayment_schedule');
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loan_disbursements');
        Schema::dropIfExists('loan_transactions');
        Schema::dropIfExists('loan_schedules');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('loan_guarantors');
        Schema::dropIfExists('loan_applications');
        Schema::dropIfExists('loan_products');
    }
};