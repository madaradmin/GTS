<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. "CCTV y Videovigilancia" -> "CCTV"
        \Illuminate\Support\Facades\DB::table('categories')
            ->where('slug', 'cctv')
            ->orWhere('name', 'LIKE', '%CCTV%')
            ->update([
                'name' => 'CCTV',
                'description' => 'Cámaras domo PTZ, fijas perimetrales, servidores VMS Genetec y grabadores NVR.',
            ]);

        // 2. "Redes e Infraestructura IT" -> "Periféricos"
        \Illuminate\Support\Facades\DB::table('categories')
            ->where('slug', 'networks')
            ->orWhere('name', 'LIKE', '%Redes%')
            ->update([
                'name' => 'Periféricos',
                'description' => 'Terminales de usuario, switches de borde, periféricos biométricos y hardware de soporte.',
            ]);

        // 3. Actualizar colores institucionales en el catálogo de prioridades
        \Illuminate\Support\Facades\DB::table('priorities')
            ->where('slug', 'critica')
            ->update(['color' => '#D83B52']); // Rojo sobrio

        \Illuminate\Support\Facades\DB::table('priorities')
            ->where('slug', 'alta')
            ->update(['color' => '#E06D24']); // Naranja corporativo

        \Illuminate\Support\Facades\DB::table('priorities')
            ->where('slug', 'media')
            ->update(['color' => '#E0A21A']); // Amarillo / Ámbar suave

        \Illuminate\Support\Facades\DB::table('priorities')
            ->where('slug', 'baja')
            ->update(['color' => '#5B86B4']); // Azul grisáceo
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('categories')
            ->where('slug', 'cctv')
            ->update(['name' => 'CCTV y Videovigilancia']);

        \Illuminate\Support\Facades\DB::table('categories')
            ->where('slug', 'networks')
            ->update(['name' => 'Redes e Infraestructura IT']);
    }
};
