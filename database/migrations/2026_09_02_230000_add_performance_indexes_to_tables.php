<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->index('status', 'idx_members_status');
            $table->index('mobile', 'idx_members_mobile');
            $table->index('nid', 'idx_members_nid');
            $table->index(['area_id', 'status'], 'idx_members_area_status');
            $table->index(['field_officer_id', 'status'], 'idx_members_officer_status');
        });

        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->index('status', 'idx_savings_acc_status');
            $table->index('opening_date', 'idx_savings_acc_opening_date');
            $table->index(['member_id', 'status'], 'idx_savings_acc_member_status');
            $table->index(['area_id', 'status'], 'idx_savings_acc_area_status');
            $table->index(['field_officer_id', 'status'], 'idx_savings_acc_officer_status');
            $table->index(['savings_program_id', 'status'], 'idx_savings_acc_prog_status');
        });

        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->index('txn_date', 'idx_savings_txn_date');
            $table->index('collection_date', 'idx_savings_txn_col_date');
            $table->index('type', 'idx_savings_txn_type');
            $table->index('status', 'idx_savings_txn_status');
            $table->index(['savings_account_id', 'status', 'txn_date'], 'idx_savings_txn_acc_stat_date');
            $table->index(['member_id', 'txn_date'], 'idx_savings_txn_member_date');
            $table->index(['field_officer_id', 'txn_date'], 'idx_savings_txn_officer_date');
            $table->index(['area_id', 'txn_date'], 'idx_savings_txn_area_date');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->index('status', 'idx_loans_status');
            $table->index('frequency', 'idx_loans_frequency');
            $table->index('disbursement_date', 'idx_loans_disbursement_date');
            $table->index(['member_id', 'status'], 'idx_loans_member_status');
            $table->index(['area_id', 'status'], 'idx_loans_area_status');
            $table->index(['field_officer_id', 'status'], 'idx_loans_officer_status');
            $table->index(['loan_product_id', 'status'], 'idx_loans_prod_status');
        });

        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->index('due_date', 'idx_loan_sched_due_date');
            $table->index('status', 'idx_loan_sched_status');
            $table->index(['loan_id', 'status'], 'idx_loan_sched_loan_status');
            $table->index(['status', 'due_date'], 'idx_loan_sched_stat_due');
        });

        Schema::table('loan_transactions', function (Blueprint $table) {
            $table->index('txn_date', 'idx_loan_txn_date');
            $table->index('collection_date', 'idx_loan_txn_col_date');
            $table->index('type', 'idx_loan_txn_type');
            $table->index('status', 'idx_loan_txn_status');
            $table->index(['loan_id', 'type', 'status', 'txn_date'], 'idx_loan_txn_lookup');
            $table->index(['member_id', 'txn_date'], 'idx_loan_txn_member_date');
            $table->index(['field_officer_id', 'txn_date'], 'idx_loan_txn_officer_date');
            $table->index(['area_id', 'txn_date'], 'idx_loan_txn_area_date');
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->index('status', 'idx_loan_apps_status');
            $table->index('application_date', 'idx_loan_apps_date');
            $table->index(['member_id', 'status'], 'idx_loan_apps_member_status');
            $table->index(['field_officer_id', 'status'], 'idx_loan_apps_officer_status');
            $table->index(['area_id', 'status'], 'idx_loan_apps_area_status');
        });

        Schema::table('field_officer_settlements', function (Blueprint $table) {
            $table->index('settlement_date', 'idx_stl_date');
            $table->index('status', 'idx_stl_status');
            $table->index(['field_officer_id', 'settlement_date'], 'idx_stl_officer_date');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->index('type', 'idx_cash_txn_type');
            $table->index('direction', 'idx_cash_txn_dir');
            $table->index('status', 'idx_cash_txn_status');
            $table->index(['source_type', 'source_id'], 'idx_cash_txn_source');
        });

        Schema::table('savings_withdrawals', function (Blueprint $table) {
            $table->index('status', 'idx_savings_wdr_status');
            $table->index('requested_at', 'idx_savings_wdr_req_at');
            $table->index(['member_id', 'status'], 'idx_savings_wdr_mem_status');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_status');
            $table->dropIndex('idx_members_mobile');
            $table->dropIndex('idx_members_nid');
            $table->dropIndex('idx_members_area_status');
            $table->dropIndex('idx_members_officer_status');
        });

        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_savings_acc_status');
            $table->dropIndex('idx_savings_acc_opening_date');
            $table->dropIndex('idx_savings_acc_member_status');
            $table->dropIndex('idx_savings_acc_area_status');
            $table->dropIndex('idx_savings_acc_officer_status');
            $table->dropIndex('idx_savings_acc_prog_status');
        });

        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_savings_txn_date');
            $table->dropIndex('idx_savings_txn_col_date');
            $table->dropIndex('idx_savings_txn_type');
            $table->dropIndex('idx_savings_txn_status');
            $table->dropIndex('idx_savings_txn_acc_stat_date');
            $table->dropIndex('idx_savings_txn_member_date');
            $table->dropIndex('idx_savings_txn_officer_date');
            $table->dropIndex('idx_savings_txn_area_date');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('idx_loans_status');
            $table->dropIndex('idx_loans_frequency');
            $table->dropIndex('idx_loans_disbursement_date');
            $table->dropIndex('idx_loans_member_status');
            $table->dropIndex('idx_loans_area_status');
            $table->dropIndex('idx_loans_officer_status');
            $table->dropIndex('idx_loans_prod_status');
        });

        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_loan_sched_due_date');
            $table->dropIndex('idx_loan_sched_status');
            $table->dropIndex('idx_loan_sched_loan_status');
            $table->dropIndex('idx_loan_sched_stat_due');
        });

        Schema::table('loan_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_loan_txn_date');
            $table->dropIndex('idx_loan_txn_col_date');
            $table->dropIndex('idx_loan_txn_type');
            $table->dropIndex('idx_loan_txn_status');
            $table->dropIndex('idx_loan_txn_lookup');
            $table->dropIndex('idx_loan_txn_member_date');
            $table->dropIndex('idx_loan_txn_officer_date');
            $table->dropIndex('idx_loan_txn_area_date');
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropIndex('idx_loan_apps_status');
            $table->dropIndex('idx_loan_apps_date');
            $table->dropIndex('idx_loan_apps_member_status');
            $table->dropIndex('idx_loan_apps_officer_status');
            $table->dropIndex('idx_loan_apps_area_status');
        });

        Schema::table('field_officer_settlements', function (Blueprint $table) {
            $table->dropIndex('idx_stl_date');
            $table->dropIndex('idx_stl_status');
            $table->dropIndex('idx_stl_officer_date');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cash_txn_type');
            $table->dropIndex('idx_cash_txn_dir');
            $table->dropIndex('idx_cash_txn_status');
            $table->dropIndex('idx_cash_txn_source');
        });

        Schema::table('savings_withdrawals', function (Blueprint $table) {
            $table->dropIndex('idx_savings_wdr_status');
            $table->dropIndex('idx_savings_wdr_req_at');
            $table->dropIndex('idx_savings_wdr_mem_status');
        });
    }
};
