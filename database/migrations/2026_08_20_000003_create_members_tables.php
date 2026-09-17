<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_no')->unique();
            $table->date('membership_date');
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('father_husband_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->string('mobile')->nullable();
            $table->string('nid')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('field_officer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('occupation')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'closed'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('member_nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('nid')->nullable();
            $table->string('mobile')->nullable();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->tinyInteger('priority')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('member_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('other');
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_documents');
        Schema::dropIfExists('member_nominees');
        Schema::dropIfExists('members');
    }
};