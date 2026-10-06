<?php

namespace App\Services;

use App\Models\FolioSequence;
use Illuminate\Support\Facades\DB;

class FolioGeneratorService
{
    /**
     * Genera un folio atómico e incremental bajo concurrencia garantizada.
     * Estructura: INC-YYYY-XXXX (ej. INC-2026-0105)
     */
    public function generateNextFolio(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');

        return DB::transaction(function () use ($year) {
            // Bloqueo pesimista a nivel de fila (FOR UPDATE) en PostgreSQL
            $sequence = FolioSequence::where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                // Si el año no existe, se inicializa en 0
                $sequence = FolioSequence::create([
                    'year' => $year,
                    'last_number' => 0,
                    'updated_at' => now(),
                ]);

                // Re-bloquear fila creada
                $sequence = FolioSequence::where('year', $year)->lockForUpdate()->first();
            }

            $sequence->last_number += 1;
            $sequence->updated_at = now();
            $sequence->save();

            return sprintf('INC-%04d-%04d', $year, $sequence->last_number);
        });
    }

    /**
     * Retorna una vista previa preliminar del siguiente folio sin incrementar la secuencia.
     */
    public function getNextFolioPreview(?int $year = null): string
    {
        $year = $year ?? (int) date('Y');

        $sequence = FolioSequence::where('year', $year)->first();
        $nextNumber = ($sequence ? $sequence->last_number : 0) + 1;

        return sprintf('INC-%04d-%04d', $year, $nextNumber);
    }
}
