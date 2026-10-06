<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\IndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterPhase5FinalVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gts;

    protected User $apocCco;

    protected User $apocCsa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->gts = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();

        $ccoRole = Role::firstOrCreate(['slug' => 'apoc-cco'], ['name' => 'APOC-CCO']);
        $this->apocCco = User::firstOrCreate(
            ['username' => 'test.cco.master'],
            [
                'name' => 'Operador CCO Master',
                'email' => 'cco.master@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $ccoRole->id,
                'is_active' => true,
            ]
        );

        $csaRole = Role::firstOrCreate(['slug' => 'apoc-csa'], ['name' => 'APOC-CSA']);
        $this->apocCsa = User::firstOrCreate(
            ['username' => 'test.csa.master'],
            [
                'name' => 'Operador CSA Master',
                'email' => 'csa.master@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $csaRole->id,
                'is_active' => true,
            ]
        );
    }

    /**
     * Comprobación integral para rol ADMINISTRADOR.
     */
    public function test_administrador_full_verification(): void
    {
        // 1. Extensión y Despacho en vista de crear
        $respCreate = $this->actingAs($this->admin)->get(route('incidents.create'));
        $respCreate->assertOk();
        $respCreate->assertSee('Soporte 55-57-98-98-00 Ext. 13202');
        $respCreate->assertSee('Despacho Automático: GTS');
        $respCreate->assertDontSee('Mesa GTS Guardia 2 (C4 & Pistas)');
        $respCreate->assertDontSee('Ext. 4209');

        // 2. Colores en Dashboard (Media = Verde, Alta = Naranja, Crítica = Rojo, Baja = Azul)
        $mediaPriority = Priority::where('slug', 'media')->firstOrFail();
        $altaPriority = Priority::where('slug', 'alta')->firstOrFail();
        $critPriority = Priority::where('slug', 'critica')->firstOrFail();
        $bajaPriority = Priority::where('slug', 'baja')->firstOrFail();

        $incMedia = Incident::factory()->nueva()->create(['priority_id' => $mediaPriority->id]);
        $incAlta = Incident::factory()->nueva()->create(['priority_id' => $altaPriority->id]);
        $incCrit = Incident::factory()->nueva()->create(['priority_id' => $critPriority->id]);
        $incBaja = Incident::factory()->nueva()->create(['priority_id' => $bajaPriority->id]);

        $respDash = $this->actingAs($this->admin)->get('/dashboard');
        $respDash->assertOk();
        $respDash->assertSee('#10B981'); // Media (Verde)
        $respDash->assertSee('#F59E0B'); // Alta (Naranja)
        $respDash->assertSee('#D83B52'); // Crítica (Rojo)
        $respDash->assertSee('#1E5FA7'); // Baja (Azul)
        $respDash->assertDontSee('#FACC15'); // Viejo ámbar eliminado

        // 3. Puede asignar TICS y visualizarlo
        $respAssign = $this->actingAs($this->admin)->post(route('incidents.assign', $incMedia), [
            'assigned_to_user_id' => 'tics',
            'comment' => 'Asignado a TICS por Admin',
        ]);
        $respAssign->assertRedirect(route('incidents.show', $incMedia->folio));

        $incMedia->refresh();
        $this->assertTrue($incMedia->isAssignedToTics());

        $respShow = $this->actingAs($this->admin)->get(route('incidents.show', $incMedia->folio));
        $respShow->assertOk();
        $respShow->assertSee('TICS');
        $respShow->assertSee('Especialista de Atención');
    }

    /**
     * Comprobación integral para rol GTS.
     */
    public function test_gts_full_verification(): void
    {
        $incident = Incident::factory()->nueva()->create();

        // 1. GTS puede asignar TICS
        $response = $this->actingAs($this->gts)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => 'tics',
            'comment' => 'Canalizado a TICS por guardia GTS',
        ]);
        $response->assertRedirect(route('incidents.show', $incident->folio));

        $incident->refresh();
        $this->assertTrue($incident->isAssignedToTics());

        // 2. GTS puede visualizar TICS en detalle y listado
        $respShow = $this->actingAs($this->gts)->get(route('incidents.show', $incident->folio));
        $respShow->assertOk();
        $respShow->assertSee('TICS');

        $respIndex = $this->actingAs($this->gts)->get(route('incidents.index'));
        $respIndex->assertOk();
        $respIndex->assertSee('TICS');

        // 3. GTS no pierde capacidades operacionales (atención y notas técnicas)
        $respAttention = $this->actingAs($this->gts)->post(route('incidents.start-attention', $incident), [
            'comment' => 'Iniciando diagnóstico coordinado con TICS',
        ]);
        $respAttention->assertRedirect(route('incidents.show', $incident->folio));

        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertTrue($incident->isAssignedToTics()); // Preserva TICS como especialista

        $respNote = $this->actingAs($this->gts)->post(route('incidents.add-note', $incident), [
            'note' => 'Prueba de enlace satisfactoria con personal TICS.',
        ]);
        $respNote->assertRedirect(route('incidents.show', $incident->folio));
    }

    /**
     * Comprobación integral para rol APOC-CCO.
     */
    public function test_apoc_cco_full_verification(): void
    {
        // Incidencia propia de CCO
        $incCco = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocCco->id,
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
            'assigned_at' => now(),
            'status_id' => Status::where('code', 'ASIGNADA')->firstOrFail()->id,
        ]);

        // Incidencia ajena de CSA
        $incCsa = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocCsa->id,
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
        ]);

        // 1. APOC-CCO puede visualizar TICS en su ticket
        $respShow = $this->actingAs($this->apocCco)->get(route('incidents.show', $incCco->folio));
        $respShow->assertOk();
        $respShow->assertSee('TICS');

        // 2. APOC-CCO no puede asignar (403)
        $respAssign = $this->actingAs($this->apocCco)->post(route('incidents.assign', $incCco), [
            'assigned_to_user_id' => 'tics',
        ]);
        $respAssign->assertStatus(403);

        // 3. Mantiene aislamiento estricto (403 al ver ticket ajeno de CSA)
        $respShowOther = $this->actingAs($this->apocCco)->get(route('incidents.show', $incCsa->folio));
        $respShowOther->assertStatus(403);

        // 4. En el listado no aparece el ticket de CSA
        $respList = $this->actingAs($this->apocCco)->get(route('incidents.index'));
        $respList->assertOk();
        $respList->assertSee($incCco->folio);
        $respList->assertDontSee($incCsa->folio);
    }

    /**
     * Comprobación integral para rol APOC-CSA.
     */
    public function test_apoc_csa_full_verification(): void
    {
        // Incidencia propia de CSA
        $incCsa = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocCsa->id,
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
            'assigned_at' => now(),
            'status_id' => Status::where('code', 'ASIGNADA')->firstOrFail()->id,
        ]);

        // Incidencia ajena de CCO
        $incCco = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocCco->id,
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
        ]);

        // 1. APOC-CSA puede visualizar TICS en su ticket
        $respShow = $this->actingAs($this->apocCsa)->get(route('incidents.show', $incCsa->folio));
        $respShow->assertOk();
        $respShow->assertSee('TICS');

        // 2. APOC-CSA no puede asignar (403)
        $respAssign = $this->actingAs($this->apocCsa)->post(route('incidents.assign', $incCsa), [
            'assigned_to_user_id' => 'tics',
        ]);
        $respAssign->assertStatus(403);

        // 3. Mantiene aislamiento estricto (403 al ver ticket ajeno de CCO)
        $respShowOther = $this->actingAs($this->apocCsa)->get(route('incidents.show', $incCco->folio));
        $respShowOther->assertStatus(403);

        // 4. En el listado no aparece el ticket de CCO
        $respList = $this->actingAs($this->apocCsa)->get(route('incidents.index'));
        $respList->assertOk();
        $respList->assertSee($incCsa->folio);
        $respList->assertDontSee($incCco->folio);
    }

    /**
     * Comprobación de integridad global: TICS no es rol ni usuario ni infla métricas.
     */
    public function test_system_integrity_and_metric_isolation(): void
    {
        // 1. TICS no existe en roles
        $this->assertDatabaseMissing('roles', ['slug' => 'tics']);
        $this->assertDatabaseMissing('roles', ['name' => 'TICS']);

        // 2. TICS no existe en usuarios
        $this->assertDatabaseMissing('users', ['username' => 'tics']);
        $this->assertDatabaseMissing('users', ['email' => 'tics@aifa.aero']);

        // 3. Métricas de técnicos activos en dashboard
        $dashboardService = app(DashboardService::class);
        $countBefore = $dashboardService->getActiveTechniciansCount();

        Incident::factory()->count(5)->create([
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
        ]);

        $countAfter = $dashboardService->getActiveTechniciansCount();
        $this->assertEquals($countBefore, $countAfter);

        // 4. IndicatorService retorna verde para media
        $indicatorService = app(IndicatorService::class);
        $data = $indicatorService->getMetrics();
        $this->assertEquals('#10B981', $data['by_priority']['media']['color']);
    }
}
