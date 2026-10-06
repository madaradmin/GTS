<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('private');
    }

    public function test_http_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_inactive_user_cannot_authenticate_via_login(): void
    {
        $user = User::factory()->apoc()->inactive()->create([
            'username' => 'inactivo',
            'email' => 'inactivo@aifa.aero',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->post('/login', [
            'username' => 'inactivo',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_inactive_user_is_blocked_from_profile_routes(): void
    {
        $inactiveUser = User::factory()->inactive()->create();

        $this->actingAs($inactiveUser)->get('/profile')->assertStatus(403);
        $this->actingAs($inactiveUser)->patch('/profile', ['name' => 'Hacker'])->assertStatus(403);
        $this->actingAs($inactiveUser)->delete('/profile', ['password' => 'password'])->assertStatus(403);
    }

    public function test_xss_payload_in_incident_creation_is_escaped_in_html(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $category = Category::where('slug', 'cctv')->firstOrFail();
        $priority = Priority::where('slug', 'alta')->firstOrFail();

        $xssTitle = '<script>alert("XSS-ATTACK")</script> Falla Cámara';
        $xssDescription = '<img src=x onerror=alert("XSS")> Pérdida de señal';

        $response = $this->actingAs($apocUser)->post(route('incidents.store'), [
            'title' => $xssTitle,
            'description' => $xssDescription,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'Lado Tierra - Puerta 3',
        ]);

        $incident = Incident::latest('id')->first();
        $this->assertNotNull($incident);

        // Al consultar la vista detallada, los tags <script> deben estar escapados como entidades HTML (&lt;script&gt;)
        $showResponse = $this->actingAs($apocUser)->get(route('incidents.show', $incident->folio));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('&lt;script&gt;alert(&quot;XSS-ATTACK&quot;)&lt;/script&gt;', false);
        $showResponse->assertDontSee('<script>alert("XSS-ATTACK")</script>', false);
    }

    public function test_mass_assignment_protection_ignores_unauthorized_fields(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $category = Category::where('slug', 'fids')->firstOrFail();
        $priority = Priority::where('slug', 'media')->firstOrFail();
        $closedStatus = Status::where('code', 'CERRADA')->firstOrFail();

        // Intento de inyectar folio forzado y forzar el estado directo a CERRADA
        $this->actingAs($apocUser)->post(route('incidents.store'), [
            'folio' => 'INC-9999-9999',
            'status_id' => $closedStatus->id,
            'title' => 'Intento de spoofing de estado',
            'description' => 'Prueba de protección Mass Assignment',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'SUE-01',
        ]);

        $incident = Incident::latest('id')->first();
        $this->assertNotNull($incident);
        // El folio debe ser generado por FolioGeneratorService, no el inyectado
        $this->assertNotEquals('INC-9999-9999', $incident->folio);
        // El estado debe ser NUEVA, no CERRADA
        $this->assertEquals('NUEVA', $incident->status->code);
    }

    public function test_uploading_dangerous_file_extensions_is_rejected(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        // Intentar subir script PHP malicioso disfrazado
        $phpFile = UploadedFile::fake()->create('shell.php', 50, 'application/x-php');

        $response = $this->actingAs($apocUser)->post(route('incidents.attachments.store', $incident->folio), [
            'attachments' => [$phpFile],
        ]);

        $response->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_uploading_executable_file_is_rejected(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $exeFile = UploadedFile::fake()->create('trojan.exe', 500, 'application/x-msdownload');

        $response = $this->actingAs($apocUser)->post(route('incidents.attachments.store', $incident->folio), [
            'attachments' => [$exeFile],
        ]);

        $response->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_uploading_file_exceeding_10mb_is_rejected(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        // 11 MB (11264 KB) supera el límite de 10240 KB
        $oversizedFile = UploadedFile::fake()->create('big_dump.pdf', 11264, 'application/pdf');

        $response = $this->actingAs($apocUser)->post(route('incidents.attachments.store', $incident->folio), [
            'attachments' => [$oversizedFile],
        ]);

        $response->assertSessionHasErrors('attachments.0');
        $this->assertDatabaseCount('incident_attachments', 0);
    }

    public function test_cannot_access_attachment_of_another_incident_idor_protection(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $incidentA = Incident::factory()->nueva()->create();
        $incidentB = Incident::factory()->nueva()->create();

        // Archivo que pertenece legalmente a la incidencia B
        $attachmentB = IncidentAttachment::factory()->create([
            'incident_id' => $incidentB->id,
            'stored_path' => 'attachments/fake-uuid.pdf',
            'original_filename' => 'confidential.pdf',
        ]);

        // Manipulación IDOR: Solicitamos attachmentB pero usando la URL del expediente incidentA
        $response = $this->actingAs($apocUser)->get(
            route('incidents.attachments.show', [$incidentA->folio, $attachmentB->id])
        );

        $response->assertStatus(404);
    }

    public function test_cannot_upload_attachments_to_closed_incident(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $closedIncident = Incident::factory()->cerrada()->create();

        $validFile = UploadedFile::fake()->create('evidence.jpg', 200, 'image/jpeg');

        $response = $this->actingAs($apocUser)->post(
            route('incidents.attachments.store', $closedIncident->folio),
            ['attachments' => [$validFile]]
        );

        $response->assertStatus(403);
    }

    public function test_non_existent_folio_returns_404_not_found(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get('/incidencias/INC-9999-9999');

        $response->assertStatus(404);
    }

    public function test_rbac_matrix_cross_role_unauthorized_actions_are_forbidden(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $newIncident = Incident::factory()->nueva()->create();
        $inAttentionIncident = Incident::factory()->enAtencion($gtsUser)->create();
        $resolvedIncident = Incident::factory()->resuelta()->create();

        // 1. APOC no puede agregar notas técnicas
        $this->actingAs($apocUser)->post(route('incidents.add-note', $inAttentionIncident->folio), [
            'note' => 'Nota técnica no autorizada',
        ])->assertStatus(403);

        // 3. APOC no puede resolver incidencias
        $this->actingAs($apocUser)->post(route('incidents.resolve', $inAttentionIncident->folio), [
            'solution_description' => 'APOC resolviendo sin visto de campo',
        ])->assertStatus(403);

        // 4. No se puede cerrar una incidencia que no esté en estado RESUELTA (e.g. EN_ATENCION)
        $this->actingAs($gtsUser)->post(route('incidents.close', $inAttentionIncident->folio), [
            'comment' => 'Intento indebido de cierre en atención',
        ])->assertStatus(403);

        // 5. GTS no puede rechazar soluciones
        $this->actingAs($gtsUser)->post(route('incidents.reject-resolution', $resolvedIncident->folio), [
            'rejection_reason' => 'GTS rechazando solución',
        ])->assertStatus(403);
    }
}
