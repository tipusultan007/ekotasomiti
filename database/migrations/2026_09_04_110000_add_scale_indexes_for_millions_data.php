<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->index('name', 'idx_members_name');
            $table->index('name_bn', 'idx_members_name_bn');
            $table->index('membership_date', 'idx_members_membership_date');
            $table->index(['area_id', 'status', 'name'], 'idx_members_area_stat_name');
            $table->index(['field_officer_id', 'status', 'name'], 'idx_members_fo_stat_name');
        });

        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->index(['savings_program_id', 'area_id', 'status'], 'idx_savings_acc_prog_area_stat');
            $table->index(['area_id', 'status', 'account_no'], 'idx_savings_acc_area_stat_accno');
            $table->index(['member_id', 'savings_program_id', 'status'], 'idx_savings_acc_mem_prog_stat');
        });

        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->index(['received_by', 'status', 'txn_date'], 'idx_savings_txn_rcv_stat_date');
            $table->index(['field_officer_id', 'status', 'type', 'txn_date'], 'idx_savings_txn_fo_stat_type_date');
            $table->index(['received_by', 'status', 'type', 'txn_date'], 'idx_savings_txn_rcv_stat_type_date');
            $table->index(['status', 'type', 'txn_date'], 'idx_savings_txn_stat_type_date');
            $table->index(['member_id', 'status', 'txn_date'], 'idx_savings_txn_mem_stat_date');
            $table->index(['area_id', 'status', 'txn_date'], 'idx_savings_txn_area_stat_date');
            $table->index('reversed_txn_id', 'idx_savings_txn_reversed_id');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->index(['frequency', 'status', 'area_id'], 'idx_loans_freq_stat_area');
            $table->index(['area_id', 'status', 'loan_no'], 'idx_loans_area_stat_loanno');
            $table->index(['member_id', 'loan_product_id', 'status'], 'idx_loans_mem_prod_stat');
            $table->index('application_id', 'idx_loans_app_id');
        });

        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->index(['loan_id', 'status', 'due_date'], 'idx_loan_sched_loan_stat_due');
            $table->index(['loan_id', 'installment_no'], 'idx_loan_sched_loan_inst');
            $table->index(['due_date', 'status'], 'idx_loan_sched_due_stat');
        });

        Schema::table('loan_transactions', function (Blueprint $table) {
            $table->index(['received_by', 'status', 'txn_date'], 'idx_loan_txn_rcv_stat_date');
            $table->index(['field_officer_id', 'type', 'status', 'txn_date'], 'idx_loan_txn_fo_type_stat_date');
            $table->index(['received_by', 'type', 'status', 'txn_date'], 'idx_loan_txn_rcv_type_stat_date');
            $table->index(['status', 'type', 'txn_date'], 'idx_loan_txn_stat_type_date');
            $table->index(['member_id', 'status', 'txn_date'], 'idx_loan_txn_mem_stat_date');
            $table->index(['area_id', 'status', 'txn_date'], 'idx_loan_txn_area_stat_date');
            $table->index('reversed_txn_id', 'idx_loan_txn_reversed_id');
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->index(['area_id', 'status', 'application_date'], 'idx_loan_apps_area_stat_date');
            $table->index(['field_officer_id', 'status', 'application_date'], 'idx_loan_apps_fo_stat_date');
            $table->index(['loan_product_id', 'status'], 'idx_loan_apps_prod_stat');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at', 'idx_audit_logs_created_at');
            $table->index(['user_id', 'created_at'], 'idx_audit_logs_user_created');
            $table->index(['action', 'created_at'], 'idx_audit_logs_action_created');
        });

        Schema::table('field_officer_settlements', function (Blueprint $table) {
            $table->index(['field_officer_id', 'status'], 'idx_stl_fo_status');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->index(['cash_register_id', 'status', 'created_at'], 'idx_cash_txn_reg_stat_created');
            $table->index(['status', 'created_at'], 'idx_cash_txn_stat_created');
            $table->index(['field_officer_id', 'status', 'created_at'], 'idx_cash_txn_fo_stat_created');
            $table->index('reversed_txn_id', 'idx_cash_txn_reversed_id');
        });

        Schema::table('member_documents', function (Blueprint $table) {
            $table->index(['member_id', 'type'], 'idx_member_docs_mem_type');
        });

        Schema::table('member_nominees', function (Blueprint $table) {
            $table->index(['member_id', 'priority'], 'idx_nominee_mem_priority');
            $table->index('mobile', 'idx_nominee_mobile');
            $table->index('nid', 'idx_nominee_nid');
        });

        Schema::table('loan_guarantors', function (Blueprint $table) {
            $table->index('mobile', 'idx_guarantor_mobile');
            $table->index('nid', 'idx_guarantor_nid');
        });
    }

    public function down(): void
    {
        Schema::table('loan_guarantors', function (Blueprint $table) {
            $table->dropIndex('idx_guarantor_mobile');
            $table->dropIndex('idx_guarantor_nid');
        });

        Schema::table('member_nominees', function (Blueprint $table) {
            $table->dropIndex('idx_nominee_mem_priority');
            $table->dropIndex('idx_nominee_mobile');
            $table->dropIndex('idx_nominee_nid');
        });

        Schema::table('member_documents', function (Blueprint $table) {
            $table->dropIndex('idx_member_docs_mem_type');
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cash_txn_reg_stat_created');
            $table->dropIndex('idx_cash_txn_stat_created');
            $table->dropIndex('idx_cash_txn_fo_stat_created');
            $table->dropIndex('idx_cash_txn_reversed_id');
        });

        Schema::table('field_officer_settlements', function (Blueprint $table) {
            $table->dropIndex('idx_stl_fo_status');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_created_at');
            $table->dropIndex('idx_audit_logs_user_created');
            $table->dropIndex('idx_audit_logs_action_created');
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropIndex('idx_loan_apps_area_stat_date');
            $table->dropIndex('idx_loan_apps_fo_stat_date');
            $table->dropIndex('idx_loan_apps_prod_stat');
        });

        Schema::table('loan_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_loan_txn_rcv_stat_date');
            $table->dropIndex('idx_loan_txn_fo_type_stat_date');
            $table->dropIndex('idx_loan_txn_rcv_type_stat_date');
            $table->dropIndex('idx_loan_txn_stat_type_date');
            $table->dropIndex('idx_loan_txn_mem_stat_date');
            $table->dropIndex('idx_loan_txn_area_stat_date');
            $table->dropIndex('idx_loan_txn_reversed_id');
        });

        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_loan_sched_loan_stat_due');
            $table->dropIndex('idx_loan_sched_loan_inst');
            $table->dropIndex('idx_loan_sched_due_stat');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('idx_loans_freq_stat_area');
            $table->dropIndex('idx_loans_area_stat_loanno');
            $table->dropIndex('idx_loans_mem_prod_stat');
            $table->dropIndex('idx_loans_app_id');
        });

        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_savings_txn_rcv_stat_date');
            $table->dropIndex('idx_savings_txn_fo_stat_type_date');
            $table->dropIndex('idx_savings_txn_rcv_stat_type_date');
            $table->dropIndex('idx_savings_txn_stat_type_date');
            $table->dropIndex('idx_savings_txn_mem_stat_date');
            $table->dropIndex('idx_savings_txn_area_stat_date');
            $table->dropIndex('idx_savings_txn_reversed_id');
        });

        Schema::table('savings_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_savings_acc_prog_area_stat');
            $table->dropIndex('idx_savings_acc_area_stat_accno');
            $table->dropIndex('idx_savings_acc_mem_prog_stat');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_name');
            $table->dropIndex('idx_members_name_bn');
            $table->dropIndex('idx_members_membership_date');
            $table->dropIndex('idx_members_area_stat_name');
            $table->dropIndex('idx_members_fo_stat_name');
        });
    }
};
