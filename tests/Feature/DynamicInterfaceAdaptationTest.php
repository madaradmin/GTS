<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Permission;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DynamicInterfaceAdaptationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_with_audit_view_permission_sees_audit_in_sidebar_and_topbar(): void
    {
        $roleAuditor = Role::create([
            'name' => 'Auditor Externo',
            'slug' => 'auditor-ext',
            'badge_color' => 'purple',
        ]);
        $permAudit = Permission::where('slug', 'audit.view')->firstOrFail();
        $roleAuditor->permissions()->attach([$permAudit->id]);

        $auditorUser = User::create([
            'name' => 'Auditor Carlos',
            'username' => 'auditor.carlos',
            'email' => 'carlos.audit@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleAuditor->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($auditorUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Auditoría');
        $response->assertSee(route('audit.index'));

        // Usuario normal sin permiso de auditoría
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $resApoc = $this->actingAs($apocUser)->get(route('dashboard'));
        $resApoc->assertOk();
        $resApoc->assertDontSee(route('audit.index'));
    }

    public function test_user_with_roles_view_permission_sees_roles_link_in_sidebar_and_profile_tabs(): void
    {
        $roleOfficer = Role::create([
            'name' => 'Oficial de Seguridad',
            'slug' => 'sec-officer',
            'badge_color' => 'indigo',
        ]);
        $permRoles = Permission::where('slug', 'roles.view')->firstOrFail();
        $roleOfficer->permissions()->attach([$permRoles->id]);

        $officer = User::create([
            'name' => 'Oficial Fernando',
            'username' => 'sec.fernando',
            'email' => 'fernando@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleOfficer->id,
            'is_active' => true,
        ]);

        $resProfile = $this->actingAs($officer)->get(route('profile.edit'));
        $resProfile->assertOk();
        $resProfile->assertSee('Gestión de Roles y Permisos');
        $resProfile->assertSee(route('roles.index'));
    }

    public function test_user_with_incidents_view_all_sees_department_origin_badges_in_index(): void
    {
        $roleSupervisor = Role::create([
            'name' => 'Supervisor de Sala',
            'slug' => 'supervisor-sala',
            'badge_color' => 'blue',
        ]);
        $permViewAll = Permission::where('slug', 'incidents.view_all')->firstOrFail();
        $roleSupervisor->permissions()->attach([$permViewAll->id]);

        $supervisor = User::create([
            'name' => 'Supervisor Alberto',
            'username' => 'sup.alberto',
            'email' => 'alberto@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleSupervisor->id,
            'is_active' => true,
        ]);

        $ccoRole = Role::where('slug', 'apoc-cco')->first() ?? Role::create(['name' => 'APOC-CCO', 'slug' => 'apoc-cco', 'badge_color' => 'amber']);
        $ccoUser = User::create([
            'name' => 'Operador CCO Demo',
            'username' => 'cco.demo',
            'email' => 'cco.demo@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $ccoRole->id,
            'is_active' => true,
        ]);

        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::create([
            'folio' => 'INC-2026-9051',
            'title' => 'Incidencia de prueba visual CCO',
            'description' => 'Test de badges',
            'location' => 'Zona C',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $ccoUser->id,
            'reported_at' => now(),
        ]);

        $resIndex = $this->actingAs($supervisor)->get(route('incidents.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('INC-2026-9051');
        $resIndex->assertSee('CCO');
    }

    public function test_incident_show_renders_take_button_for_user_with_take_permission(): void
    {
        $roleTech = Role::create([
            'name' => 'Especialista Telemática',
            'slug' => 'esp-telematica',
            'badge_color' => 'cyan',
        ]);
        $permTake = Permission::where('slug', 'incidents.take')->firstOrFail();
        $permAssign = Permission::where('slug', 'incidents.assign')->firstOrFail();
        $permViewAssigned = Permission::where('slug', 'incidents.view_assigned')->firstOrFail();
        $roleTech->permissions()->attach([$permTake->id, $permAssign->id, $permViewAssigned->id]);

        $techUser = User::create([
            'name' => 'Ing. Telemática José',
            'username' => 'jose.tele',
            'email' => 'jose.tele@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleTech->id,
            'is_active' => true,
        ]);

        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::create([
            'folio' => 'INC-2026-9052',
            'title' => 'Incidencia sin asignar para toma técnica',
            'description' => 'Toma directa',
            'location' => 'Site Central',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $techUser->id,
            'assigned_to_user_id' => null,
            'reported_at' => now(),
        ]);

        $resShow = $this->actingAs($techUser)->get(route('incidents.show', $incident->folio));
        $resShow->assertOk();
        $resShow->assertSee('Tomar Incidencia');
    }

    public function test_users_index_renders_custom_role_with_configured_badge(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();

        $roleCustom = Role::create([
            'name' => 'Comunicaciones Radar',
            'slug' => 'comms-radar',
            'badge_color' => 'rose',
        ]);

        User::create([
            'name' => 'Operador Radar',
            'username' => 'radar.op',
            'email' => 'radar@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleCustom->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertOk();
        $response->assertSee('Comunicaciones Radar');
        $response->assertSee('text-rose-400');
    }
}
