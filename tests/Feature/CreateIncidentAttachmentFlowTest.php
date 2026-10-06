<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\Priority;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateIncidentAttachmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_apoc_user_can_create_incident_with_multiple_attachments_of_different_types(): void
    {
        $apocUser = User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc', 'apoc-cco']))->firstOrFail();
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $fileImage = UploadedFile::fake()->create('captura_falla.png', 300, 'image/png');
        $filePdf = UploadedFile::fake()->create('manual_tecnico.pdf', 1200, 'application/pdf');
        $fileLog = UploadedFile::fake()->create('syslog.txt', 50, 'text/plain');

        $payload = [
            'title' => 'Falla en enlace de fibra óptica Rack B12',
            'description' => 'Pérdida de conectividad intermitente detectada en switch troncal con alarmas rojas.',
            'location' => 'Sala de Comunicaciones Nivel 1',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'attachments' => [$fileImage, $filePdf, $fileLog],
        ];

        $response = $this->actingAs($apocUser)->post(route('incidents.store'), $payload);

        $incident = Incident::where('title', $payload['title'])->firstOrFail();
        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $attachments = IncidentAttachment::where('incident_id', $incident->id)->get();
        $this->assertCount(3, $attachments);

        $filenames = $attachments->pluck('original_filename')->all();
        $this->assertContains('captura_falla.png', $filenames);
        $this->assertContains('manual_tecnico.pdf', $filenames);
        $this->assertContains('syslog.txt', $filenames);

        foreach ($attachments as $att) {
            $this->assertEquals($apocUser->id, $att->uploaded_by_user_id);
            Storage::disk('local')->assertExists($att->stored_path);
        }

        // Auditoría inmutable de carga de evidencias
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ADJUNTOS_CARGADOS',
            'field_name' => 'attachments',
            'user_id' => $apocUser->id,
            'comment' => '3 archivo(s) de evidencia adjuntado(s) al expediente.',
        ]);

        // Verificación de visualización/descarga de adjuntos de la incidencia creada
        $firstAttachment = $attachments->first();
        $viewResponse = $this->actingAs($apocUser)->get(route('incidents.attachments.show', [$incident, $firstAttachment]));
        $viewResponse->assertOk();

        $downloadResponse = $this->actingAs($apocUser)->get(route('incidents.attachments.download', [$incident, $firstAttachment]));
        $downloadResponse->assertOk();
    }

    public function test_form_submission_with_browser_empty_attachment_input_passes_validation(): void
    {
        $apocUser = User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc', 'apoc-cco']))->firstOrFail();
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        // Simular lo que un navegador envía cuando el input attachments[] está presente pero no se seleccionó archivo
        $emptyBrowserFile = new UploadedFile(
            path: '',
            originalName: '',
            mimeType: 'application/octet-stream',
            error: UPLOAD_ERR_NO_FILE,
            test: true
        );

        $payload = [
            'title' => 'Incidencia creada sin adjuntar archivos de evidencia',
            'description' => 'Reporte preventivo de inspección de cableado sin imágenes ni bitácoras.',
            'location' => 'Área de bandas de reclamo de equipaje',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'attachments' => [$emptyBrowserFile],
        ];

        $response = $this->actingAs($apocUser)->post(route('incidents.store'), $payload);

        $incident = Incident::where('title', $payload['title'])->firstOrFail();
        $response->assertRedirect(route('incidents.show', $incident->folio));

        $this->assertDatabaseCount('incident_attachments', 0);
        $this->assertDatabaseMissing('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ADJUNTOS_CARGADOS',
        ]);
    }

    public function test_all_incident_creator_roles_can_attach_files_during_creation(): void
    {
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $roleSlugs = ['admin', 'gts', 'apoc-cco', 'apoc-csa'];

        foreach ($roleSlugs as $slug) {
            $user = User::whereHas('role', fn ($q) => $q->where('slug', $slug))->first();
            if (! $user) {
                $role = Role::where('slug', $slug)->firstOrFail();
                $user = User::factory()->create([
                    'role_id' => $role->id,
                    'is_active' => true,
                ]);
            }

            $evidence = UploadedFile::fake()->create("evidencia_{$slug}.jpg", 200, 'image/jpeg');

            $payload = [
                'title' => "Incidencia creada por rol {$slug} con evidencia",
                'description' => "Descripción detallada para la verificación de carga por rol {$slug}.",
                'location' => 'Zona Operativa Central',
                'category_id' => $category->id,
                'priority_id' => $priority->id,
                'attachments' => [$evidence],
            ];

            $response = $this->actingAs($user)->post(route('incidents.store'), $payload);

            $incident = Incident::where('title', $payload['title'])->firstOrFail();
            $response->assertRedirect(route('incidents.show', $incident->folio));

            $att = IncidentAttachment::where('incident_id', $incident->id)->first();
            $this->assertNotNull($att);
            $this->assertEquals("evidencia_{$slug}.jpg", $att->original_filename);
            $this->assertEquals($user->id, $att->uploaded_by_user_id);
            Storage::disk('local')->assertExists($att->stored_path);
        }
    }

    public function test_attachment_validation_rejects_more_than_5_files_during_creation(): void
    {
        $apocUser = User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc', 'apoc-cco']))->firstOrFail();
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $files = [];
        for ($i = 1; $i <= 6; $i++) {
            $files[] = UploadedFile::fake()->create("file_{$i}.png", 100, 'image/png');
        }

        $payload = [
            'title' => 'Incidencia con exceso de adjuntos no permitidos',
            'description' => 'Intento de enviar 6 archivos superando el límite de 5 configurado.',
            'location' => 'Puerta de Embarque 05',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'attachments' => $files,
        ];

        $response = $this->actingAs($apocUser)->post(route('incidents.store'), $payload);
        $response->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_attachment_validation_rejects_oversized_file_during_creation(): void
    {
        $apocUser = User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc', 'apoc-cco']))->firstOrFail();
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $oversizedFile = UploadedFile::fake()->create('heavy_video.mp4', 11000, 'video/mp4');

        $payload = [
            'title' => 'Incidencia con archivo pesado que excede 10MB',
            'description' => 'Intento de subir archivo de 11MB no permitido por la política.',
            'location' => 'Pista 05L',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'attachments' => [$oversizedFile],
        ];

        $response = $this->actingAs($apocUser)->post(route('incidents.store'), $payload);
        $response->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_attachment_validation_rejects_prohibited_extension_during_creation(): void
    {
        $apocUser = User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc', 'apoc-cco']))->firstOrFail();
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $phpFile = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $payload = [
            'title' => 'Incidencia con extensión ejecutable prohibida',
            'description' => 'Intento de subir archivo .php como evidencia durante el registro.',
            'location' => 'Torre de Control',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'attachments' => [$phpFile],
        ];

        $response = $this->actingAs($apocUser)->post(route('incidents.store'), $payload);
        $response->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_data_isolation_between_apoc_cco_and_apoc_csa_for_created_incident_and_attachments(): void
    {
        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();
        $csaRole = Role::where('slug', 'apoc-csa')->firstOrFail();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $ccoUser = User::factory()->create(['role_id' => $ccoRole->id, 'is_active' => true]);
        $csaUser = User::factory()->create(['role_id' => $csaRole->id, 'is_active' => true]);
        $adminUser = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);

        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $evidence = UploadedFile::fake()->create('cco_evidence.png', 100, 'image/png');

        $payload = [
            'title' => 'Incidencia confidencial creada por CCO',
            'description' => 'Reporte específico de CCO con adjunto sensible.',
            'location' => 'Sala de Crisis CCO',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'attachments' => [$evidence],
        ];

        $response = $this->actingAs($ccoUser)->post(route('incidents.store'), $payload);
        $incident = Incident::where('title', $payload['title'])->firstOrFail();
        $response->assertRedirect(route('incidents.show', $incident->folio));

        $attachment = IncidentAttachment::where('incident_id', $incident->id)->firstOrFail();

        // 1. CCO creador puede ver la incidencia y descargar su adjunto
        $this->actingAs($ccoUser)->get(route('incidents.show', $incident->folio))->assertOk();
        $this->actingAs($ccoUser)->get(route('incidents.attachments.download', [$incident, $attachment]))->assertOk();

        // 2. CSA no puede ver la incidencia ni descargar el adjunto (Aislamiento de perfil)
        $this->actingAs($csaUser)->get(route('incidents.show', $incident->folio))->assertForbidden();
        $this->actingAs($csaUser)->get(route('incidents.attachments.download', [$incident, $attachment]))->assertForbidden();

        // 3. Administrador sí tiene acceso a la incidencia y descarga del adjunto
        $this->actingAs($adminUser)->get(route('incidents.show', $incident->folio))->assertOk();
        $this->actingAs($adminUser)->get(route('incidents.attachments.download', [$incident, $attachment]))->assertOk();
    }

    public function test_create_view_renders_attachment_dropzone_and_input(): void
    {
        $apocUser = User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc', 'apoc-cco']))->firstOrFail();

        $response = $this->actingAs($apocUser)->get(route('incidents.create'));

        $response->assertOk();
        $response->assertSee('Evidencia o Adjuntos');
        $response->assertSee('attachments[]');
        $response->assertSee('enctype="multipart/form-data"', false);
        $response->assertSee('attachmentUploader()', false);
    }
}
