<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApocMisIncidenciasPhase3Test extends TestCase
{
    use RefreshDatabase;

    protected User $juanCco;

    protected User $pedroCco;

    protected User $luisCsa;

    protected Incident $cco001;

    protected Incident $cco002;

    protected Incident $cco003;

    protected Incident $cco004;

    protected Incident $csa001;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->juanCco = User::factory()->apocCco()->create([
            'name' => 'Juan CCO',
            'username' => 'juan.cco',
        ]);

        $this->pedroCco = User::factory()->apocCco()->create([
            'name' => 'Pedro CCO',
            'username' => 'pedro.cco',
        ]);

        $this->luisCsa = User::factory()->apocCsa()->create([
            'name' => 'Luis CSA',
            'username' => 'luis.csa',
        ]);

        $category = Category::first();
        $priority = Priority::first();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        // Juan crea CCO-001 y CCO-002
        $this->cco001 = Incident::factory()->create([
            'folio' => 'INC-2026-0001',
            'title' => 'CCO-001 Radar Torre Principal',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        $this->cco002 = Incident::factory()->create([
            'folio' => 'INC-2026-0002',
            'title' => 'CCO-002 Radio VHF Transmisor',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        // Pedro crea CCO-003 y CCO-004
        $this->cco003 = Incident::factory()->create([
            'folio' => 'INC-2026-0003',
            'title' => 'CCO-003 Servidor FIDS Pasillo C',
            'reported_by_user_id' => $this->pedroCco->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        $this->cco004 = Incident::factory()->create([
            'folio' => 'INC-2026-0004',
            'title' => 'CCO-004 Switch Core Red Operativa',
            'reported_by_user_id' => $this->pedroCco->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        // Luis crea CSA-001
        $this->csa001 = Incident::factory()->create([
            'folio' => 'INC-2026-0005',
            'title' => 'CSA-001 Carrusel Equipaje Sala 2',
            'reported_by_user_id' => $this->luisCsa->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);
    }

    /**
     * CASO 4 — MIS INCIDENCIAS:
     * Juan entra a "Mis incidencias" y debe ver CCO-001 y CCO-002, NO CCO-003, CCO-004 ni CSA-001.
     */
    public function test_caso_4_apoc_user_only_sees_their_own_incidents_in_mis_incidencias(): void
    {
        $response = $this->actingAs($this->juanCco)->get(route('incidents.my'));

        $response->assertOk();
        $response->assertSee('Mis Incidencias');

        // Ve sus incidencias propias
        $response->assertSee('CCO-001');
        $response->assertSee('CCO-002');
        $response->assertSee($this->cco001->folio);
        $response->assertSee($this->cco002->folio);

        // NO ve las creadas por su compañero Pedro (CCO)
        $response->assertDontSee('CCO-003');
        $response->assertDontSee('CCO-004');
        $response->assertDontSee($this->cco003->folio);
        $response->assertDontSee($this->cco004->folio);

        // NO ve las de CSA
        $response->assertDontSee('CSA-001');
        $response->assertDontSee($this->csa001->folio);

        // Métricas de "Mis Incidencias"
        $metrics = $response->viewData('metrics');
        $this->assertEquals(2, $metrics['total']);
    }

    /**
     * CASO 5 — TODAS LAS INCIDENCIAS CCO:
     * Juan entra a "Todas las incidencias" y debe ver CCO-001, CCO-002, CCO-003 y CCO-004, NO CSA-001.
     */
    public function test_caso_5_apoc_user_sees_all_group_incidents_in_todas_las_incidencias(): void
    {
        $response = $this->actingAs($this->juanCco)->get('/Todas las incidencias');

        $response->assertOk();
        $response->assertSee('Todas las Incidencias');

        // Ve sus incidencias
        $response->assertSee('CCO-001');
        $response->assertSee('CCO-002');
        $response->assertSee($this->cco001->folio);
        $response->assertSee($this->cco002->folio);

        // Ve las de su compañero Pedro (CCO)
        $response->assertSee('CCO-003');
        $response->assertSee('CCO-004');
        $response->assertSee($this->cco003->folio);
        $response->assertSee($this->cco004->folio);

        // NO ve las de CSA
        $response->assertDontSee('CSA-001');
        $response->assertDontSee($this->csa001->folio);

        // Métricas del grupo CCO completo
        $metrics = $response->viewData('metrics');
        $this->assertEquals(4, $metrics['total']);
    }

    /**
     * Verifica que Pedro en "Mis incidencias" ve CCO-003 y CCO-004, pero NO CCO-001 ni CCO-002.
     */
    public function test_peer_user_sees_only_own_incidents_in_mis_incidencias(): void
    {
        $response = $this->actingAs($this->pedroCco)->get(route('incidents.my'));

        $response->assertOk();
        $response->assertSee('Mis Incidencias');

        // Ve sus incidencias propias
        $response->assertSee('CCO-003');
        $response->assertSee('CCO-004');
        $response->assertSee($this->cco003->folio);
        $response->assertSee($this->cco004->folio);

        // NO ve las de Juan
        $response->assertDontSee('CCO-001');
        $response->assertDontSee('CCO-002');
        $response->assertDontSee($this->cco001->folio);
        $response->assertDontSee($this->cco002->folio);

        $metrics = $response->viewData('metrics');
        $this->assertEquals(2, $metrics['total']);
    }

    /**
     * Verifica que el sidebar integra visualmente:
     * Incidencias
     * ├── Registrar incidencia
     * ├── Mis incidencias
     * └── Todas las incidencias
     */
    public function test_sidebar_displays_mis_incidencias_in_correct_order_for_apoc(): void
    {
        $viewAllPerm = \App\Models\Permission::where('slug', 'incidents.view_all')->first();
        if ($viewAllPerm) {
            $this->juanCco->role->permissions()->syncWithoutDetaching([$viewAllPerm->id]);
        }

        $response = $this->actingAs($this->juanCco)->get(route('dashboard'));

        $response->assertOk();
        $content = $response->getContent();

        // 1. Debe contener los tres enlaces
        $this->assertStringContainsString(route('incidents.create'), $content);
        $this->assertStringContainsString(route('incidents.my'), $content);
        $this->assertStringContainsString(route('incidents.index'), $content);

        // 2. Comprobar el orden relativo exacto en el submenú de navegación
        $posCreate = strpos($content, 'Registrar incidencia');
        $posMy = strpos($content, 'Mis incidencias');
        $posIndex = strpos($content, 'Todas las incidencias');

        $this->assertNotFalse($posCreate, 'Enlace a crear no encontrado en sidebar');
        $this->assertNotFalse($posMy, 'Enlace a mis incidencias no encontrado en sidebar');
        $this->assertNotFalse($posIndex, 'Enlace a todas las incidencias no encontrado en sidebar');

        $this->assertTrue($posCreate < $posMy, 'Registrar incidencia debe preceder a Mis incidencias');
        $this->assertTrue($posMy < $posIndex, 'Mis incidencias debe preceder a Todas las incidencias');
    }

    /**
     * Verifica la compatibilidad de la ruta legacy /incidencias/mis-incidencias para APOC.
     */
    public function test_legacy_mis_incidencias_redirect_works_for_apoc(): void
    {
        $response = $this->actingAs($this->juanCco)->get('/incidencias/mis-incidencias');

        $response->assertRedirect(route('incidents.my'));
    }

    /**
     * Verifica que APOC no puede consultar asignaciones técnicas de GTS.
     */
    public function test_apoc_cannot_filter_by_technical_assignments(): void
    {
        $response = $this->actingAs($this->juanCco)->get(route('incidents.index', ['assigned_to' => $this->juanCco->id]));

        $response->assertStatus(403);
    }
}
