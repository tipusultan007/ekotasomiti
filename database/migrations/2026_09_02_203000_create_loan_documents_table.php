<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('other');
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_documents');
    }
};
