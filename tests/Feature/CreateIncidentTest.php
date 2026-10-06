<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\IncidentHistory;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateIncidentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login_when_accessing_create_view(): void
    {
        $response = $this->get('/incidencias/nueva');

        $response->assertRedirect('/login');
    }

    public function test_gts_user_can_access_create_view(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $response = $this->actingAs($gtsUser)->get('/incidencias/nueva');

        $response->assertOk();
        $response->assertSee('Nueva Incidencia');
        $response->assertSee('Canal GTS Activo');
    }

    public function test_apoc_user_can_access_create_view_and_sees_preliminary_folio(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get('/incidencias/nueva');

        $response->assertOk();
        $response->assertSee('Nueva Incidencia');
        $response->assertSee('INC-'.date('Y'));
        $response->assertViewHas(['categories', 'priorities', 'preliminaryFolio']);
    }

    public function test_apoc_user_can_create_incident_successfully(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $payload = [
            'title' => 'Falla en lector biométrico torniquete 02',
            'description' => 'El torniquete 02 de la puerta 14B no reconoce tarjetas de embarque ni pases aeroportuarios.',
            'location' => 'Terminal de Pasajeros - Puerta 14B',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ];

        $response = $this->actingAs($apocUser)->post('/incidencias', $payload);

        $incident = Incident::where('title', $payload['title'])->first();

        $this->assertNotNull($incident);
        $this->assertEquals('NUEVA', $incident->status->code);
        $this->assertEquals($apocUser->id, $incident->reported_by_user_id);
        $this->assertNull($incident->assigned_to_user_id);

        // Redirección con éxito a show
        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        // Verificar historial de auditoría inmutable
        $history = IncidentHistory::where('incident_id', $incident->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('CREADA', $history->action);
        $this->assertEquals($apocUser->id, $history->user_id);
    }

    public function test_apoc_user_can_upload_attachments_during_incident_creation(): void
    {
        Storage::fake('local');

        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $file = UploadedFile::fake()->create('evidencia_cctv.jpg', 500, 'image/jpeg');

        $payload = [
            'title' => 'Cámara domo PTZ 04 sin respuesta de telemetría',
            'description' => 'La cámara no responde a comandos de movimiento desde el joystick de la consola C2.',
            'location' => 'Perímetro Bravo - Caseta 4',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'attachments' => [$file],
        ];

        $response = $this->actingAs($apocUser)->post('/incidencias', $payload);

        $incident = Incident::where('title', $payload['title'])->firstOrFail();
        $response->assertRedirect(route('incidents.show', $incident->folio));

        $attachment = IncidentAttachment::where('incident_id', $incident->id)->first();
        $this->assertNotNull($attachment);
        $this->assertEquals('evidencia_cctv.jpg', $attachment->original_filename);
        $this->assertEquals($apocUser->id, $attachment->uploaded_by_user_id);

        Storage::disk('local')->assertExists($attachment->stored_path);

        // Verificar registro de auditoría para la carga de adjuntos
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ADJUNTOS_CARGADOS',
            'user_id' => $apocUser->id,
        ]);
    }

    public function test_gts_user_can_submit_incident_creation(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();

        $payload = [
            'title' => 'Reporte creado por usuario técnico GTS',
            'description' => 'Un usuario con rol GTS ahora cuenta con facultades de registro de incidencias.',
            'location' => 'Servidores Centrales',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ];

        $response = $this->actingAs($gtsUser)->post('/incidencias', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('incidents', [
            'title' => $payload['title'],
            'reported_by_user_id' => $gtsUser->id,
        ]);
    }

    public function test_incident_creation_fails_with_invalid_data(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->post('/incidencias', []);

        $response->assertSessionHasErrors([
            'title',
            'description',
            'location',
            'category_id',
            'priority_id',
        ]);
    }

    public function test_incident_creation_view_displays_updated_support_extension_and_simplified_dispatch(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get(route('incidents.create'));

        $response->assertOk();
        $response->assertSee('Soporte 55-57-98-98-00 Ext. 13202');
        $response->assertDontSee('Ext. 4209 / 4210');
        $response->assertSee('Despacho Automático: GTS');
        $response->assertDontSee('Mesa GTS Guardia 2 (C4 & Pistas)');
    }
}
