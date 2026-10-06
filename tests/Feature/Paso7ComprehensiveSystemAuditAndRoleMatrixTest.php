<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\IncidentHistory;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Paso7ComprehensiveSystemAuditAndRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Perfil APOC: Verificación de permisos y restricciones estrictas.
     */
    public function test_apoc_role_matrix_permissions_and_restrictions(): void
    {
        $apoc = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        // 1.1 Reportes: Puede acceder y ve "Descargar reporte", pero NO "Reporte diario" ni "Reporte semanal"
        $repResponse = $this->actingAs($apoc)->get(route('reports.index'));
        $repResponse->assertOk();
        $repResponse->assertSee('Descargar reporte');
        $repResponse->assertDontSee('Reporte diario');
        $repResponse->assertDontSee('Reporte semanal');

        // 1.2 Indicadores: Puede acceder al módulo
        $indResponse = $this->actingAs($apoc)->get(route('indicators.index'));
        $indResponse->assertOk();
        $indResponse->assertSee('Indicadores de Rendimiento Tecnológico');

        // 1.3 Catálogos: Accede como vista completa
        $catResponse = $this->actingAs($apoc)->get(route('catalogs.index'));
        $catResponse->assertOk();
        $catResponse->assertSee('Catálogos e Infraestructura');

        // 1.4 Sidebar de APOC: Sin dropdown en Catálogos, sin Auditoría, CON Mis incidencias
        $sidebarContent = $repResponse->getContent();
        $this->assertStringContainsString(route('catalogs.index'), $sidebarContent);
        $this->assertStringNotContainsString('openCat', $sidebarContent);
        $this->assertStringNotContainsString(route('audit.index'), $sidebarContent);
        $this->assertStringContainsString(route('incidents.my'), $sidebarContent);

        // 1.5 Restricción de Auditoría
        $this->actingAs($apoc)->get(route('audit.index'))->assertForbidden();

        // 1.6 Restricción de Gestión de Usuarios
        $this->actingAs($apoc)->get(route('users.index'))->assertForbidden();
        $this->actingAs($apoc)->post(route('users.store'), [
            'name' => 'Intruso',
            'email' => 'intruso@aifa.aero',
            'role' => 'apoc',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();

        // 1.7 Restricción de Eliminación de Incidencias
        $incident = Incident::create([
            'folio' => 'INC-2026-00010',
            'title' => 'Incidencia de prueba APOC',
            'description' => 'Descripción de prueba',
            'location' => 'Edificio Terminal - Sala B',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
            'status_id' => Status::first()->id,
            'reported_by_user_id' => $apoc->id,
            'sla_due_at' => now()->addHours(4),
        ]);
        $this->actingAs($apoc)->delete(route('incidents.destroy', $incident))->assertForbidden();
        $this->actingAs($apoc)->delete(route('audit.incidents.destroy', $incident))->assertForbidden();
    }

    /**
     * 2. Perfil GTS: Verificación de permisos técnicos y restricciones estrictas.
     */
    public function test_gts_role_matrix_permissions_and_restrictions(): void
    {
        $gts = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        // 2.1 Reportes: Puede acceder y Descargar reportes
        $repResponse = $this->actingAs($gts)->get(route('reports.index'));
        $repResponse->assertOk();
        $repResponse->assertSee('Descargar reporte');
        $repResponse->assertDontSee('Reporte diario');
        $repResponse->assertDontSee('Reporte semanal');

        // 2.2 Indicadores: Puede acceder a indicadores
        $indResponse = $this->actingAs($gts)->get(route('indicators.index'));
        $indResponse->assertOk();
        $indResponse->assertSee('Indicadores de Rendimiento Tecnológico');

        // 2.3 Catálogos: Puede acceder a catálogos
        $catResponse = $this->actingAs($gts)->get(route('catalogs.index'));
        $catResponse->assertOk();

        // 2.4 Operación técnica propia: Mis incidencias
        $myIncResponse = $this->actingAs($gts)->get(route('incidents.my'));
        $myIncResponse->assertOk();
        $myIncResponse->assertSee('Mis Incidencias');
        $this->actingAs($gts)->post('/profile/shift-status')->assertNotFound();

        // 2.5 Restricción de Auditoría
        $this->actingAs($gts)->get(route('audit.index'))->assertForbidden();

        // 2.6 Restricción de Gestión de Usuarios
        $this->actingAs($gts)->get(route('users.index'))->assertForbidden();
        $this->actingAs($gts)->delete(route('users.destroy', $gts))->assertForbidden();

        // 2.7 Restricción de Eliminación de Incidencias
        $incident = Incident::create([
            'folio' => 'INC-2026-00020',
            'title' => 'Incidencia de prueba GTS',
            'description' => 'Descripción de prueba',
            'location' => 'Edificio Terminal - Sala B',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
            'status_id' => Status::first()->id,
            'reported_by_user_id' => $gts->id,
            'sla_due_at' => now()->addHours(4),
        ]);
        $this->actingAs($gts)->delete(route('incidents.destroy', $incident))->assertForbidden();
        $this->actingAs($gts)->delete(route('audit.incidents.destroy', $incident))->assertForbidden();
    }

    /**
     * 3. Perfil ADMINISTRADOR: Verificación de acceso completo y operaciones críticas.
     */
    public function test_admin_role_matrix_permissions_and_full_operations(): void
    {
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        // 3.1 Reportes: Acceso y unificación
        $repResponse = $this->actingAs($admin)->get(route('reports.index'));
        $repResponse->assertOk();
        $repResponse->assertSee('Descargar reporte');
        $repResponse->assertDontSee('Reporte diario');
        $repResponse->assertDontSee('Reporte semanal');

        // 3.2 Indicadores: Telemetría completa OACI / AFAC
        $indResponse = $this->actingAs($admin)->get(route('indicators.index'));
        $indResponse->assertOk();
        $indResponse->assertSee('MTTR');
        $indResponse->assertSee('MTTA');
        $indResponse->assertSee('Eficiencia de Resolución');

        // 3.3 Auditoría: Acceso completo y eliminación de incidencias
        $auditIncident = Incident::create([
            'folio' => 'INC-2026-00030',
            'title' => 'Incidencia de prueba Auditoría',
            'description' => 'Descripción de prueba',
            'location' => 'Edificio Terminal - Sala B',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
            'status_id' => Status::first()->id,
            'reported_by_user_id' => $admin->id,
            'sla_due_at' => now()->addHours(4),
        ]);
        IncidentHistory::create([
            'incident_id' => $auditIncident->id,
            'user_id' => $admin->id,
            'action' => 'created',
            'comment' => 'Registro de prueba para auditoría',
        ]);

        $auditResponse = $this->actingAs($admin)->get(route('audit.index'));
        $auditResponse->assertOk();
        $auditResponse->assertSee('Bitácora General de Auditoría');
        $auditResponse->assertSee('Confirmar Eliminación Permanente');
        $auditResponse->assertSee('Eliminar');

        // 3.4 Eliminación segura desde Auditoría con cascada y limpieza física
        Storage::fake('local');
        $newIncident = Incident::create([
            'folio' => 'INC-2026-99999',
            'title' => 'Incidencia para prueba de eliminación admin',
            'description' => 'Descripción de prueba para auditoría final',
            'location' => 'Edificio Terminal - Sala B',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
            'status_id' => Status::first()->id,
            'reported_by_user_id' => $admin->id,
            'sla_due_at' => now()->addHours(4),
        ]);

        $file = UploadedFile::fake()->create('evidencia_audit.pdf', 500, 'application/pdf');
        $path = $file->store('attachments/'.$newIncident->id, 'local');
        IncidentAttachment::create([
            'incident_id' => $newIncident->id,
            'original_filename' => 'evidencia_audit.pdf',
            'stored_path' => $path,
            'file_size_bytes' => 500 * 1024,
            'mime_type' => 'application/pdf',
            'uploaded_by_user_id' => $admin->id,
        ]);

        $delResponse = $this->actingAs($admin)->delete(route('audit.incidents.destroy', $newIncident));
        $delResponse->assertRedirect(route('audit.index'));
        $this->assertDatabaseMissing('incidents', ['id' => $newIncident->id]);
        $this->assertDatabaseMissing('incident_attachments', ['incident_id' => $newIncident->id]);

        // 3.5 Catálogos: Enlace directo y conservación de las 5 categorías esenciales
        $catResponse = $this->actingAs($admin)->get(route('catalogs.index'));
        $catResponse->assertOk();
        $this->assertDatabaseHas('categories', ['slug' => 'cctv']);
        $this->assertDatabaseHas('categories', ['slug' => 'access-control']);
        $this->assertDatabaseHas('categories', ['slug' => 'fids']);
        $this->assertDatabaseHas('categories', ['slug' => 'tetra']);
        $this->assertDatabaseHas('categories', ['slug' => 'networks']);

        // 3.6 Configuración y Gestión de Usuarios: Acceso y operaciones
        $usersResponse = $this->actingAs($admin)->get(route('users.index'));
        $usersResponse->assertOk();
        $usersResponse->assertSee('Gestión de Usuarios y Cuadrillas');
        $usersResponse->assertSee('Registrar Usuario');

        // Alta de usuario
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();
        $createResponse = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Técnico Especialista Fase 7',
            'username' => 'tecnico.fase7',
            'email' => 'tecnico.fase7@aifa.aero',
            'role_id' => $gtsRole->id,
            'password' => 'Temporal2026!',
            'badge_number' => 'GTS-777',
        ]);
        $createResponse->assertRedirect(route('users.index'));
        $createdUser = User::where('username', 'tecnico.fase7')->firstOrFail();
        $this->assertEquals('gts', $createdUser->role->slug);

        // Eliminación física directa de usuario sin historial
        $delUserResp = $this->actingAs($admin)->delete(route('users.destroy', $createdUser));
        $delUserResp->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $createdUser->id]);
    }

    /**
     * 4. Auditoría de Vistas y Código: Ausencia de referencias obsoletas a Reportes y Catálogos dropdown.
     */
    public function test_absence_of_obsolete_menu_and_dropdown_references(): void
    {
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $html = $response->getContent();

        // En el sidebar, no debe existir "Reporte diario" ni "Reporte semanal"
        $this->assertStringNotContainsString('Reporte diario', $html);
        $this->assertStringNotContainsString('Reporte semanal', $html);

        // En el sidebar, Catálogos debe ser un enlace directo sin botón de collapse ni submenú
        $this->assertStringContainsString('href="'.route('catalogs.index').'"', $html);
        $this->assertStringNotContainsString('openCat', $html);
        $this->assertStringNotContainsString('catOpen', $html);

        // En el sidebar, Descargar reporte debe ser un enlace directo a reports.index
        $this->assertStringContainsString('href="'.route('reports.index').'"', $html);
        $this->assertStringContainsString('Descargar reporte', $html);

        // En el sidebar, Indicadores debe ser un enlace directo a indicators.index
        $this->assertStringContainsString('href="'.route('indicators.index').'"', $html);
        $this->assertStringContainsString('Indicadores', $html);
    }
}
