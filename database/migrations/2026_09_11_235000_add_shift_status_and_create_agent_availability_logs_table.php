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
        // 1. Agregar estado de turno y disponibilidad en la tabla users
        Schema::table('users', function (Blueprint $table) {
            $table->string('shift_status', 30)->default('ACTIVO')->after('badge_number');
            $table->timestamp('shift_status_updated_at')->nullable()->after('shift_status');
        });

        // Inicializar la fecha de actualización para usuarios existentes
        DB::table('users')->update([
            'shift_status' => 'ACTIVO',
            'shift_status_updated_at' => now(),
        ]);

        // 2. Crear tabla inmutable para auditoría e historial de disponibilidad de agentes
        Schema::create('agent_availability_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30);
            $table->string('previous_status', 30)->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Índices para optimizar consultas de auditoría y disponibilidad operativa
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_availability_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shift_status', 'shift_status_updated_at']);
        });
    }
};
