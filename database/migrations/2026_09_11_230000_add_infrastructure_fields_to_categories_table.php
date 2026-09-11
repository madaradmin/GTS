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
        Schema::table('categories', function (Blueprint $table) {
            $table->string('status', 30)->default('ACTIVO')->after('color');
            $table->string('code', 50)->nullable()->after('slug');
            $table->string('location', 255)->nullable()->after('description');
            $table->text('technical_specs')->nullable()->after('location');
        });

        // Poblar metadatos institucionales iniciales para los 5 subsistemas base de GTS
        DB::table('categories')->where('slug', 'cctv')->update([
            'code' => 'INF-CCTV',
            'status' => 'ACTIVO',
            'location' => 'Edificio Terminal, Plataformas y Perimetro AIFA',
            'technical_specs' => 'Sistema VMS Genetec Omnicast, camaras domo PTZ Axis/Bosch perimetrales y NVRs redundantes.',
        ]);

        DB::table('categories')->where('slug', 'access-control')->update([
            'code' => 'INF-ACC',
            'status' => 'ACTIVO',
            'location' => 'Filtros de Inspeccion, Puertas de Embarque y Aduana',
            'technical_specs' => 'Controladoras HID VertX, e-Gates Gunnebo automatizados y lectores biometricos Suprema.',
        ]);

        DB::table('categories')->where('slug', 'fids')->update([
            'code' => 'INF-FIDS',
            'status' => 'ACTIVO',
            'location' => 'Salas de Ultima Espera, Pasillos de Llegadas y Mostradores',
            'technical_specs' => 'Servidores de Carteleria Samsung MagicINFO, pantallas industriales 65 UHD y control de flujo.',
        ]);

        DB::table('categories')->where('slug', 'tetra')->update([
            'code' => 'INF-TETRA',
            'status' => 'ACTIVO',
            'location' => 'Torre de Control (TWR), Lado Aire y Subestaciones',
            'technical_specs' => 'Red troncalizada digital Motorola Dimetra IP, repetidores MTS4 y terminales portatiles MTP3550.',
        ]);

        DB::table('categories')->where('slug', 'networks')->update([
            'code' => 'INF-PERIF',
            'status' => 'ACTIVO',
            'location' => 'Puntos de Atencion a Pasajeros y Oficinas Operativas',
            'technical_specs' => 'Terminales de trabajo CUTE, switches Cisco Catalyst PoE+, impresoras de abordaje y lectores 2D.',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['status', 'code', 'location', 'technical_specs']);
        });
    }
};
