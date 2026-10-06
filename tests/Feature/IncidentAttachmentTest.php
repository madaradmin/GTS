<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IncidentAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_upload_attachments(): void
    {
        Storage::fake('local');
        $incident = Incident::factory()->create();

        $response = $this->post("/incidencias/{$incident->folio}/adjuntos", [
            'attachments' => [
                UploadedFile::fake()->create('evidencia.png', 200, 'image/png'),
            ],
        ]);

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_view_or_download_attachments(): void
    {
        Storage::fake('local');
        $incident = Incident::factory()->create();
        $attachment = IncidentAttachment::factory()->create([
            'incident_id' => $incident->id,
            'stored_path' => 'incidents/'.$incident->id.'/test.png',
        ]);

        $this->get("/incidencias/{$incident->folio}/adjuntos/{$attachment->id}/ver")
            ->assertRedirect('/login');

        $this->get("/incidencias/{$incident->folio}/adjuntos/{$attachment->id}/descargar")
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_upload_valid_attachments_to_active_incident(): void
    {
        Storage::fake('local');
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $file1 = UploadedFile::fake()->create('camara_puerta_14.jpg', 400, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('bitacora_otdr.pdf', 250, 'application/pdf');

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/adjuntos", [
                'attachments' => [$file1, $file2],
            ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        // Verificar inserción en base de datos
        $this->assertDatabaseHas('incident_attachments', [
            'incident_id' => $incident->id,
            'uploaded_by_user_id' => $apocUser->id,
            'original_filename' => 'camara_puerta_14.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertDatabaseHas('incident_attachments', [
            'incident_id' => $incident->id,
            'uploaded_by_user_id' => $apocUser->id,
            'original_filename' => 'bitacora_otdr.pdf',
            'mime_type' => 'application/pdf',
        ]);

        // Verificar almacenamiento seguro en disco local privado
        $stored = IncidentAttachment::where('incident_id', $incident->id)->get();
        $this->assertCount(2, $stored);

        foreach ($stored as $att) {
            Storage::disk('local')->assertExists($att->stored_path);
            $this->assertStringStartsWith("incidents/{$incident->id}/", $att->stored_path);
        }

        // Verificar registro de auditoría inmutable
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'ADJUNTOS_CARGADOS',
            'field_name' => 'attachments',
        ]);
    }

    public function test_cannot_upload_attachment_when_incident_is_closed(): void
    {
        Storage::fake('local');
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->cerrada()->create();

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/adjuntos", [
                'attachments' => [
                    UploadedFile::fake()->create('prueba.jpg', 150, 'image/jpeg'),
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_attachment_validation_rejects_unauthorized_mimes(): void
    {
        Storage::fake('local');
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $invalidFile = UploadedFile::fake()->create('script.php', 10, 'text/x-php');

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/adjuntos", [
                'attachments' => [$invalidFile],
            ]);

        $response->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_attachment_validation_rejects_file_exceeding_10mb(): void
    {
        Storage::fake('local');
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $oversizedFile = UploadedFile::fake()->create('video_pesado.pdf', 11264, 'application/pdf'); // 11 MB

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/adjuntos", [
                'attachments' => [$oversizedFile],
            ]);

        $response->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_attachment_validation_rejects_more_than_5_files(): void
    {
        Storage::fake('local');
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $files = [
            UploadedFile::fake()->create('f1.jpg', 50, 'image/jpeg'),
            UploadedFile::fake()->create('f2.jpg', 50, 'image/jpeg'),
            UploadedFile::fake()->create('f3.jpg', 50, 'image/jpeg'),
            UploadedFile::fake()->create('f4.jpg', 50, 'image/jpeg'),
            UploadedFile::fake()->create('f5.jpg', 50, 'image/jpeg'),
            UploadedFile::fake()->create('f6.jpg', 50, 'image/jpeg'),
        ];

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/adjuntos", [
                'attachments' => $files,
            ]);

        $response->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_authenticated_user_can_view_attachment_inline(): void
    {
        Storage::fake('local');
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $file = UploadedFile::fake()->create('evidencia_inline.jpg', 100, 'image/jpeg');
        $path = $file->storeAs("incidents/{$incident->id}", 'inline_uuid.jpg', 'local');

        $attachment = IncidentAttachment::create([
            'incident_id' => $incident->id,
            'uploaded_by_user_id' => $apocUser->id,
            'original_filename' => 'evidencia_inline.jpg',
            'stored_path' => $path,
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 102400,
        ]);

        $response = $this->actingAs($apocUser)
            ->get("/incidencias/{$incident->folio}/adjuntos/{$attachment->id}/ver");

        $response->assertOk();
        $this->assertStringContainsString('image/jpeg', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_authenticated_user_can_download_attachment(): void
    {
        Storage::fake('local');
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->enAtencion()->create();

        $file = UploadedFile::fake()->create('manual_tetra.pdf', 300, 'application/pdf');
        $path = $file->storeAs("incidents/{$incident->id}", 'manual_uuid.pdf', 'local');

        $attachment = IncidentAttachment::create([
            'incident_id' => $incident->id,
            'uploaded_by_user_id' => $gtsUser->id,
            'original_filename' => 'manual_tetra.pdf',
            'stored_path' => $path,
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 307200,
        ]);

        $response = $this->actingAs($gtsUser)
            ->get("/incidencias/{$incident->folio}/adjuntos/{$attachment->id}/descargar");

        $response->assertOk();
        $this->assertStringContainsString('manual_tetra.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_cannot_access_attachment_belonging_to_another_incident(): void
    {
        Storage::fake('local');
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $incidentA = Incident::factory()->create();
        $incidentB = Incident::factory()->create();

        $attachmentA = IncidentAttachment::factory()->create([
            'incident_id' => $incidentA->id,
            'stored_path' => 'incidents/'.$incidentA->id.'/some_file.pdf',
        ]);
        Storage::disk('local')->put($attachmentA->stored_path, 'dummy content');

        // Intento de cruzar el folio del incidente B con el ID de adjunto del incidente A
        $response = $this->actingAs($apocUser)
            ->get("/incidencias/{$incidentB->folio}/adjuntos/{$attachmentA->id}/ver");

        $response->assertNotFound();
    }

    public function test_inactive_user_cannot_access_attachments(): void
    {
        Storage::fake('local');
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $incident = Incident::factory()->create();
        $attachment = IncidentAttachment::factory()->create([
            'incident_id' => $incident->id,
            'stored_path' => 'incidents/'.$incident->id.'/test.pdf',
        ]);
        Storage::disk('local')->put($attachment->stored_path, 'test');

        $this->actingAs($inactiveUser)
            ->get("/incidencias/{$incident->folio}/adjuntos/{$attachment->id}/ver")
            ->assertForbidden();

        $this->actingAs($inactiveUser)
            ->get("/incidencias/{$incident->folio}/adjuntos/{$attachment->id}/descargar")
            ->assertForbidden();

        $this->actingAs($inactiveUser)
            ->post("/incidencias/{$incident->folio}/adjuntos", [
                'attachments' => [UploadedFile::fake()->create('test.png', 100, 'image/png')],
            ])
            ->assertForbidden();
    }
}
