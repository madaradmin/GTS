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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PermissionMatrixSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Helper to create a custom user with a clean custom role.
     */
    protected function createCustomUser(string $roleName, string $roleSlug, array $permissionSlugs = []): array
    {
        $role = Role::create([
            'name' => $roleName,
            'slug' => $roleSlug,
            'badge_color' => 'cyan',
            'is_protected' => false,
        ]);

        if (! empty($permissionSlugs)) {
            $permIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id')->all();
            $role->permissions()->attach($permIds);
        }

        $user = User::create([
            'name' => "User {$roleName}",
            'username' => "user.{$roleSlug}",
            'email' => "{$roleSlug}@aifa.aero",
            'password' => Hash::make('Password123!'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        return [$user, $role];
    }

    /**
     * Test 1: reports.view synchronization
     */
    public function test_reports_view_permission_controls_sidebar_and_access(): void
    {
        [$userWithReports, $role] = $this->createCustomUser('Operador Reportes', 'op-rep', ['reports.view']);
        [$userWithoutReports] = $this->createCustomUser('Operador Sin Reportes', 'op-sin-rep', []);

        // 1. User with reports.view sees report link and can access /reportes
        $resWith = $this->actingAs($userWithReports)->get(route('dashboard'));
        $resWith->assertOk();
        $resWith->assertSee(route('reports.index'));
        $resWith->assertSee('Descargar reporte');

        $resReportPage = $this->actingAs($userWithReports)->get(route('reports.index'));
        $resReportPage->assertOk();

        // 2. User without reports.view does NOT see report link and gets 403 on /reportes
        $resWithout = $this->actingAs($userWithoutReports)->get(route('dashboard'));
        $resWithout->assertOk();
        $resWithout->assertDontSee(route('reports.index'));

        $resDenied = $this->actingAs($userWithoutReports)->get(route('reports.index'));
        $resDenied->assertForbidden();
    }

    /**
     * Test 2: reports.export synchronization
     */
    public function test_reports_export_permission_controls_export_buttons_and_pdf_endpoint(): void
    {
        [$userViewOnly] = $this->createCustomUser('Viewer Reportes', 'view-rep', ['reports.view']);
        [$userExporter] = $this->createCustomUser('Exporter Reportes', 'exp-rep', ['reports.view', 'reports.export']);

        // User with only reports.view sees index but NOT export buttons
        $resView = $this->actingAs($userViewOnly)->get(route('reports.index'));
        $resView->assertOk();
        $resView->assertDontSee('Descargar PDF Institucional');
        $resView->assertDontSee(route('reports.pdf'));

        // Accessing PDF endpoint directly gives 403
        $this->actingAs($userViewOnly)->get(route('reports.pdf'))->assertForbidden();

        // User with reports.export sees export buttons and can access PDF endpoint
        $resExport = $this->actingAs($userExporter)->get(route('reports.index'));
        $resExport->assertOk();
        $resExport->assertSee('Descargar PDF Institucional');
        $resExport->assertSee(route('reports.pdf'));

        $this->actingAs($userExporter)->get(route('reports.pdf'))->assertOk();
    }

    /**
     * Test 3: incidents.create synchronization
     */
    public function test_incident_create_permission_controls_ui_and_store_endpoint(): void
    {
        [$userCreator] = $this->createCustomUser('Creador Incidencias', 'creator', ['incidents.create', 'incidents.view_assigned']);
        [$userReader] = $this->createCustomUser('Lector Incidencias', 'reader', ['incidents.view_assigned']);

        // Creator sees create button in dashboard
        $resCreatorDash = $this->actingAs($userCreator)->get(route('dashboard'));
        $resCreatorDash->assertOk();
        $resCreatorDash->assertSee('Registrar incidencia');
        $resCreatorDash->assertSee(route('incidents.create'));

        // Reader does not see create button
        $resReaderDash = $this->actingAs($userReader)->get(route('dashboard'));
        $resReaderDash->assertOk();
        $resReaderDash->assertDontSee('Registrar incidencia');

        // Backend route access
        $this->actingAs($userCreator)->get(route('incidents.create'))->assertOk();
        $this->actingAs($userReader)->get(route('incidents.create'))->assertForbidden();

        // Attempting POST store without permission returns 403
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();

        $postData = [
            'title' => 'Incidencia no autorizada',
            'description' => 'Prueba de seguridad sin permiso',
            'location' => 'Zona A',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
        ];

        $this->actingAs($userReader)->post(route('incidents.store'), $postData)->assertForbidden();
    }

    /**
     * Test 4: incidents.resolve synchronization
     */
    public function test_incident_resolve_permission_controls_resolve_button_and_endpoint(): void
    {
        [$techResolver, $roleResolver] = $this->createCustomUser('Tecnico Resolver', 'tech-res', [
            'incidents.view_all',
            'incidents.resolve',
            'incidents.start_attention',
        ]);
        [$techNoResolve, $roleNoResolve] = $this->createCustomUser('Tecnico Sin Resolver', 'tech-no-res', [
            'incidents.view_all',
            'incidents.start_attention',
        ]);

        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $statusAtencion = Status::where('code', 'EN_ATENCION')->firstOrFail();

        // Incidencia reportada por el técnico
        $incident = Incident::create([
            'folio' => 'INC-2026-9901',
            'title' => 'Falla Switch Core 1',
            'description' => 'Prueba resolución',
            'location' => 'Site 1',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusAtencion->id,
            'reported_by_user_id' => $techResolver->id,
            'assigned_to_user_id' => $techResolver->id,
            'reported_at' => now(),
        ]);

        // Configurar alcance de atención hacia su propio rol
        RoleOperationalScope::create([
            'source_role_id' => $roleResolver->id,
            'target_role_id' => $roleResolver->id,
            'scope_type' => 'attend',
        ]);
        RoleOperationalScope::create([
            'source_role_id' => $roleResolver->id,
            'target_role_id' => $roleResolver->id,
            'scope_type' => 'view',
        ]);

        // Tech with incidents.resolve sees "Marcar como Resuelta"
        $resShow = $this->actingAs($techResolver)->get(route('incidents.show', $incident->folio));
        $resShow->assertOk();
        $resShow->assertSee('Marcar como Resuelta');

        // Tech without incidents.resolve does NOT see button and is blocked at endpoint
        $incident->assigned_to_user_id = $techNoResolve->id;
        $incident->reported_by_user_id = $techNoResolve->id;
        $incident->save();

        RoleOperationalScope::create([
            'source_role_id' => $roleNoResolve->id,
            'target_role_id' => $roleNoResolve->id,
            'scope_type' => 'attend',
        ]);
        RoleOperationalScope::create([
            'source_role_id' => $roleNoResolve->id,
            'target_role_id' => $roleNoResolve->id,
            'scope_type' => 'view',
        ]);

        $resShowDenied = $this->actingAs($techNoResolve)->get(route('incidents.show', $incident->folio));
        $resShowDenied->assertOk();
        $resShowDenied->assertDontSee('Marcar como Resuelta');

        $this->actingAs($techNoResolve)->post(route('incidents.resolve', $incident), [
            'solution' => 'Solución aplicada',
        ])->assertForbidden();
    }

    /**
     * Test 5: Dynamic synchronization for default GTS role when modifying matrix
     */
    public function test_dynamic_permission_revocation_works_for_default_gts_role(): void
    {
        $gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $gtsRole = $gtsUser->role;

        // Verify initial state: GTS has reports.view
        $permReports = Permission::where('slug', 'reports.view')->firstOrFail();
        $this->assertTrue($gtsRole->permissions->contains($permReports->id));

        // Revoke reports.view from GTS role in the database matrix
        $gtsRole->permissions()->detach([$permReports->id]);
        $gtsUser->unsetRelation('role');

        // GTS sidebar and dashboard must NOT display report link anymore
        $resGtsDash = $this->actingAs($gtsUser)->get(route('dashboard'));
        $resGtsDash->assertOk();
        $resGtsDash->assertDontSee(route('reports.index'));

        // GTS direct access to /reportes is rejected with 403
        $this->actingAs($gtsUser)->get(route('reports.index'))->assertForbidden();

        // Re-grant reports.view
        $gtsRole->permissions()->attach([$permReports->id]);
        $gtsUser->unsetRelation('role');

        // GTS can access reports again
        $this->actingAs($gtsUser)->get(route('reports.index'))->assertOk();
    }

    /**
     * Test 6: Audit view and incident delete synchronization
     */
    public function test_audit_view_and_incident_delete_synchronization(): void
    {
        [$auditorWithoutDelete] = $this->createCustomUser('Auditor Solo Lectura', 'audit-ro', ['audit.view']);
        [$auditorWithDelete] = $this->createCustomUser('Auditor Con Borrado', 'audit-rw', ['audit.view', 'incidents.delete']);

        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::create([
            'folio' => 'INC-2026-9902',
            'title' => 'Incidencia para prueba de borrado audit',
            'description' => 'Test delete audit',
            'location' => 'Site 2',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $auditorWithoutDelete->id,
            'reported_at' => now(),
        ]);

        // Auditor without incidents.delete can access audit index but does NOT see Eliminar button
        $resAuditorRO = $this->actingAs($auditorWithoutDelete)->get(route('audit.index'));
        $resAuditorRO->assertOk();
        $resAuditorRO->assertDontSee('Eliminar definitivamente expediente INC-2026-9902');

        // Direct delete endpoint is blocked with 403
        $this->actingAs($auditorWithoutDelete)->delete(route('audit.incidents.destroy', $incident->folio))->assertForbidden();

        // Auditor with incidents.delete sees Eliminar button and can delete
        $resAuditorRW = $this->actingAs($auditorWithDelete)->get(route('audit.index'));
        $resAuditorRW->assertOk();
        $resAuditorRW->assertSee('Eliminar definitivamente expediente INC-2026-9902');

        $this->actingAs($auditorWithDelete)->delete(route('audit.incidents.destroy', $incident->folio))->assertRedirect(route('audit.index'));
        $this->assertDatabaseMissing('incidents', ['folio' => 'INC-2026-9902']);
    }

    /**
     * Test 7: User management permissions synchronization
     */
    public function test_user_management_granular_permissions_synchronization(): void
    {
        [$userAdminRO] = $this->createCustomUser('Gestor Usuarios Lectura', 'user-ro', ['users.view']);
        [$userAdminFull] = $this->createCustomUser('Gestor Usuarios Full', 'user-full', ['users.view', 'users.create', 'users.edit', 'users.delete']);

        // User with users.view only can access index, but does NOT see "Registrar Usuario", Edit, or Delete
        $resRO = $this->actingAs($userAdminRO)->get(route('users.index'));
        $resRO->assertOk();
        $resRO->assertDontSee('Registrar Usuario');

        // Cannot create user via POST
        $this->actingAs($userAdminRO)->post(route('users.store'), [
            'name' => 'Nuevo Intento',
            'username' => 'nuevo.intento',
            'password' => 'Password123!',
            'role_id' => $userAdminRO->role_id,
        ])->assertForbidden();

        // User with full permissions sees "Registrar Usuario" and can create
        $resFull = $this->actingAs($userAdminFull)->get(route('users.index'));
        $resFull->assertOk();
        $resFull->assertSee('Registrar Usuario');

        $this->actingAs($userAdminFull)->post(route('users.store'), [
            'name' => 'Usuario Creado OK',
            'username' => 'creado.ok',
            'password' => 'Password123!',
            'role_id' => $userAdminFull->role_id,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['username' => 'creado.ok']);
    }

    /**
     * Test 8: Admin retains global bypass across all endpoints
     */
    public function test_admin_retains_unrestricted_global_bypass(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();

        // Admin can access reports, audit, users, roles without explicit permissions attached
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('reports.pdf'))->assertOk();
        $this->actingAs($admin)->get(route('audit.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('roles.index'))->assertOk();

        // Admin sees all sidebar links
        $resDash = $this->actingAs($admin)->get(route('dashboard'));
        $resDash->assertOk();
        $resDash->assertSee('Descargar reporte');
        $resDash->assertSee('Auditoría');
        $resDash->assertSee('Roles y Permisos');
    }
}
