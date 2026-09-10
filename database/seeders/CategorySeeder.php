<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'CCTV y Videovigilancia',
                'slug' => 'cctv',
                'description' => 'Cámaras domo PTZ, fijas perimetrales, servidores VMS Genetec y grabadores NVR.',
                'icon' => 'videocam',
                'color' => '#06B6D4',
                'is_active' => true,
            ],
            [
                'name' => 'Control de Acceso',
                'slug' => 'access-control',
                'description' => 'Torniquetes e-Gates, lectores biométricos, esclusas y cerraduras electromagnéticas de puertas.',
                'icon' => 'fingerprint',
                'color' => '#8B5CF6',
                'is_active' => true,
            ],
            [
                'name' => 'FIDS / Pantallas de Vuelo',
                'slug' => 'fids',
                'description' => 'Pantallas de información de vuelos en salas de espera, cartelería digital y servidores multimedia.',
                'icon' => 'tv',
                'color' => '#F59E0B',
                'is_active' => true,
            ],
            [
                'name' => 'Radiocomunicación TETRA',
                'slug' => 'tetra',
                'description' => 'Estaciones base troncalizadas, repetidores de pista y terminales portátiles de radio.',
                'icon' => 'radio',
                'color' => '#3B82F6',
                'is_active' => true,
            ],
            [
                'name' => 'Redes e Infraestructura IT',
                'slug' => 'networks',
                'description' => 'Switches de borde perimetral, racks IDF/MDF, cableado estructurado y enlaces de fibra óptica.',
                'icon' => 'hub',
                'color' => '#10B981',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
