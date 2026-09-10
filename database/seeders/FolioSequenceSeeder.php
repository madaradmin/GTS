<?php

namespace Database\Seeders;

use App\Models\FolioSequence;
use Illuminate\Database\Seeder;

class FolioSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = (int) date('Y');

        FolioSequence::firstOrCreate(
            ['year' => $currentYear],
            [
                'last_number' => 104, // Inicializado en 104 para coincidir con la maquetación INC-2025/2026-0104
                'updated_at' => now(),
            ]
        );
    }
}
