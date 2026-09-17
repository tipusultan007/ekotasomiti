<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('written_off_amount', 14, 2)->nullable();
            $table->string('write_off_reason')->nullable();
            $table->foreignId('written_off_by')->nullable()->after('closing_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('written_off_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('written_off_by');
            $table->dropColumn(['written_off_amount', 'write_off_reason', 'written_off_at']);
        });
    }
};