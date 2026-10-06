<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Priority;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_global_search_is_present_in_sidebar_and_removed_from_topbar(): void
    {
        $user = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();

        // 1. Verificar que el buscador se renderiza en el Sidebar
        $response->assertSee('name="search"', false);
        $response->assertSee('placeholder="Buscar incidencias..."', false);
        $response->assertSee('Buscador Global del Sistema GTS', false);

        // 2. Verificar que la barra superior ya NO contiene el buscador antiguo
        $response->assertDontSee('placeholder="Buscar por folio, descripción, equipo o usuario..."', false);
        $response->assertDontSee('Buscador Global con Caret de Filtros', false);
    }

    public function test_search_by_incident_folio(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $service = app(IncidentService::class);

        $incident = $service->createIncident([
            'title' => 'Incidencia de prueba folio',
            'description' => 'Descripción detallada para prueba de búsqueda por folio.',
            'location' => 'Sala de Espera B',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $user);

        $response = $this->actingAs($user)->get('/Todas las incidencias?search='.$incident->folio);

        $response->assertOk();
        $response->assertSee($incident->folio);
    }

    public function test_search_by_incident_title(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $service = app(IncidentService::class);

        $incident = $service->createIncident([
            'title' => 'Torniquete biométrico bloqueado puerta 8',
            'description' => 'Falla de lectura de credenciales magnéticas.',
            'location' => 'Puerta 8',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $user);

        $response = $this->actingAs($user)->get('/Todas las incidencias?search=biom%C3%A9trico');

        $response->assertOk();
        $response->assertSee($incident->folio);
    }

    public function test_search_by_incident_description(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $service = app(IncidentService::class);

        $incident = $service->createIncident([
            'title' => 'Problema en terminal de pasajeros',
            'description' => 'Sobrecalentamiento excesivo en rack de comunicaciones switch Cisco.',
            'location' => 'Muelle Norte',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $user);

        $response = $this->actingAs($user)->get('/Todas las incidencias?search=sobrecalentamiento');

        $response->assertOk();
        $response->assertSee($incident->folio);
    }

    public function test_search_by_incident_location(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $service = app(IncidentService::class);

        $incident = $service->createIncident([
            'title' => 'Cámara domo desconectada',
            'description' => 'Pérdida de enlace de video en circuito de seguridad.',
            'location' => 'Pista 04R Cabecera Sur',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $user);

        $response = $this->actingAs($user)->get('/Todas las incidencias?search=Cabecera+Sur');

        $response->assertOk();
        $response->assertSee($incident->folio);
    }

    public function test_search_by_category_name(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $service = app(IncidentService::class);
        $category = Category::first();

        $incident = $service->createIncident([
            'title' => 'Falla en subsistema específico',
            'description' => 'Reporte para verificar la indexación por categoría.',
            'location' => 'Oficinas Centrales',
            'category_id' => $category->id,
            'priority_id' => Priority::first()->id,
        ], $user);

        $response = $this->actingAs($user)->get('/Todas las incidencias?search='.urlencode($category->name));

        $response->assertOk();
        $response->assertSee($incident->folio);
    }

    public function test_search_by_assigned_technician_name(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $service = app(IncidentService::class);

        $incident = $service->createIncident([
            'title' => 'Falla asignada a Morales',
            'description' => 'Reporte asignado a especialista técnico específico.',
            'location' => 'Torre de Control',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $apocUser);

        $service->assignIncident($incident, $adminUser, $gtsUser);

        $response = $this->actingAs($adminUser)->get('/Todas las incidencias?search=Morales');

        $response->assertOk();
        $response->assertSee($incident->folio);
    }
}
