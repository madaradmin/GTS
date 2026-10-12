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
        Schema::create('incident_assigned_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('area_code', 50);
            $table->string('area_name', 100);
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->timestamps();

            $table->unique(['incident_id', 'area_code']);
            $table->index(['incident_id', 'role_id']);
        });

        // Backfill histórico de incidencias existentes para preservar asignaciones previas
        $existingIncidents = DB::table('incidents')->get();
        $roles = DB::table('roles')->get()->keyBy('id');

        foreach ($existingIncidents as $inc) {
            // 1. Si tenía asignado un técnico individual
            if ($inc->assigned_to_user_id) {
                $user = DB::table('users')->where('id', $inc->assigned_to_user_id)->first();
                if ($user && $user->role_id && isset($roles[$user->role_id])) {
                    $role = $roles[$user->role_id];
                    DB::table('incident_assigned_areas')->insertOrIgnore([
                        'incident_id' => $inc->id,
                        'area_code' => $role->slug,
                        'area_name' => $role->name,
                        'role_id' => $role->id,
                        'created_at' => $inc->assigned_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // 2. Si estaba asignada al área externa TICS
            if ($inc->assigned_specialist && strtoupper($inc->assigned_specialist) === 'TICS') {
                DB::table('incident_assigned_areas')->insertOrIgnore([
                    'incident_id' => $inc->id,
                    'area_code' => 'tics',
                    'area_name' => 'TICS',
                    'role_id' => null,
                    'created_at' => $inc->assigned_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_assigned_areas');
    }
};

