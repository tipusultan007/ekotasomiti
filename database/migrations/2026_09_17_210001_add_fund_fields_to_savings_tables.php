<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savings_programs', function (Blueprint $table) {
            $table->foreignId('fund_id')->nullable()->after('max_balance')->constrained('funds')->nullOnDelete();
            $table->decimal('fund_contribution', 12, 2)->default(0)->after('fund_id');
        });

        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->decimal('gross_amount', 14, 2)->nullable()->after('amount');
            $table->decimal('fund_amount', 12, 2)->default(0)->after('gross_amount');
            $table->foreignId('fund_transaction_id')->nullable()->after('fund_amount')->constrained('fund_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fund_transaction_id');
            $table->dropColumn(['gross_amount', 'fund_amount']);
        });

        Schema::table('savings_programs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fund_id');
            $table->dropColumn('fund_contribution');
        });
    }
};

