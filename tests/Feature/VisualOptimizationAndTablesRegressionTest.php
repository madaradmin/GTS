<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualOptimizationAndTablesRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Verificación técnica de que el Sidebar NO contiene clases que dibujen bordes blancos en :hover.
     */
    public function test_sidebar_navigation_has_no_white_hover_borders(): void
    {
        $sidebarPath = resource_path('views/components/sidebar.blade.php');
        $this->assertFileExists($sidebarPath);

        $sidebarContent = file_get_contents($sidebarPath);

        // No debe existir 'hover:border' en ningún ítem de navegación ni submenú
        $this->assertStringNotContainsString('hover:border hover:border-[#263241]', $sidebarContent);
        $this->assertStringNotContainsString('hover:border ', $sidebarContent);

        // Debe contener el esquema limpio con fondo oscuro
        $this->assertStringContainsString('hover:bg-[#101923]', $sidebarContent);
        $this->assertStringContainsString('border border-transparent', $sidebarContent);
    }

    /**
     * 2. Verificación del activo y renderizado del logotipo oficial del AIFA.
     */
    public function test_official_aifa_logo_asset_exists_and_renders_in_topbar(): void
    {
        $logoPath = public_path('images/aifa-logo.png');
        $this->assertFileExists($logoPath, 'El archivo official del logotipo aifa-logo.png debe existir en public/images/');

        $imageInfo = getimagesize($logoPath);
        $this->assertNotFalse($imageInfo, 'El logotipo debe ser una imagen válida');
        $this->assertEquals(IMAGETYPE_PNG, $imageInfo[2], 'El logotipo debe ser de tipo PNG');
        $this->assertEquals(1024, $imageInfo[0], 'El ancho debe ser de 1024px');
        $this->assertEquals(627, $imageInfo[1], 'El alto debe ser de 627px');

        // Renderizado en Topbar para los 3 roles
        $users = [
            User::where('email', 'apoc@aifa.aero')->firstOrFail(),
            User::where('email', 'gts.morales@aifa.aero')->firstOrFail(),
            User::where('email', 'admin@aifa.aero')->firstOrFail(),
        ];

        foreach ($users as $user) {
            $response = $this->actingAs($user)->get(route('dashboard'));
            $response->assertOk();
            $response->assertSee('images/aifa-logo.png');
            $response->assertSee('Aeropuerto Internacional Felipe Ángeles');
            $response->assertSee('AIFA');
        }
    }

    /**
     * 3. Verificación de que la tabla de Todas las Incidencias cuenta con table-fixed,
     * porcentajes de ancho balanceados y ausencia de scroll horizontal artificial.
     */
    public function test_incident_table_has_table_fixed_and_no_horizontal_overflow_columns(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        // Crear una incidencia de prueba con título y ubicación extensos
        $incident = Incident::first();

        $response = $this->actingAs($apocUser)->get(route('incidents.index'));
        $response->assertOk();

        $content = $response->getContent();

        // Verificación de estructura table-fixed y max-w-full
        $this->assertStringContainsString('table-fixed', $content);
        $this->assertStringContainsString('w-full max-w-full', $content);

        // Erradicación de contenedores de scroll horizontal
        $this->assertStringNotContainsString('overflow-x-auto', $content);
        $this->assertStringNotContainsString('overflow-x-scroll', $content);

        // Verificación de anchos porcentuales de columnas (suman 100%)
        $this->assertStringContainsString('w-[13%]', $content); // Folio
        $this->assertStringContainsString('w-[26%]', $content); // Título
        $this->assertStringContainsString('w-[17%]', $content); // Ubicación
        $this->assertStringContainsString('w-[9%]', $content);  // Prioridad
        $this->assertStringContainsString('w-[10%]', $content); // Estado
        $this->assertStringContainsString('w-[11%]', $content); // Responsable
        $this->assertStringContainsString('w-[7%]', $content);  // Fecha y Acciones

        // No debe existir min-w rígido que ensanche la tabla
        $this->assertStringNotContainsString('min-w-[240px]', $content);
        $this->assertStringNotContainsString('min-w-[180px]', $content);

        // El folio de la incidencia debe estar completamente visible en el HTML
        if ($incident) {
            $this->assertStringContainsString($incident->folio, $content);
        }

        // Paddings optimizados
        $this->assertStringContainsString('py-2.5 px-2', $content);
    }

    /**
     * 4. Verificación de la pantalla 'Mis Incidencias' con título contextual y layout consistente.
     */
    public function test_my_incidents_screen_renders_contextual_title_and_same_clean_table_layout(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $response = $this->actingAs($gtsUser)->get(route('incidents.my'));
        $response->assertOk();
        $response->assertSee('Mis Incidencias');
        $response->assertSee('table-fixed');
    }

    /**
     * 5. Verificación de que APOC puede acceder al módulo de Mis Incidencias (habilitado para APOC en FASE 3).
     */
    public function test_apoc_profile_can_access_my_incidents(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get(route('incidents.my'));
        $response->assertOk();
    }
}
