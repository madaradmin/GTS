<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5IntegralSystemVerificationTest extends TestCase
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

    /*
    |--------------------------------------------------------------------------
    | 1. NAVEGACIÓN Y UX
    |--------------------------------------------------------------------------
    */

    public function test_sidebar_is_initially_hidden_with_static_classes_to_prevent_fouc(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        // 1. Sidebar inicia estáticamente con -translate-x-full shadow-none
        $this->assertStringContainsString('fixed inset-y-0 left-0 z-40 top-16 w-64', $content);
        $this->assertStringContainsString('-translate-x-full shadow-none', $content);

        // 2. Transición suave de 200ms
        $this->assertStringContainsString('transition-transform duration-200 ease-out', $content);

        // 3. Zona sensible invisible para activación por proximidad en extremo izquierdo (0 a 14px)
        $this->assertStringContainsString('fixed top-16 bottom-0 left-0 w-3.5 z-30 pointer-events-auto bg-transparent', $content);
        $this->assertStringContainsString('@mouseenter="openSidebar()"', $content);

        // 4. El controlador reactivo maneja debounce anti-parpadeo
        $this->assertStringContainsString('sidebarHover: false', $content);
        $this->assertStringContainsString('openSidebar()', $content);
        $this->assertStringContainsString('closeSidebar()', $content);
        $this->assertStringContainsString('hoverTimeout: null', $content);
    }

    public function test_hamburger_menu_is_completely_eliminated_across_layout_and_topbar(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        // Cero rastro de menú hamburguesa
        $this->assertStringNotContainsString('sidebarOpen = !sidebarOpen', $content);
        $this->assertStringNotContainsString('Alternar Menú Lateral', $content);
        $this->assertStringNotContainsString('hamburger', strtolower($content));

        // El contenido principal no es empujado (overflow-x-hidden)
        $this->assertStringContainsString('overflow-x-hidden', $content);
    }

    public function test_sidebar_navigation_links_are_all_functional(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();

        $response->assertSee(route('dashboard'));
        $response->assertSee(route('incidents.index'));
        $response->assertSee(route('reports.index'));
        $response->assertSee(route('indicators.index'));
        $response->assertSee(route('catalogs.index'));
        $response->assertSee(route('audit.index'));
        $response->assertSee(route('profile.edit'));
    }

    /*
    |--------------------------------------------------------------------------
    | 2. AUTENTICACIÓN MEDIANTE USERNAME
    |--------------------------------------------------------------------------
    */

    public function test_login_authenticates_with_valid_username_and_password(): void
    {
        $response = $this->post(route('login'), [
            'username' => 'admin',
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($this->adminUser);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_rejects_invalid_password(): void
    {
        $response = $this->post(route('login'), [
            'username' => 'admin',
            'password' => 'WrongPassword!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_login_rejects_non_existent_username(): void
    {
        $response = $this->post(route('login'), [
            'username' => 'usuario.inexistente',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = User::factory()->gts()->create([
            'username' => 'inactivo.test',
            'password' => bcrypt('Password123!'),
            'is_active' => false,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'inactivo.test',
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    public function test_user_creation_with_username_and_no_email_succeeds(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'Técnico Especialista AIFA',
            'username' => 'tecnico.aifa',
            'email' => null,
            'password' => 'Segura12345!',
            'role_id' => $gtsRole->id,
            'badge_number' => 'GTS-2026',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Técnico Especialista AIFA',
            'username' => 'tecnico.aifa',
            'email' => null,
            'role_id' => $gtsRole->id,
            'badge_number' => 'GTS-2026',
            'is_active' => true,
        ]);

        // Puede autenticarse con sus credenciales de username
        $authResponse = $this->post(route('login'), [
            'username' => 'tecnico.aifa',
            'password' => 'Segura12345!',
        ]);
        $this->assertAuthenticated();
    }

    public function test_user_creation_rejects_duplicate_username(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'Duplicado Test',
            'username' => 'admin', // ya existe
            'email' => null,
            'password' => 'Segura12345!',
            'role_id' => $gtsRole->id,
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_profile_update_preserves_username_and_supports_nullable_email(): void
    {
        $response = $this->actingAs($this->gtsUser)->patch(route('profile.update'), [
            'name' => 'Ing. Carlos Morales Modificado',
            'username' => 'gts.morales',
            'email' => null,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $this->gtsUser->id,
            'name' => 'Ing. Carlos Morales Modificado',
            'username' => 'gts.morales',
            'email' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. DASHBOARD Y REDISEÑO DE GRÁFICOS (50% / 50%)
    |--------------------------------------------------------------------------
    */

    public function test_operational_health_matrix_is_completely_absent_from_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();

        $content = $response->getContent();

        // Verificamos que no exista ningún rastro de la matriz ni de sus subsistemas fijos
        $this->assertStringNotContainsString('Matriz de salud operacional', $content);
        $this->assertStringNotContainsString('matriz-salud', $content);
        $this->assertStringNotContainsString('healthMatrix', $content);

        // Verificamos que la fila de gráficos use la cuadrícula simétrica 50%/50%
        $this->assertStringContainsString('grid grid-cols-1 md:grid-cols-2 gap-3 h-[152px]', $content);
        $this->assertStringNotContainsString('md:grid-cols-3', $content);

        // Verificamos presencia de los dos gráficos
        $this->assertStringContainsString('Incidencias por tipo', $content);
        $this->assertStringContainsString('Incidencias por prioridad', $content);
    }

    public function test_dashboard_renders_dynamic_chart_data(): void
    {
        $category = Category::first();
        $priority = Priority::where('level', 4)->first() ?? Priority::first();
        $status = Status::where('is_final', false)->first() ?? Status::first();

        Incident::factory()->create([
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'location' => 'Sala de Última Espera',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();

        // Comprobamos que el servicio inyecte los arreglos correctos
        $response->assertViewHas('distribution');
        $response->assertViewHas('prioritiesSummary');
        $response->assertViewMissing('healthMatrix');
    }

    /*
    |--------------------------------------------------------------------------
    | 4. SISTEMA COMPLETO: INCIDENCIAS, INDICADORES, REPORTES, AUDITORÍA, ROLES
    |--------------------------------------------------------------------------
    */

    public function test_incident_lifecycle_and_status_transition_with_audit_trail(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $statusEnAtencion = Status::where('code', 'EN_ATENCION')->firstOrFail();
        $statusResuelta = Status::where('code', 'RESUELTA')->firstOrFail();

        // 1. Crear incidencia (Admin)
        $storeResponse = $this->actingAs($this->adminUser)->post(route('incidents.store'), [
            'title' => 'Falla crítica de cámaras en Filtro 1',
            'description' => 'Pérdida de señal en switch PoE del filtro 1 de inspección de pasajeros.',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'Filtro 1 de Inspección',
        ]);

        $storeResponse->assertRedirect();
        $incident = Incident::where('title', 'Falla crítica de cámaras en Filtro 1')->firstOrFail();
        $this->assertEquals('NUEVA', $incident->status->code);

        // 2. Asignar técnico GTS
        $assignResponse = $this->actingAs($this->adminUser)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => $this->gtsUser->id,
            'comment' => 'Asignado para atención técnica en sitio.',
        ]);
        $assignResponse->assertRedirect();
        $incident->refresh();
        $this->assertEquals($this->gtsUser->id, $incident->assigned_to_user_id);
        $this->assertEquals('ASIGNADA', $incident->status->code);

        // 3. Transición de estado a 'En Atención' (GTS)
        $transition1 = $this->actingAs($this->gtsUser)->post(route('incidents.start-attention', $incident), [
            'comment' => 'Técnico arriba al sitio y comienza diagnóstico de enlace.',
        ]);
        $transition1->assertRedirect();
        $this->assertEquals($statusEnAtencion->id, $incident->fresh()->status_id);

        // 4. Transición de estado a 'Resuelta' (GTS)
        $transition2 = $this->actingAs($this->gtsUser)->post(route('incidents.resolve', $incident), [
            'solution_description' => 'Reemplazo de patch cord y verificación de alimentación PoE satisfactoria en switch principal.',
        ]);
        $transition2->assertRedirect();
        $this->assertEquals($statusResuelta->id, $incident->fresh()->status_id);

        // 5. Bitácora de historial registrada
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'field_name' => 'status_id',
            'new_value' => 'Resuelta',
            'user_id' => $this->gtsUser->id,
        ]);
    }

    public function test_apoc_role_has_operational_read_access_but_forbidden_from_admin_modules(): void
    {
        // Puede ver Dashboard
        $dash = $this->actingAs($this->apocUser)->get(route('dashboard'));
        $dash->assertOk();

        // Puede ver Incidencias
        $incList = $this->actingAs($this->apocUser)->get(route('incidents.index'));
        $incList->assertOk();

        // Puede ver Indicadores
        $indicators = $this->actingAs($this->apocUser)->get(route('indicators.index'));
        $indicators->assertOk();

        // Puede ver Reportes
        $reports = $this->actingAs($this->apocUser)->get(route('reports.index'));
        $reports->assertOk();

        // DENEGADO en Auditoría (403)
        $audit = $this->actingAs($this->apocUser)->get(route('audit.index'));
        $audit->assertForbidden();

        // DENEGADO en Gestión de Usuarios (403)
        $users = $this->actingAs($this->apocUser)->get(route('users.index'));
        $users->assertForbidden();
    }

    public function test_gts_role_forbidden_from_admin_modules(): void
    {
        // GTS denegado en auditoría
        $audit = $this->actingAs($this->gtsUser)->get(route('audit.index'));
        $audit->assertForbidden();

        // GTS denegado en gestión de usuarios
        $users = $this->actingAs($this->gtsUser)->get(route('users.index'));
        $users->assertForbidden();
    }

    public function test_indicators_system_calculates_and_renders(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('indicators.index'));
        $response->assertOk();

        $response->assertSee('Indicadores de Rendimiento Tecnológico');
        $response->assertSee('MTTR (Resolución)');
        $response->assertSee('Tiempo objetivo estimado:');
    }

    public function test_reports_system_generates_views_pdf_and_whatsapp_summary(): void
    {
        // 1. Vista de Reportes
        $repResponse = $this->actingAs($this->adminUser)->get(route('reports.index'));
        $repResponse->assertOk();
        $repResponse->assertSee('Sistema Profesional de Reportes');

        // 2. Exportación / Vista previa PDF
        $pdfResponse = $this->actingAs($this->adminUser)->get(route('reports.pdf'));
        $pdfResponse->assertOk();

        // 3. Resumen WhatsApp vía POST JSON
        $waResponse = $this->actingAs($this->adminUser)->postJson(route('reports.whatsapp'), [
            'phone' => '+525512345678',
        ]);
        $waResponse->assertOk();
        $waData = $waResponse->json();
        $this->assertArrayHasKey('url', $waData);
        $this->assertStringContainsString('api.whatsapp.com', $waData['url']);
        $this->assertStringContainsString('AIFA', urldecode($waData['url']));
    }

    public function test_audit_log_captures_actions_and_admin_can_view_them(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $response->assertOk();

        $response->assertSee('Bitácora General de Auditoría');
        $response->assertSee('Eventos de Bitácora');
    }
}
