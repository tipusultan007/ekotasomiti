<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('field_officers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('nid')->nullable();
            $table->text('address')->nullable();
            $table->date('joining_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('commission_rate', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('field_officer_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_officer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['field_officer_id', 'area_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('field_officer_area');
        Schema::dropIfExists('field_officers');
        Schema::dropIfExists('areas');
    }
};