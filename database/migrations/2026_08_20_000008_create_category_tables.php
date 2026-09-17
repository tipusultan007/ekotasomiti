<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->nullable()->after('category')->constrained('expense_categories')->nullOnDelete();
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->foreignId('income_category_id')->nullable()->after('category')->constrained('income_categories')->nullOnDelete();
        });

        DB::table('expenses')->select('category')->distinct()->whereNotNull('category')->get()
            ->each(function ($row) {
                $id = DB::table('expense_categories')->insertGetId([
                    'name' => $row->category,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('expenses')->where('category', $row->category)->update(['expense_category_id' => $id]);
            });

        DB::table('incomes')->select('category')->distinct()->whereNotNull('category')->get()
            ->each(function ($row) {
                $id = DB::table('income_categories')->insertGetId([
                    'name' => $row->category,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('incomes')->where('category', $row->category)->update(['income_category_id' => $id]);
            });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('category')->nullable()->after('expense_category_id');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->string('category')->nullable()->after('income_category_id');
        });

        DB::table('expense_categories')->get()->each(function ($category) {
            DB::table('expenses')->where('expense_category_id', $category->id)->update(['category' => $category->name]);
        });

        DB::table('income_categories')->get()->each(function ($category) {
            DB::table('incomes')->where('income_category_id', $category->id)->update(['category' => $category->name]);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn('expense_category_id');
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropForeign(['income_category_id']);
            $table->dropColumn('income_category_id');
        });

        Schema::dropIfExists('income_categories');
        Schema::dropIfExists('expense_categories');
    }
};
