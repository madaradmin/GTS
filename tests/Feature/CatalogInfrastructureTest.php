<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_apoc_user_can_access_catalogs_in_read_only_mode(): void
    {
        $apocUser = User::factory()->apoc()->create();

        $response = $this->actingAs($apocUser)->get(route('catalogs.index'));

        $response->assertStatus(200);
        $response->assertSee('Catálogos e Infraestructura Tecnológica');
        $response->assertSee('Modo Consulta (Solo Lectura)');
        $response->assertDontSee('Alta de Infraestructura');
        $response->assertSee('Total Infraestructura');
        $response->assertSee('Activos Operacionales');
        $response->assertSee('Disponibilidad Global');
    }

    public function test_gts_user_can_access_catalogs_in_read_only_mode(): void
    {
        $gtsUser = User::factory()->gts()->create();

        $response = $this->actingAs($gtsUser)->get(route('catalogs.index'));

        $response->assertStatus(200);
        $response->assertSee('Modo Consulta (Solo Lectura)');
        $response->assertDontSee('Alta de Infraestructura');
    }

    public function test_admin_user_can_access_catalogs_with_management_privileges(): void
    {
        $adminUser = User::factory()->admin()->create();

        $response = $this->actingAs($adminUser)->get(route('catalogs.index'));

        $response->assertStatus(200);
        $response->assertSee('Alta de Infraestructura');
        $response->assertDontSee('Modo Consulta (Solo Lectura)');
    }

    public function test_apoc_and_gts_cannot_create_infrastructure(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create();

        $payload = [
            'name' => 'Nuevo Subsistema PIDS',
            'status' => 'ACTIVO',
        ];

        $this->actingAs($apocUser)->post(route('catalogs.store'), $payload)
            ->assertStatus(403);

        $this->actingAs($gtsUser)->post(route('catalogs.store'), $payload)
            ->assertStatus(403);
    }

    public function test_apoc_and_gts_cannot_update_infrastructure(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create();
        $category = Category::first();

        $payload = [
            'name' => 'Nombre Modificado Sin Permiso',
            'status' => 'ACTIVO',
        ];

        $this->actingAs($apocUser)->put(route('catalogs.update', $category), $payload)
            ->assertStatus(403);

        $this->actingAs($gtsUser)->put(route('catalogs.update', $category), $payload)
            ->assertStatus(403);
    }

    public function test_apoc_and_gts_cannot_toggle_infrastructure_status(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create();
        $category = Category::first();

        $payload = ['status' => 'FUERA DE SERVICIO'];

        $this->actingAs($apocUser)->post(route('catalogs.status', $category), $payload)
            ->assertStatus(403);

        $this->actingAs($gtsUser)->post(route('catalogs.status', $category), $payload)
            ->assertStatus(403);
    }

    public function test_admin_can_create_new_infrastructure_item(): void
    {
        $adminUser = User::factory()->admin()->create();

        $payload = [
            'name' => 'Sistema de Megafonía PAS',
            'code' => 'INF-PAS',
            'location' => 'Edificio Terminal y Puertas 1-20',
            'description' => 'Sistema de voceo de seguridad y anuncios de vuelos en salas de espera.',
            'technical_specs' => 'Central IP Bosch Praesideo, amplificadores de clase D y micrófonos zonales.',
            'icon' => 'volume_up',
            'color' => '#E06D24',
            'status' => 'ACTIVO',
        ];

        $response = $this->actingAs($adminUser)->post(route('catalogs.store'), $payload);

        $response->assertRedirect(route('catalogs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Sistema de Megafonía PAS',
            'code' => 'INF-PAS',
            'status' => 'ACTIVO',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_infrastructure_item(): void
    {
        $adminUser = User::factory()->admin()->create();
        $category = Category::where('slug', 'cctv')->first();

        $payload = [
            'name' => 'CCTV y Analítica Perimetral',
            'code' => 'INF-CCTV-V2',
            'location' => 'Edificio Terminal, Plataformas y Cercado Perimetral',
            'description' => 'Cámaras domo PTZ y fijas con analítica térmica integrada.',
            'technical_specs' => 'VMS Genetec Omnicast 5.12, servidores Dell PowerEdge.',
            'icon' => 'videocam',
            'color' => '#06B6D4',
            'status' => 'ACTIVO',
        ];

        $response = $this->actingAs($adminUser)->put(route('catalogs.update', $category), $payload);

        $response->assertRedirect(route('catalogs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'CCTV y Analítica Perimetral',
            'code' => 'INF-CCTV-V2',
        ]);
    }

    public function test_admin_can_change_status_and_dar_de_baja(): void
    {
        $adminUser = User::factory()->admin()->create();
        $category = Category::where('slug', 'fids')->first();

        // 1. Poner FUERA DE SERVICIO
        $response1 = $this->actingAs($adminUser)->post(route('catalogs.status', $category), [
            'status' => 'FUERA DE SERVICIO',
            'reason' => 'Falla en switch central de distribución FIDS',
        ]);
        $response1->assertRedirect(route('catalogs.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'status' => 'FUERA DE SERVICIO',
            'is_active' => false,
        ]);

        // 2. Dar de baja (INACTIVO)
        $response2 = $this->actingAs($adminUser)->post(route('catalogs.status', $category), [
            'status' => 'INACTIVO',
            'reason' => 'Mantenimiento preventivo anual',
        ]);
        $response2->assertRedirect(route('catalogs.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'status' => 'INACTIVO',
            'is_active' => false,
        ]);

        // 3. Reactivar (ACTIVO)
        $response3 = $this->actingAs($adminUser)->post(route('catalogs.status', $category), [
            'status' => 'ACTIVO',
        ]);
        $response3->assertRedirect(route('catalogs.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'status' => 'ACTIVO',
            'is_active' => true,
        ]);
    }

    public function test_cannot_physically_delete_infrastructure_with_associated_incidents(): void
    {
        $adminUser = User::factory()->admin()->create();
        $category = Category::where('slug', 'cctv')->first();

        // Crear una incidencia asociada
        Incident::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertTrue($category->incidents()->count() > 0);

        // Intento de eliminación física por administrador
        $response = $this->actingAs($adminUser)->delete(route('catalogs.destroy', $category));

        $response->assertRedirect(route('catalogs.index'));
        $response->assertSessionHas('error');

        // El registro sigue existiendo en la base de datos para preservar la trazabilidad
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_admin_can_delete_infrastructure_without_incidents(): void
    {
        $adminUser = User::factory()->admin()->create();
        $newCategory = Category::create([
            'name' => 'Infraestructura Temporal de Prueba',
            'slug' => 'infra-test-del',
            'code' => 'INF-TEST-DEL',
            'status' => 'ACTIVO',
        ]);

        $this->assertEquals(0, $newCategory->incidents()->count());

        $response = $this->actingAs($adminUser)->delete(route('catalogs.destroy', $newCategory));

        $response->assertRedirect(route('catalogs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', [
            'id' => $newCategory->id,
        ]);
    }

    public function test_search_and_filtering_works_in_catalogs_view(): void
    {
        $apocUser = User::factory()->apoc()->create();

        // Filtrar por término
        $responseSearch = $this->actingAs($apocUser)->get(route('catalogs.index', ['search' => 'CCTV']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('CCTV');

        // Filtrar por categoría slug
        $responseCategory = $this->actingAs($apocUser)->get(route('catalogs.index', ['category' => 'tetra']));
        $responseCategory->assertStatus(200);
        $responseCategory->assertSee('Radiocomunicación');

        // Filtrar por estado
        $responseStatus = $this->actingAs($apocUser)->get(route('catalogs.index', ['status' => 'ACTIVO']));
        $responseStatus->assertStatus(200);
    }

    public function test_sidebar_contains_active_catalogs_link(): void
    {
        $user = User::factory()->apoc()->create();

        $response = $this->actingAs($user)->get(route('catalogs.index'));

        $response->assertStatus(200);
        $response->assertSee(route('catalogs.index'));
        $response->assertSee('Catálogos');
        $response->assertDontSee('openCat');
        $response->assertDontSee('• CCTV');
    }
}
