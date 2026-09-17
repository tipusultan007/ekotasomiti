<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('prefix');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unsignedInteger('length')->default(6);
            $table->timestamps();
        });

        Schema::create('transaction_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('prefix');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unsignedInteger('length')->default(6);
            $table->boolean('yearly')->default(false);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->index(['model_type', 'model_id']);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('type')->default('document');
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('transaction_sequences');
        Schema::dropIfExists('account_sequences');
    }
};