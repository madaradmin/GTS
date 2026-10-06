<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Asegurar roles APOC-CCO y APOC-CSA
        $ccoRole = Role::firstOrCreate(
            ['slug' => 'apoc-cco'],
            [
                'name' => 'APOC-CCO',
                'description' => 'Centro de Control Operativo - Reporte, seguimiento y cierre de incidencias CCO.',
            ]
        );

        $csaRole = Role::firstOrCreate(
            ['slug' => 'apoc-csa'],
            [
                'name' => 'APOC-CSA',
                'description' => 'Centro de Servicios Aeroportuarios - Reporte, seguimiento y cierre de incidencias CSA.',
            ]
        );

        // 2. Estandarizar descripciones y nombres de roles existentes
        Role::where('slug', 'apoc')->update([
            'description' => 'Centro de Operaciones Aeroportuarias - Rol base heredado.',
        ]);

        Role::where('slug', 'gts')->update([
            'name' => 'GTS',
            'description' => 'Grupo de Seguridad Tecnológica - Asignación, diagnóstico, atención y solución técnica en campo.',
        ]);

        Role::where('slug', 'admin')->update([
            'name' => 'ADMINISTRADOR',
            'description' => 'Supervisión general, administración de usuarios, catálogos y auditoría global.',
        ]);

        // 3. Migrar cuentas existentes de CCO y CSA según su login o placa
        User::where(function ($q) {
            $q->where('username', 'cco.aifa')
                ->orWhere('badge_number', 'CCO.001');
        })->update(['role_id' => $ccoRole->id]);

        User::where(function ($q) {
            $q->where('username', 'csa.aifa')
                ->orWhere('badge_number', 'CSA.001');
        })->update(['role_id' => $csaRole->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $legacyRole = Role::where('slug', 'apoc')->first();

        if ($legacyRole) {
            User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc-cco', 'apoc-csa']))
                ->update(['role_id' => $legacyRole->id]);
        }

        Role::whereIn('slug', ['apoc-cco', 'apoc-csa'])->delete();
    }
};
