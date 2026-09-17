<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private array $fkTables = [
        'members',
        'savings_accounts',
        'savings_transactions',
        'loans',
        'loan_applications',
        'loan_transactions',
        'cash_transactions',
        'field_officer_settlements',
    ];

    public function up(): void
    {
        Schema::create('user_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'area_id']);
        });

        // 1. Drop the foreign keys that reference the old field_officers table so
        //    we can remap field_officer_id values to users without constraint errors.
        foreach ($this->fkTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                try {
                    $table->dropForeign($tbl . '_field_officer_id_foreign');
                } catch (\Throwable $e) {
                    // foreign key may already be missing
                }
            });
        }

        // 2. Migrate each field officer into a user (field_officer role) and
        //    repoint every field_officer_id column to the user's id.
        $role = Role::firstOrCreate(['name' => 'field_officer', 'guard_name' => 'web']);

        $officers = DB::table('field_officers')->get();
        foreach ($officers as $fo) {
            if ($fo->user_id && DB::table('users')->where('id', $fo->user_id)->exists()) {
                $user = User::find($fo->user_id);
            } else {
                $email = 'fo_' . strtolower($fo->code) . '@ekota.com';
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $fo->name,
                        'phone' => $fo->mobile,
                        'password' => bcrypt('password'),
                        'is_active' => $fo->status === 'active',
                    ]
                );
            }

            $user->syncRoles([$role->name]);

            $areaIds = DB::table('field_officer_area')
                ->where('field_officer_id', $fo->id)
                ->pluck('area_id')
                ->all();

            foreach ($areaIds as $areaId) {
                DB::table('user_area')->updateOrInsert(
                    ['user_id' => $user->id, 'area_id' => $areaId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            foreach ($this->fkTables as $tbl) {
                DB::table($tbl)->where('field_officer_id', $fo->id)->update(['field_officer_id' => $user->id]);
            }
        }

        // 3. Re-add the foreign keys, this time referencing users.
        foreach ($this->fkTables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if ($tbl === 'field_officer_settlements') {
                    $table->foreign('field_officer_id')->references('id')->on('users')->cascadeOnDelete();
                } else {
                    $table->foreign('field_officer_id')->references('id')->on('users')->nullOnDelete();
                }
            });
        }

        // 4. Drop the obsolete tables.
        Schema::dropIfExists('field_officer_area');
        Schema::dropIfExists('field_officers');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_area');
    }
};
