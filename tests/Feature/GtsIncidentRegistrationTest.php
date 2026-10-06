<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtsIncidentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $gtsUser;

    protected User $apocUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
    }

    /**
     * 1. Verificar que el usuario GTS ve la opción "Registrar incidencia" en Sidebar y Dashboard.
     */
    public function test_gts_user_sees_register_incident_option_in_ui(): void
    {
        // 1. Sidebar y Dashboard
        $dashResponse = $this->actingAs($this->gtsUser)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Registrar incidencia');

        // 2. Lista de incidencias
        $indexResponse = $this->actingAs($this->gtsUser)->get(route('incidents.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Nueva Incidencia');
    }

    /**
     * 2. Verificar que el usuario GTS puede abrir el formulario de creación de incidencias.
     */
    public function test_gts_user_can_access_incident_create_form(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('incidents.create'));

        $response->assertOk();
        $response->assertSee('Nueva Incidencia');
        $response->assertSee('Canal GTS Activo');
        $response->assertSee('Crear Incidencia');
    }

    /**
     * 3. Verificar que el usuario GTS puede crear y despachar exitosamente una nueva incidencia.
     */
    public function test_gts_user_can_create_and_store_new_incident(): void
    {
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $response = $this->actingAs($this->gtsUser)->post(route('incidents.store'), [
            'title' => 'Cámara domo PTZ 04 sin señal de video en filtro alfa',
            'description' => 'Falla intermitente en enlace de fibra óptica del rack de telecomunicaciones sala B.',
            'location' => 'Edificio Terminal - Sala B',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ]);

        $response->assertRedirect();

        $incident = Incident::where('title', 'Cámara domo PTZ 04 sin señal de video en filtro alfa')->first();
        $this->assertNotNull($incident);
        $this->assertEquals($this->gtsUser->id, $incident->reported_by_user_id);
        $this->assertEquals('NUEVA', $incident->status->code);

        // Aparece en la lista general de incidencias
        $listResponse = $this->actingAs($this->gtsUser)->get(route('incidents.index'));
        $listResponse->assertOk();
        $listResponse->assertSee($incident->folio);

        // Aparece en el módulo Mis Incidencias del técnico GTS
        $myResponse = $this->actingAs($this->gtsUser)->get(route('incidents.my'));
        $myResponse->assertOk();
        $myResponse->assertSee($incident->folio);
    }

    /**
     * 4. Confirmar que el rol APOC continúa pudiendo crear incidencias sin alteraciones.
     */
    public function test_apoc_user_continues_to_be_able_to_create_incidents(): void
    {
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $response = $this->actingAs($this->apocUser)->post(route('incidents.store'), [
            'title' => 'Incidencia reportada por operador APOC torre de control',
            'description' => 'Pérdida de visualización en pantallas FIDS de mostradores de documentación 10 a 15.',
            'location' => 'Ambulatorio Salidas Nacionales',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ]);

        $response->assertRedirect();
        $incident = Incident::where('title', 'Incidencia reportada por operador APOC torre de control')->first();
        $this->assertNotNull($incident);
        $this->assertEquals($this->apocUser->id, $incident->reported_by_user_id);
    }

    /**
     * 5. Confirmar que el rol ADMINISTRADOR continúa pudiendo crear incidencias sin alteraciones.
     */
    public function test_admin_user_continues_to_be_able_to_create_incidents(): void
    {
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('incidents.store'), [
            'title' => 'Mantenimiento preventivo correctivo reportado por Jefatura',
            'description' => 'Revisión y sustitución de pararrayos y puesta a tierra en estación meteorológica.',
            'location' => 'Plataforma Comercial',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ]);

        $response->assertRedirect();
        $incident = Incident::where('title', 'Mantenimiento preventivo correctivo reportado por Jefatura')->first();
        $this->assertNotNull($incident);
        $this->assertEquals($this->adminUser->id, $incident->reported_by_user_id);
    }

    /**
     * 6. Confirmar que los demás permisos y restricciones de roles siguen intactos.
     */
    public function test_other_role_permissions_and_boundaries_remain_intact(): void
    {
        // 1. GTS sigue sin acceso a Auditoría (403)
        $this->actingAs($this->gtsUser)->get(route('audit.index'))->assertForbidden();

        // 2. GTS sigue sin acceso a Configuración de Usuarios (403)
        $this->actingAs($this->gtsUser)->get(route('users.index'))->assertForbidden();

        // 3. APOC sigue sin acceso a Resolver Incidencias (403)
        $inAttentionIncident = Incident::factory()->enAtencion($this->gtsUser)->create();
        $this->actingAs($this->apocUser)->post(route('incidents.resolve', $inAttentionIncident->folio), [
            'solution_description' => 'Intento ilegal de resolución APOC',
        ])->assertForbidden();

        // 4. APOC tiene acceso a Mis Incidencias (habilitado para APOC en FASE 3)
        $this->actingAs($this->apocUser)->get(route('incidents.my'))->assertOk();
    }
}
