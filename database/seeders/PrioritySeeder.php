<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            [
                'name' => 'Baja',
                'slug' => 'baja',
                'level' => 1,
                'color' => '#64748B',
                'sla_minutes' => 240, // 4 horas
                'is_active' => true,
            ],
            [
                'name' => 'Media',
                'slug' => 'media',
                'level' => 2,
                'color' => '#3B82F6',
                'sla_minutes' => 120, // 2 horas
                'is_active' => true,
            ],
            [
                'name' => 'Alta',
                'slug' => 'alta',
                'level' => 3,
                'color' => '#F59E0B',
                'sla_minutes' => 45,  // 45 minutos
                'is_active' => true,
            ],
            [
                'name' => 'Crítica',
                'slug' => 'critica',
                'level' => 4,
                'color' => '#EF4444',
                'sla_minutes' => 15,  // 15 minutos (emergencia operacional)
                'is_active' => true,
            ],
        ];

        foreach ($priorities as $priority) {
            Priority::firstOrCreate(['slug' => $priority['slug']], $priority);
        }
    }
}

