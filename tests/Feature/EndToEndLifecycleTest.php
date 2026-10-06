<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('private');
    }

    /**
     * Simulación integral del ciclo de vida aeroportuario real en AIFA:
     * Reporte APOC -> Asignación GTS -> Atención en Campo -> Resolución Técnica ->
     * Rechazo APOC -> Segunda Atención -> Solución Certificada -> Cierre Definitivo ->
     * Bloqueo Total de Inmutabilidad y Auditoría Pericial.
     */
    public function test_complete_mission_critical_incident_lifecycle_e2e(): void
    {
        // Actores Operacionales
        $apocOperator = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsSupervisor = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $gtsSpecialist = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();
        $fidsCategory = Category::where('slug', 'fids')->firstOrFail();
        $criticalPriority = Priority::where('slug', 'critica')->firstOrFail();

        // ---------------------------------------------------------------------
        // PASO 1: Despacho Inicial por APOC (Apertura de Incidencia)
        // ---------------------------------------------------------------------
        $createResponse = $this->actingAs($apocOperator)->post(route('incidents.store'), [
            'title' => 'Pérdida total de señal en clúster de pantallas FIDS Salas SUE-03 a SUE-07',
            'description' => 'Múltiples pantallas de información de vuelos muestran pantalla negra tras fluctuación eléctrica en subestación perimetral.',
            'category_id' => $fidsCategory->id,
            'priority_id' => $criticalPriority->id,
            'location' => 'Edificio Terminal - Lado Aire - Salas de Última Espera SUE-03 a SUE-07',
        ]);

        $createResponse->assertRedirect();

        $incident = Incident::where('category_id', $fidsCategory->id)->latest('id')->first();
        $this->assertNotNull($incident);
        $this->assertMatchesRegularExpression('/^INC-\d{4}-\d{4}$/', $incident->folio);
        $this->assertEquals('NUEVA', $incident->status->code);
        $this->assertEquals($apocOperator->id, $incident->reported_by_user_id);
        $this->assertNull($incident->assigned_to_user_id);

        // ---------------------------------------------------------------------
        // PASO 2: Carga de Evidencia Fotográfica Inicial por APOC
        // ---------------------------------------------------------------------
        $initialEvidence = UploadedFile::fake()->create('pantalla_negra_fids.jpg', 350, 'image/jpeg');

        $attachmentResponse = $this->actingAs($apocOperator)->post(
            route('incidents.attachments.store', $incident->folio),
            ['attachments' => [$initialEvidence]]
        );

        $attachmentResponse->assertRedirect(route('incidents.show', $incident->folio));
        $this->assertDatabaseCount('incident_attachments', 1);

        // ---------------------------------------------------------------------
        // PASO 3: Inspección y Asignación Operativa por GTS
        // ---------------------------------------------------------------------
        $assignResponse = $this->actingAs($gtsSupervisor)->post(
            route('incidents.assign', $incident->folio),
            ['assigned_to_user_id' => $gtsSpecialist->id]
        );

        $assignResponse->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertEquals($gtsSpecialist->id, $incident->assigned_to_user_id);
        $this->assertNotNull($incident->assigned_at);

        // ---------------------------------------------------------------------
        // PASO 4: Despliegue en Campo por la Especialista GTS (Iniciar Atención)
        // ---------------------------------------------------------------------
        $startResponse = $this->actingAs($gtsSpecialist)->post(
            route('incidents.start-attention', $incident->folio)
        );

        $startResponse->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertNotNull($incident->attended_at);

        // ---------------------------------------------------------------------
        // PASO 5: Registro de Bitácora Técnica de Diagnóstico (2 Notas de Campo)
        // ---------------------------------------------------------------------
        $note1Response = $this->actingAs($gtsSpecialist)->post(
            route('incidents.add-note', $incident->folio),
            ['note' => 'En sitio IDF-LadoAire-02: Switches PoE operativos pero se detecta caída de señal óptica en puerto SFP+ 10G enlace con core SITIA.']
        );
        $note1Response->assertRedirect(route('incidents.show', $incident->folio));

        $note2Response = $this->actingAs($gtsSpecialist)->post(
            route('incidents.add-note', $incident->folio),
            ['note' => 'Prueba de reflectometría OTDR indica atenuación anómala de -28dB en conector LC dúplex. Limpieza de férula en progreso.']
        );
        $note2Response->assertRedirect(route('incidents.show', $incident->folio));

        // ---------------------------------------------------------------------
        // PASO 6: Primera Propuesta de Solución (GTS)
        // ---------------------------------------------------------------------
        $resolve1Response = $this->actingAs($gtsSpecialist)->post(
            route('incidents.resolve', $incident->folio),
            ['solution_description' => 'Limpieza química de conectores de fibra y reinicio de switch de borde; señal de video normalizada en pruebas iniciales.']
        );

        $resolve1Response->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);
        $this->assertNotNull($incident->resolved_at);

        // ---------------------------------------------------------------------
        // PASO 7: Control de Calidad APOC: Rechazo de la Solución por Falla Intermitente
        // ---------------------------------------------------------------------
        $rejectResponse = $this->actingAs($apocOperator)->post(
            route('incidents.reject-resolution', $incident->folio),
            ['rejection_reason' => 'Persiste intermitencia en pantallas SUE-05 y SUE-06 con pérdida recurrente de sincronía HDMI cada 2 minutos.']
        );

        $rejectResponse->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertEquals('Persiste intermitencia en pantallas SUE-05 y SUE-06 con pérdida recurrente de sincronía HDMI cada 2 minutos.', $incident->rejection_reason);

        // ---------------------------------------------------------------------
        // PASO 8: Segunda Intervención y Solución Definitiva (GTS)
        // ---------------------------------------------------------------------
        $this->actingAs($gtsSpecialist)->post(
            route('incidents.add-note', $incident->folio),
            ['note' => 'Sustitución física del módulo transceptor óptico SFP+ 10G y patchcord de fibra monomodo por componentes nuevos certificados de almacén.']
        );

        $resolve2Response = $this->actingAs($gtsSpecialist)->post(
            route('incidents.resolve', $incident->folio),
            ['solution_description' => 'Sustitución definitiva de módulo transceiver óptico y patchcord de fibra. Telemetría estable durante 45 minutos sin caída de paquetes.']
        );

        $resolve2Response->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);

        // ---------------------------------------------------------------------
        // PASO 9: Cierre Definitivo con Visto Bueno por APOC
        // ---------------------------------------------------------------------
        $closeResponse = $this->actingAs($apocOperator)->post(
            route('incidents.close', $incident->folio),
            ['closing_notes' => 'Visto Bueno emitido por Supervisor de Turno APOC. Pantallas de vuelos 100% operativas en salas SUE-03 a SUE-07 sin afectación a pasajeros.']
        );

        $closeResponse->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);
        $this->assertNotNull($incident->closed_at);

        // ---------------------------------------------------------------------
        // PASO 10: Verificación de Inmutabilidad (Expediente Cerrado y Sellado)
        // ---------------------------------------------------------------------
        // Intento de agregar nueva nota tras el cierre -> 403 Forbidden
        $this->actingAs($gtsSpecialist)->post(
            route('incidents.add-note', $incident->folio),
            ['note' => 'Intento ilegal de agregar nota a ticket cerrado']
        )->assertStatus(403);

        // Intento de subir nuevo archivo tras el cierre -> 403 Forbidden
        $lateFile = UploadedFile::fake()->create('tardio.pdf', 100, 'application/pdf');
        $this->actingAs($apocOperator)->post(
            route('incidents.attachments.store', $incident->folio),
            ['attachments' => [$lateFile]]
        )->assertStatus(403);

        // Intento de forzar cambio de estado tras el cierre -> 403 Forbidden
        $this->actingAs($gtsSpecialist)->post(
            route('incidents.resolve', $incident->folio),
            ['solution_description' => 'Intento ilegal de re-resolver']
        )->assertStatus(403);

        // ---------------------------------------------------------------------
        // PASO 11: Auditoría Integral y No Repudio (Peritaje Forense)
        // ---------------------------------------------------------------------
        $histories = IncidentHistory::where('incident_id', $incident->id)
            ->orderBy('id', 'asc')
            ->get();

        // Debe haber al menos 9 eventos de auditoría registrados
        $this->assertGreaterThanOrEqual(9, $histories->count());

        $actions = $histories->pluck('action')->toArray();
        $this->assertContains('CREADA', $actions);
        $this->assertContains('ADJUNTOS_CARGADOS', $actions);
        $this->assertContains('ASIGNADA', $actions);
        $this->assertContains('CAMBIO_ESTADO', $actions);
        $this->assertContains('NOTA_TECNICA', $actions);
        $this->assertContains('RESUELTA', $actions);
        $this->assertContains('RECHAZO_SOLUCION', $actions);
        $this->assertContains('CERRADA', $actions);

        // Intentar eliminar un registro de auditoría debe fallar (Inmutabilidad pericial)
        $this->expectException(\RuntimeException::class);
        $histories->first()->delete();
    }
}
