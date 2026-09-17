<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            if (! Schema::hasColumn('funds', 'type')) {
                $table->string('type')->default('welfare')->after('name');
            }
        });

        Schema::table('fund_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('fund_transactions', 'reference')) {
                $table->string('reference')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('fund_transactions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('received_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('fund_transactions', 'status')) {
                $table->enum('status', ['posted', 'reversed'])->default('posted')->after('notes');
            }
            if (! Schema::hasColumn('fund_transactions', 'reversed_txn_id')) {
                $table->foreignId('reversed_txn_id')->nullable()->after('status')->constrained('fund_transactions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('fund_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('fund_transactions', 'reversed_txn_id')) {
                $table->dropConstrainedForeignId('reversed_txn_id');
            }
            if (Schema::hasColumn('fund_transactions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('fund_transactions', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('fund_transactions', 'reference')) {
                $table->dropColumn('reference');
            }
        });

        Schema::table('funds', function (Blueprint $table) {
            if (Schema::hasColumn('funds', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};

