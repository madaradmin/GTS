<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Permission;
use App\Models\Priority;
use App\Models\Role;
use App\Models\RoleOperationalScope;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRealtimeAndOriginTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $gtsUser;
    protected User $ccoUser;
    protected User $csaUser;
    protected Category $category;
    protected Priority $priority;
    protected Status $statusNueva;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->ccoUser = User::factory()->apocCco()->create([
            'username' => 'cco.dashboard',
            'badge_number' => 'CCO-DASH',
        ]);
        $this->csaUser = User::factory()->apocCsa()->create([
            'username' => 'csa.dashboard',
            'badge_number' => 'CSA-DASH',
        ]);

        $this->category = Category::firstOrCreate(['slug' => 'cctv'], [
            'name' => 'Circuito Cerrado (CCTV)',
            'is_active' => true,
        ]);
        $this->priority = Priority::firstOrCreate(['slug' => 'alta'], [
            'name' => 'Alta',
            'level' => 2,
            'is_active' => true,
        ]);
        $this->statusNueva = Status::firstOrCreate(['code' => 'NUEVA'], [
            'name' => 'Nueva',
            'is_terminal' => false,
        ]);
    }

    public function test_origin_badge_accessor_identifies_fixed_system_roles(): void
    {
        // 1. Incidencia de CCO
        $incCco = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-TEST',
            'reported_by_user_id' => $this->ccoUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $badgeCco = $incCco->origin_badge;
        $this->assertNotNull($badgeCco);
        $this->assertEquals('CCO', $badgeCco['label']);
        $this->assertStringContainsString('APOC-CCO', $badgeCco['title']);
        $this->assertEquals('bg-amber-500/10', $badgeCco['bg']);

        // 2. Incidencia de CSA
        $incCsa = Incident::factory()->create([
            'folio' => 'INC-2026-CSA-TEST',
            'reported_by_user_id' => $this->csaUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $badgeCsa = $incCsa->origin_badge;
        $this->assertNotNull($badgeCsa);
        $this->assertEquals('CSA', $badgeCsa['label']);
        $this->assertStringContainsString('APOC-CSA', $badgeCsa['title']);
        $this->assertEquals('bg-orange-500/10', $badgeCsa['bg']);

        // 3. Incidencia de GTS
        $incGts = Incident::factory()->create([
            'folio' => 'INC-2026-GTS-TEST',
            'reported_by_user_id' => $this->gtsUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $badgeGts = $incGts->origin_badge;
        $this->assertNotNull($badgeGts);
        $this->assertEquals('GTS', $badgeGts['label']);
        $this->assertEquals('bg-cyan-500/10', $badgeGts['bg']);

        // 4. Incidencia de ADMIN
        $incAdmin = Incident::factory()->create([
            'folio' => 'INC-2026-ADM-TEST',
            'reported_by_user_id' => $this->adminUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $badgeAdmin = $incAdmin->origin_badge;
        $this->assertNotNull($badgeAdmin);
        $this->assertEquals('ADMIN', $badgeAdmin['label']);
        $this->assertEquals('bg-purple-500/10', $badgeAdmin['bg']);
    }

    public function test_origin_badge_accessor_dynamically_identifies_custom_and_future_roles(): void
    {
        // Crear un rol personalizado "Telemática y Redes" con paleta "blue"
        $customRole = Role::create([
            'name' => 'Telemática',
            'slug' => 'telematica',
            'description' => 'Área de Comunicaciones y Redes',
            'color' => 'blue',
            'badge_bg' => 'bg-blue-500/10',
            'badge_text' => 'text-blue-400',
            'badge_border' => 'border-blue-500/20',
            'is_protected' => false,
        ]);

        $customUser = User::factory()->create([
            'role_id' => $customRole->id,
            'username' => 'telematica.user',
            'email' => 'telematica@aifa.aero',
        ]);

        $incCustom = Incident::factory()->create([
            'folio' => 'INC-2026-TEL-001',
            'reported_by_user_id' => $customUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $badge = $incCustom->origin_badge;
        $this->assertNotNull($badge);
        $this->assertEquals('Telemática', $badge['label']);
        $this->assertEquals('Procedencia: Telemática', $badge['title']);
        $this->assertEquals('bg-blue-500/10', $badge['bg']);
        $this->assertEquals('text-blue-400', $badge['text']);
        $this->assertEquals('border-blue-500/20', $badge['border']);
    }

    public function test_dashboard_and_partial_render_origin_badge_for_custom_roles(): void
    {
        $customRole = Role::create([
            'name' => 'Mantenimiento',
            'slug' => 'mantenimiento',
            'description' => 'Área de Mantenimiento Integral',
            'color' => 'emerald',
            'badge_bg' => 'bg-emerald-500/10',
            'badge_text' => 'text-emerald-400',
            'badge_border' => 'border-emerald-500/20',
            'is_protected' => false,
        ]);

        $customUser = User::factory()->create([
            'role_id' => $customRole->id,
            'username' => 'mant.user',
            'email' => 'mant@aifa.aero',
        ]);

        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-MNT-999',
            'reported_by_user_id' => $customUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        // Verificación en el Dashboard principal
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('INC-2026-MNT-999');
        $response->assertSee('Mantenimiento');
        $response->assertSee('bg-emerald-500/10');

        // Verificación en la ruta del endpoint parcial en tiempo real
        $partialResponse = $this->actingAs($this->adminUser)->get(route('dashboard.recent-incidents'));
        $partialResponse->assertOk();
        $partialResponse->assertSee('INC-2026-MNT-999');
        $partialResponse->assertSee('Mantenimiento');
        $partialResponse->assertSee('bg-emerald-500/10');
    }

    public function test_realtime_endpoint_requires_auth_and_rejects_inactive_user(): void
    {
        // 1. Invitado no autenticado
        $guestResponse = $this->get(route('dashboard.recent-incidents'));
        $guestResponse->assertRedirect(route('login'));

        // 2. Usuario inactivo
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $inactiveResponse = $this->actingAs($inactiveUser)->get(route('dashboard.recent-incidents'));
        $inactiveResponse->assertForbidden();
    }

    public function test_realtime_endpoint_returns_json_when_requested(): void
    {
        Incident::factory()->create([
            'folio' => 'INC-2026-JSON-001',
            'reported_by_user_id' => $this->gtsUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('dashboard.recent-incidents'));

        $response->assertOk();
        $response->assertJsonStructure([
            'incidents' => [
                '*' => [
                    'id',
                    'folio',
                    'show_url',
                    'created_at_formatted',
                    'category_name',
                    'category_slug',
                    'status' => ['code', 'name'],
                    'priority' => ['slug', 'name'],
                    'origin_badge' => ['label', 'title', 'bg', 'text', 'border'],
                ],
            ],
            'timestamp',
        ]);

        $this->assertEquals('INC-2026-JSON-001', $response->json('incidents.0.folio'));
        $this->assertEquals('GTS', $response->json('incidents.0.origin_badge.label'));
    }

    public function test_realtime_endpoint_supports_etag_and_returns_304_when_unmodified(): void
    {
        Incident::factory()->create([
            'folio' => 'INC-2026-ETAG-001',
            'reported_by_user_id' => $this->gtsUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        // Primera petición: genera ETag
        $firstResponse = $this->actingAs($this->adminUser)->get(route('dashboard.recent-incidents'));
        $firstResponse->assertOk();
        $etag = $firstResponse->headers->get('ETag');
        $this->assertNotEmpty($etag);

        // Segunda petición: enviando If-None-Match debe retornar 304 Not Modified con contenido vacío
        $secondResponse = $this->actingAs($this->adminUser)
            ->withHeader('If-None-Match', $etag)
            ->get(route('dashboard.recent-incidents'));

        $secondResponse->assertStatus(304);
        $this->assertEmpty($secondResponse->getContent());

        // Si se crea una nueva incidencia, el ETag cambia y se retorna 200 con el nuevo contenido
        Incident::factory()->create([
            'folio' => 'INC-2026-ETAG-002',
            'reported_by_user_id' => $this->adminUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $thirdResponse = $this->actingAs($this->adminUser)
            ->withHeader('If-None-Match', $etag)
            ->get(route('dashboard.recent-incidents'));

        $thirdResponse->assertOk();
        $thirdResponse->assertSee('INC-2026-ETAG-002');
    }

    public function test_realtime_endpoint_strictly_respects_user_scopes_and_permissions(): void
    {
        // 1. Rol limitado que solo ve asignadas
        $restrictedRole = Role::create([
            'name' => 'Técnico Limitado',
            'slug' => 'tecnico-limitado',
            'description' => 'Solo ve asignadas',
            'color' => 'slate',
            'is_protected' => false,
        ]);
        $permViewAssigned = Permission::firstOrCreate(['slug' => 'incidents.view_assigned'], [
            'name' => 'Ver asignadas',
            'module' => 'incidents',
        ]);
        $restrictedRole->permissions()->sync([$permViewAssigned->id]);

        $restrictedUser = User::factory()->create([
            'role_id' => $restrictedRole->id,
            'username' => 'tecnico.limitado',
            'email' => 'limitado@aifa.aero',
        ]);

        // Incidencia no asignada a él
        Incident::factory()->create([
            'folio' => 'INC-AJENA-999',
            'reported_by_user_id' => $this->adminUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        // El usuario limitado consulta el endpoint en tiempo real: NO debe ver la incidencia ajena
        $response = $this->actingAs($restrictedUser)->get(route('dashboard.recent-incidents'));
        $response->assertOk();
        $response->assertDontSee('INC-AJENA-999');
        $response->assertSee('No hay incidencias registradas en el sistema');

        // Si se le asigna una incidencia directamente:
        Incident::factory()->create([
            'folio' => 'INC-PROPIA-111',
            'reported_by_user_id' => $this->adminUser->id,
            'assigned_to_user_id' => $restrictedUser->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $responseWithAssigned = $this->actingAs($restrictedUser)->get(route('dashboard.recent-incidents'));
        $responseWithAssigned->assertOk();
        $responseWithAssigned->assertSee('INC-PROPIA-111');
        $responseWithAssigned->assertDontSee('INC-AJENA-999');
    }
}
