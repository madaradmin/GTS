<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apocRole = Role::where('slug', 'apoc')->firstOrFail();
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $users = [
            // Operador APOC de guardia (observado en las maquetas del reporte)
            [
                'name' => 'Tco. M. Reyes',
                'username' => 'apoc',
                'email' => 'apoc@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $apocRole->id,
                'badge_number' => 'APOC-042',
                'is_active' => true,
            ],
            // Especialistas GTS de guardia (observados en asignación y notas técnicas)
            [
                'name' => 'Ing. Roberto Morales',
                'username' => 'gts.morales',
                'email' => 'gts.morales@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $gtsRole->id,
                'badge_number' => 'GTS-108',
                'is_active' => true,
            ],
            [
                'name' => 'Ing. Carlos Mendoza',
                'username' => 'gts.mendoza',
                'email' => 'gts.mendoza@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $gtsRole->id,
                'badge_number' => 'GTS-215',
                'is_active' => true,
            ],
            // Administrador de Seguridad Tecnológica
            [
                'name' => 'Admin Seguridad Tecnológica',
                'username' => 'admin',
                'email' => 'admin@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $adminRole->id,
                'badge_number' => 'ADM-001',
                'is_active' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['username' => $userData['username']],
                $userData
            );
        }
    }
}
