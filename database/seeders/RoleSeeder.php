<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Operador APOC',
                'slug' => 'apoc',
                'description' => 'Centro de Operaciones Aeroportuarias - Reporte, seguimiento y cierre de incidencias.',
            ],
            [
                'name' => 'Especialista GTS',
                'slug' => 'gts',
                'description' => 'Grupo de Seguridad Tecnológica - Asignación, diagnóstico, atención y solución técnica en campo.',
            ],
            [
                'name' => 'Administrador del Sistema',
                'slug' => 'admin',
                'description' => 'Supervisión general, administración de usuarios, catálogos y auditoría global.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
