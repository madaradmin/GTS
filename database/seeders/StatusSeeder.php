<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'NUEVA',
                'name' => 'Nueva',
                'color' => '#3B82F6',
                'step_order' => 1,
                'is_terminal' => false,
            ],
            [
                'code' => 'ASIGNADA',
                'name' => 'Asignada',
                'color' => '#8B5CF6',
                'step_order' => 2,
                'is_terminal' => false,
            ],
            [
                'code' => 'EN_ATENCION',
                'name' => 'En Atención',
                'color' => '#F59E0B',
                'step_order' => 3,
                'is_terminal' => false,
            ],
            [
                'code' => 'RESUELTA',
                'name' => 'Resuelta',
                'color' => '#10B981',
                'step_order' => 4,
                'is_terminal' => false,
            ],
            [
                'code' => 'CERRADA',
                'name' => 'Cerrada',
                'color' => '#64748B',
                'step_order' => 5,
                'is_terminal' => true,
            ],
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate(['code' => $status['code']], $status);
        }
    }
}
