<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentRegistrationTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected Priority $priority;

    protected User $adminUser;

    protected User $gtsUser;

    protected User $ccoUser;

    protected User $csaUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->category = Category::where('is_active', true)->firstOrFail();
        $this->priority = Priority::where('is_active', true)->firstOrFail();

        $this->adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $this->gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $this->ccoUser = User::factory()->apocCco()->create([
            'name' => 'Operador CCO Guardia',
            'username' => 'cco.guardia',
        ]);

        $this->csaUser = User::factory()->apocCsa()->create([
            'name' => 'Operador CSA Guardia',
            'username' => 'csa.guardia',
        ]);
    }

    /**
     * PRUEBA 1 — Nueva incidencia:
     * Crear una incidencia nueva y comprobar:
     * - fecha correcta
     * - hora correcta
     * - created_at y reported_at correctos en base de datos
     * - fecha/hora correcta en la interfaz
     */
    public function test_prueba_1_new_incident_has_exact_local_mexico_city_date_and_time_in_backend_and_views(): void
    {
        // Fijar un momento específico en zona horaria America/Mexico_City
        $knownLocalMoment = Carbon::create(2026, 9, 30, 9, 45, 0, 'America/Mexico_City');
        Carbon::setTestNow($knownLocalMoment);

        $response = $this->actingAs($this->ccoUser)->post(route('incidents.store'), [
            'title' => 'Falla en Radar de Superficie SMR',
            'description' => 'Pérdida de señal intermitente en sector Bravo del aeropuerto.',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'location' => 'Torre de Control - Consola CCO',
        ]);

        $response->assertRedirect();

        $incident = Incident::where('title', 'Falla en Radar de Superficie SMR')->firstOrFail();

        // 1. Verificación en base de datos: el timestamp debe ser exactamente las 09:45:00 en America/Mexico_City
        $this->assertEquals('America/Mexico_City', config('app.timezone'));
        $this->assertEquals('2026-09-30 09:45:00', $incident->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-09-30 09:45:00', $incident->reported_at->format('Y-m-d H:i:s'));
        $this->assertEquals('America/Mexico_City', $incident->created_at->timezoneName);

        // 2. Verificación en vista de detalle (incidents.show)
        $responseShow = $this->actingAs($this->ccoUser)->get(route('incidents.show', $incident->folio));
        $responseShow->assertOk();
        $responseShow->assertSee('2026-09-30 09:45:00'); // Timestamp exacto de creación
        $responseShow->assertSee('30 Sep 09:45'); // Formato de reported_at en cabecera

        // 3. Verificación en listado (incidents.index)
        $responseIndex = $this->actingAs($this->ccoUser)->get(route('incidents.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('30/09/2026');
        $responseIndex->assertSee('09:45 hrs');

        // 4. Verificación en formulario de creación (incidents.create)
        $responseCreate = $this->actingAs($this->ccoUser)->get(route('incidents.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('2026-09-30 • 09:45:00 hrs');

        Carbon::setTestNow();
    }

    /**
     * PRUEBA 2 — Diferencia entre navegador y servidor:
     * Comprobar que la hora no depende de JavaScript ni de valores enviados por el cliente.
     */
    public function test_prueba_2_time_display_does_not_rely_on_client_side_javascript(): void
    {
        $knownLocalMoment = Carbon::create(2026, 9, 30, 14, 20, 15, 'America/Mexico_City');
        Carbon::setTestNow($knownLocalMoment);

        // El usuario NO envía ningún campo de fecha/hora en la solicitud POST
        $response = $this->actingAs($this->adminUser)->post(route('incidents.store'), [
            'title' => 'Incidencia Servidor NTP Central',
            'description' => 'Validación de servidor de tiempo sin payload de fecha en request.',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'location' => 'Data Center Principal',
        ]);
        $response->assertRedirect();

        $incident = Incident::where('title', 'Incidencia Servidor NTP Central')->firstOrFail();

        // El timestamp es asignado 100% por backend/Eloquent en America/Mexico_City
        $this->assertEquals('2026-09-30 14:20:15', $incident->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-09-30 14:20:15', $incident->reported_at->format('Y-m-d H:i:s'));

        // El HTML no contiene scripts que alteren la fecha/hora en el cliente
        $responseShow = $this->actingAs($this->adminUser)->get(route('incidents.show', $incident->folio));
        $content = $responseShow->getContent();
        $this->assertStringContainsString('2026-09-30 14:20:15', $content);
        $this->assertStringNotContainsString('new Date()', $content);

        Carbon::setTestNow();
    }

    /**
     * PRUEBA 3 — Diferentes roles:
     * Probar la creación con todos los roles autorizados:
     * - ADMINISTRADOR
     * - GTS
     * - APOC-CCO
     * - APOC-CSA
     * La fecha/hora de creación debe comportarse idénticamente en todos.
     */
    public function test_prueba_3_incident_creation_across_all_roles_records_accurate_local_timestamp(): void
    {
        $testMoments = [
            'admin' => ['user' => $this->adminUser, 'time' => Carbon::create(2026, 9, 30, 8, 15, 0, 'America/Mexico_City')],
            'gts' => ['user' => $this->gtsUser, 'time' => Carbon::create(2026, 9, 30, 11, 30, 0, 'America/Mexico_City')],
            'cco' => ['user' => $this->ccoUser, 'time' => Carbon::create(2026, 9, 30, 15, 45, 0, 'America/Mexico_City')],
            'csa' => ['user' => $this->csaUser, 'time' => Carbon::create(2026, 9, 30, 19, 0, 0, 'America/Mexico_City')],
        ];

        foreach ($testMoments as $roleKey => $data) {
            Carbon::setTestNow($data['time']);

            $response = $this->actingAs($data['user'])->post(route('incidents.store'), [
                'title' => "Incidencia creada por rol {$roleKey}",
                'description' => "Verificación de hora exacta para el rol {$roleKey}.",
                'category_id' => $this->category->id,
                'priority_id' => $this->priority->id,
                'location' => 'Zona Operacional AIFA',
            ]);
            $response->assertRedirect();

            $incident = Incident::where('title', "Incidencia creada por rol {$roleKey}")->firstOrFail();

            $expectedFormatted = $data['time']->format('Y-m-d H:i:s');
            $this->assertEquals($expectedFormatted, $incident->created_at->format('Y-m-d H:i:s'), "Fallo de hora para rol {$roleKey}");
            $this->assertEquals($expectedFormatted, $incident->reported_at->format('Y-m-d H:i:s'), "Fallo de hora para rol {$roleKey}");
        }

        Carbon::setTestNow();
    }

    /**
     * PRUEBA 4 — Incidencia histórica:
     * Verificar que las incidencias existentes no hayan sido modificadas accidentalmente.
     */
    public function test_prueba_4_historical_incidents_preserve_their_original_timestamps(): void
    {
        $historicalDate = Carbon::create(2026, 8, 15, 10, 30, 0, 'America/Mexico_City');

        // Incidencia histórica previamente existente
        $historical = Incident::factory()->create([
            'folio' => 'INC-2026-HIST-999',
            'title' => 'Incidencia Histórica Intacta',
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
            'reported_at' => $historicalDate,
            'reported_by_user_id' => $this->adminUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => Status::where('code', 'NUEVA')->firstOrFail()->id,
        ]);

        // Crear una nueva incidencia en fecha posterior
        $newMoment = Carbon::create(2026, 9, 30, 16, 0, 0, 'America/Mexico_City');
        Carbon::setTestNow($newMoment);

        $this->actingAs($this->adminUser)->post(route('incidents.store'), [
            'title' => 'Nueva Incidencia Posterior',
            'description' => 'Comprobar que no se afectan registros antiguos.',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'location' => 'Área de Llegadas',
        ])->assertRedirect();

        // Recargar la histórica y comprobar que sigue inalterada
        $historical->refresh();
        $this->assertEquals('2026-08-15 10:30:00', $historical->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-08-15 10:30:00', $historical->reported_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    /**
     * PRUEBA 5 — Auditoría:
     * Verificar que la fecha/hora asociada al registro de la incidencia continúe siendo coherente con la bitácora de auditoría.
     */
    public function test_prueba_5_audit_log_has_exact_temporal_coherence_with_incident_creation(): void
    {
        $fixedMoment = Carbon::create(2026, 9, 30, 12, 10, 30, 'America/Mexico_City');
        Carbon::setTestNow($fixedMoment);

        $this->actingAs($this->ccoUser)->post(route('incidents.store'), [
            'title' => 'Incidencia Coherencia de Auditoría',
            'description' => 'Verificar que la bitácora tenga la misma estampa temporal que la incidencia.',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'location' => 'Sala de Equipos CCO',
        ])->assertRedirect();

        $incident = Incident::where('title', 'Incidencia Coherencia de Auditoría')->firstOrFail();
        $auditLog = IncidentHistory::where('incident_id', $incident->id)
            ->where('action', 'CREADA')
            ->firstOrFail();

        // 1. La hora de la incidencia y la del log de auditoría deben ser idénticas al segundo
        $this->assertEquals('2026-09-30 12:10:30', $incident->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-09-30 12:10:30', $auditLog->created_at->format('Y-m-d H:i:s'));
        $this->assertEquals($incident->created_at->toDateTimeString(), $auditLog->created_at->toDateTimeString());

        // 2. Comprobar que en la vista de auditoría se muestra la hora local real
        $responseAudit = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $responseAudit->assertOk();
        $responseAudit->assertSee('30/09/2026 12:10:30');

        Carbon::setTestNow();
    }

    /**
     * PRUEBA 6 — Reportes:
     * Verificar que la fecha/hora de la incidencia siga apareciendo correctamente en los reportes existentes.
     */
    public function test_prueba_6_reports_render_accurate_local_date_and_time(): void
    {
        $reportMoment = Carbon::create(2026, 9, 30, 17, 25, 45, 'America/Mexico_City');
        Carbon::setTestNow($reportMoment);

        $this->actingAs($this->ccoUser)->post(route('incidents.store'), [
            'title' => 'Incidencia para Reporte de Desempeño',
            'description' => 'Verificar visualización en reportes y exportación.',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'location' => 'Plataforma Principal',
        ])->assertRedirect();

        $incident = Incident::where('title', 'Incidencia para Reporte de Desempeño')->firstOrFail();

        // 1. Vista web de reportes (reports.index)
        $responseReports = $this->actingAs($this->ccoUser)->get(route('reports.index'));
        $responseReports->assertOk();
        $responseReports->assertSee('30/09/2026 17:25');

        // 2. Exportación PDF de reportes (reports.pdf)
        $responsePdf = $this->actingAs($this->ccoUser)->get(route('reports.pdf'));
        $responsePdf->assertOk();

        // 3. Preset 'today' en reportes debe capturar correctamente la incidencia registrada hoy
        $responsePresetToday = $this->actingAs($this->ccoUser)->get(route('reports.index', ['period_preset' => 'today']));
        $responsePresetToday->assertOk();
        $responsePresetToday->assertSee($incident->folio);
        $responsePresetToday->assertSee('Incidencia para Reporte de Desempeño');

        Carbon::setTestNow();
    }
}
