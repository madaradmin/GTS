<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar columna username como nullable inicialmente para migrar los datos existentes sin conflicto
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('name');
        });

        // 2. Poblado seguro de usernames para las cuentas institucionales existentes
        DB::table('users')->where('email', 'admin@aifa.aero')->update(['username' => 'admin']);
        DB::table('users')->where('email', 'apoc@aifa.aero')->update(['username' => 'apoc']);
        DB::table('users')->where('email', 'gts.mendoza@aifa.aero')->update(['username' => 'gts.mendoza']);
        DB::table('users')->where('email', 'gts.morales@aifa.aero')->update(['username' => 'gts.morales']);

        // Fallback robusto para cualquier otro usuario existente
        $usersWithoutUsername = DB::table('users')->whereNull('username')->get();
        foreach ($usersWithoutUsername as $user) {
            $base = ! empty($user->email) ? strtolower(explode('@', $user->email)[0]) : "usuario_{$user->id}";
            $candidate = $base;
            $counter = 1;
            while (DB::table('users')->where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                $candidate = "{$base}_{$counter}";
                $counter++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }

        // 3. Establecer username como único y no nulo; flexibilizar email como opcional (nullable)
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->unique()->change();
            $table->string('email', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 255)->nullable(false)->change();
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
