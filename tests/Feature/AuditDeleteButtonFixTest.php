<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\IncidentHistory;
use App\Models\User;
use App\Notifications\IncidentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditDeleteButtonFixTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $apocUser;

    protected User $gtsUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
    }

    /**
     * 1. Comprobar que en la vista de Auditoría, la columna de Acción tiene espacio suficiente (w-[10%])
     * y el botón Eliminar está presente con su icono, etiqueta y estilos sin truncamiento.
     */
    public function test_audit_view_renders_delete_button_with_full_visibility_and_proper_column_width(): void
    {
        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-DELTEST1',
            'title' => 'Incidencia para auditoría visual de botón eliminar',
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $this->adminUser->id,
            'action' => 'CREADA',
            'comment' => 'Evento inicial de auditoría',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $response->assertOk();

        // Ancho de columna del 10% para la columna de Acción en ambas tablas
        $response->assertSee('w-[10%] py-2.5 px-2 text-center font-semibold">Acción', false);

        // Botón Eliminar con icono y texto completo, centrado y con whitespace-nowrap
        $response->assertSee('Eliminar');
        $response->assertSee('material-symbols-outlined text-[15px] shrink-0">delete', false);
        $response->assertSee('confirmDelete(\'INC-2026-DELTEST1\'', false);
        $response->assertSee(route('audit.incidents.destroy', 'INC-2026-DELTEST1'));

        // Modal de confirmación con listener de escape
        $response->assertSee('@keydown.escape.window="showDeleteModal = false"', false);
        $response->assertSee('Confirmar Eliminación Permanente');
        $response->assertSee('Eliminar Definitivamente');
    }

    /**
     * 2. Comprobar que la eliminación ejecutada desde la ruta del botón de Auditoría
     * purga el expediente, archivos, historial y notificaciones.
     */
    public function test_audit_delete_button_action_successfully_purges_incident_and_cascades(): void
    {
        Storage::fake('local');

        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-PURGE-01',
            'title' => 'Incidencia a purgar desde Auditoría',
        ]);

        $history = IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $this->adminUser->id,
            'action' => 'ASIGNACION',
            'comment' => 'Registro de prueba de asignación',
        ]);

        $file = UploadedFile::fake()->create('evidencia_audit.pdf', 250);
        $storedPath = $file->storeAs('incidents/'.$incident->id, 'evidencia_audit.pdf', 'local');

        $attachment = IncidentAttachment::create([
            'incident_id' => $incident->id,
            'uploaded_by_user_id' => $this->adminUser->id,
            'original_filename' => 'evidencia_audit.pdf',
            'stored_path' => $storedPath,
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 250000,
        ]);

        $this->adminUser->notify(new IncidentNotification(
            type: 'incident_created_admin',
            title: 'Notificación de Auditoría',
            message: 'Incidencia creada',
            incidentId: $incident->id,
            folio: $incident->folio,
            actionUrl: route('incidents.show', $incident->folio)
        ));

        // Administrador ejecuta la eliminación
        $response = $this->actingAs($this->adminUser)
            ->delete(route('audit.incidents.destroy', $incident->folio));

        $response->assertRedirect(route('audit.index'));
        $response->assertSessionHas('status_message');

        // Verificación en base de datos
        $this->assertDatabaseMissing('incidents', ['id' => $incident->id]);
        $this->assertDatabaseMissing('incident_histories', ['id' => $history->id]);
        $this->assertDatabaseMissing('incident_attachments', ['id' => $attachment->id]);
        $this->assertDatabaseMissing('notifications', ['data->incident_id' => $incident->id]);
    }

    /**
     * 3. Comprobar que usuarios con rol APOC o GTS tienen prohibido el acceso a la bitácora
     * y a la ejecución de eliminación.
     */
    public function test_non_admins_cannot_access_audit_or_trigger_delete_button(): void
    {
        $incident = Incident::factory()->create(['folio' => 'INC-2026-FORBIDDEN']);

        // APOC denegado
        $this->actingAs($this->apocUser)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($this->apocUser)->delete(route('audit.incidents.destroy', $incident->folio))->assertForbidden();

        // GTS denegado
        $this->actingAs($this->gtsUser)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($this->gtsUser)->delete(route('audit.incidents.destroy', $incident->folio))->assertForbidden();

        // Expediente permanece intacto en PostgreSQL
        $this->assertDatabaseHas('incidents', ['id' => $incident->id]);
    }
}
