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

class IncidentDeletionTest extends TestCase
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

    public function test_guest_cannot_delete_incident(): void
    {
        $incident = Incident::factory()->create();

        $response = $this->delete(route('audit.incidents.destroy', $incident->folio));
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('incidents', ['id' => $incident->id]);
    }

    public function test_apoc_and_gts_cannot_delete_incident(): void
    {
        $incident = Incident::factory()->create();

        // APOC
        $respApoc = $this->actingAs($this->apocUser)->delete(route('audit.incidents.destroy', $incident->folio));
        $respApoc->assertForbidden();

        // GTS
        $respGts = $this->actingAs($this->gtsUser)->delete(route('audit.incidents.destroy', $incident->folio));
        $respGts->assertForbidden();

        $this->assertDatabaseHas('incidents', ['id' => $incident->id]);
    }

    public function test_admin_can_delete_incident_and_cascades_safely(): void
    {
        Storage::fake('local');

        $incident = Incident::factory()->create(['folio' => 'FOL-DEL-001']);

        // Crear historial
        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $this->apocUser->id,
            'action' => 'CREADA',
            'comment' => 'Incidencia de prueba para borrado.',
            'created_at' => now(),
        ]);

        // Crear adjunto simulado
        $file = UploadedFile::fake()->create('evidencia.png', 100);
        $storedPath = $file->storeAs('incidents/'.$incident->id, 'evidencia.png', 'local');

        IncidentAttachment::create([
            'incident_id' => $incident->id,
            'uploaded_by_user_id' => $this->apocUser->id,
            'original_filename' => 'evidencia.png',
            'stored_path' => $storedPath,
            'mime_type' => 'image/png',
            'file_size_bytes' => 100,
        ]);

        // Crear notificación asociada
        $this->apocUser->notify(new IncidentNotification(
            type: 'incident_created',
            title: 'Incidencia Registrada',
            message: "La incidencia {$incident->folio} ha sido creada.",
            incidentId: $incident->id,
            folio: $incident->folio,
            actionUrl: route('incidents.show', $incident->folio)
        ));

        $this->adminUser->notify(new IncidentNotification(
            type: 'incident_created_admin',
            title: "Nueva Incidencia: {$incident->folio}",
            message: "Apertura de ticket {$incident->folio}.",
            incidentId: $incident->id,
            folio: $incident->folio,
            actionUrl: route('incidents.show', $incident->folio)
        ));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->apocUser->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->adminUser->id,
        ]);

        Storage::disk('local')->assertExists($storedPath);
        $this->assertDatabaseHas('incidents', ['id' => $incident->id]);
        $this->assertDatabaseHas('incident_histories', ['incident_id' => $incident->id]);
        $this->assertDatabaseHas('incident_attachments', ['incident_id' => $incident->id]);

        // Ejecutar eliminación como Admin
        $response = $this->actingAs($this->adminUser)->delete(route('audit.incidents.destroy', $incident->folio));

        $response->assertRedirect(route('audit.index'));
        $response->assertSessionHas('status_message');

        // Verificar que se eliminó de la base de datos y cascada
        $this->assertDatabaseMissing('incidents', ['id' => $incident->id]);
        $this->assertDatabaseMissing('incident_histories', ['incident_id' => $incident->id]);
        $this->assertDatabaseMissing('incident_attachments', ['incident_id' => $incident->id]);

        // Verificar que las notificaciones vinculadas fueron purgadas
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->apocUser->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->adminUser->id,
        ]);

        // Verificar que los archivos físicos en disco fueron purgados
        Storage::disk('local')->assertMissing($storedPath);
    }

    public function test_audit_index_shows_delete_button_for_admin(): void
    {
        $incident = Incident::factory()->create(['folio' => 'FOL-DEL-002']);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $this->apocUser->id,
            'action' => 'CREADA',
            'comment' => 'Evento para probar botón en auditoría.',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('audit.index'));

        $response->assertStatus(200);
        $response->assertSee('Eliminar');
        $response->assertSee('FOL-DEL-002');
        $response->assertSee('Confirmar Eliminación Permanente');
    }
}
