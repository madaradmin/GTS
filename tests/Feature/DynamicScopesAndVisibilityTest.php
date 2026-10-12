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
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DynamicScopesAndVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_custom_role_with_view_operational_scope_sees_only_allowed_departments_and_own(): void
    {
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();

        // 1. Crear roles personalizados
        $roleTics = Role::create([
            'name' => 'TICS Operaciones',
            'slug' => 'tics-ops',
            'description' => 'Área técnica TICS',
            'badge_color' => 'indigo',
        ]);
        $roleMantenimiento = Role::create([
            'name' => 'Mantenimiento General',
            'slug' => 'mantenimiento',
            'description' => 'Mantenimiento AIFA',
            'badge_color' => 'amber',
        ]);

        $permViewDept = Permission::where('slug', 'incidents.view_department')->firstOrFail();
        $permCreate = Permission::where('slug', 'incidents.create')->firstOrFail();
        $roleTics->permissions()->attach([$permViewDept->id, $permCreate->id]);
        $roleMantenimiento->permissions()->attach([$permViewDept->id, $permCreate->id]);

        // 2. Configurar alcance de visibilidad: TICS puede ver Mantenimiento
        RoleOperationalScope::create([
            'source_role_id' => $roleTics->id,
            'target_role_id' => $roleMantenimiento->id,
            'scope_type' => 'view',
        ]);

        // 3. Crear usuarios
        $ticsUser = User::create([
            'name' => 'Ing. Tics',
            'username' => 'tics.user',
            'email' => 'tics@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleTics->id,
            'is_active' => true,
        ]);
        $mantUser = User::create([
            'name' => 'Técnico Mant',
            'username' => 'mant.user',
            'email' => 'mant@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleMantenimiento->id,
            'is_active' => true,
        ]);
        $ccoRole = Role::where('slug', 'apoc-cco')->first() ?? Role::create(['name' => 'APOC-CCO', 'slug' => 'apoc-cco', 'badge_color' => 'amber']);
        $ccoUser = User::create([
            'name' => 'Operador CCO',
            'username' => 'cco.test',
            'email' => 'cco.test@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $ccoRole->id,
            'is_active' => true,
        ]);

        // 4. Crear incidencias de los tres usuarios
        $incMant = Incident::create([
            'folio' => 'INC-2026-9001',
            'title' => 'Falla en bombas de agua',
            'description' => 'Revisión técnica de cuartos de máquinas',
            'location' => 'Edificio Terminal',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $mantUser->id,
            'reported_at' => now(),
        ]);

        $incTics = Incident::create([
            'folio' => 'INC-2026-9002',
            'title' => 'Switch rack principal caído',
            'description' => 'Sin enlace de fibra hacia torre',
            'location' => 'Site C2',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $ticsUser->id,
            'reported_at' => now(),
        ]);

        $incCco = Incident::create([
            'folio' => 'INC-2026-9003',
            'title' => 'Incidencia CCO plataforma',
            'description' => 'Retraso en puertas de abordaje',
            'location' => 'Puerta 12',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $ccoUser->id,
            'reported_at' => now(),
        ]);

        // 5. Verificar alcance de visibilidad desde query scope
        $visibleToTics = Incident::visibleToUser($ticsUser)->pluck('id')->all();

        $this->assertContains($incMant->id, $visibleToTics, 'TICS debe ver la incidencia de Mantenimiento por alcance operativo configurado');
        $this->assertContains($incTics->id, $visibleToTics, 'TICS debe ver su propia incidencia');
        $this->assertNotContains($incCco->id, $visibleToTics, 'TICS no debe ver incidencias de CCO al no tener alcance operativo hacia CCO');
    }

    public function test_custom_role_with_view_all_permission_sees_all_incidents(): void
    {
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();

        $roleAuditor = Role::create([
            'name' => 'Auditoría Especial',
            'slug' => 'auditoria-esp',
            'description' => 'Auditoría global',
            'badge_color' => 'purple',
        ]);
        $permViewAll = Permission::where('slug', 'incidents.view_all')->firstOrFail();
        $roleAuditor->permissions()->attach([$permViewAll->id]);

        $auditorUser = User::create([
            'name' => 'Lic. Auditor',
            'username' => 'auditor.esp',
            'email' => 'auditor@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleAuditor->id,
            'is_active' => true,
        ]);

        $ccoRole = Role::where('slug', 'apoc-cco')->first() ?? Role::create(['name' => 'APOC-CCO', 'slug' => 'apoc-cco', 'badge_color' => 'amber']);
        $csaRole = Role::where('slug', 'apoc-csa')->first() ?? Role::create(['name' => 'APOC-CSA', 'slug' => 'apoc-csa', 'badge_color' => 'cyan']);
        $ccoUser = User::create([
            'name' => 'Operador CCO 2',
            'username' => 'cco.test2',
            'email' => 'cco.test2@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $ccoRole->id,
            'is_active' => true,
        ]);
        $csaUser = User::create([
            'name' => 'Operador CSA 2',
            'username' => 'csa.test2',
            'email' => 'csa.test2@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $csaRole->id,
            'is_active' => true,
        ]);

        $inc1 = Incident::create([
            'folio' => 'INC-2026-9011',
            'title' => 'Incidencia CCO 1',
            'description' => 'Desc 1',
            'location' => 'Loc 1',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $ccoUser->id,
            'reported_at' => now(),
        ]);

        $inc2 = Incident::create([
            'folio' => 'INC-2026-9012',
            'title' => 'Incidencia CSA 2',
            'description' => 'Desc 2',
            'location' => 'Loc 2',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $csaUser->id,
            'reported_at' => now(),
        ]);

        $visibleToAuditor = Incident::visibleToUser($auditorUser)->pluck('id')->all();

        $this->assertContains($inc1->id, $visibleToAuditor);
        $this->assertContains($inc2->id, $visibleToAuditor);
    }

    public function test_custom_role_with_attend_scope_filters_attendable_incidents(): void
    {
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();

        $roleTech = Role::create([
            'name' => 'Técnico Especialista',
            'slug' => 'tech-esp',
            'description' => 'Atención técnica',
            'badge_color' => 'blue',
        ]);
        $roleTarget = Role::create([
            'name' => 'Área Externa',
            'slug' => 'area-ext',
            'description' => 'Área externa',
            'badge_color' => 'gray',
        ]);

        $permAttend = Permission::where('slug', 'incidents.attend')->firstOrFail();
        $roleTech->permissions()->attach([$permAttend->id]);

        RoleOperationalScope::create([
            'source_role_id' => $roleTech->id,
            'target_role_id' => $roleTarget->id,
            'scope_type' => 'attend',
        ]);

        $techUser = User::create([
            'name' => 'Técnico Juan',
            'username' => 'tech.juan',
            'email' => 'juan@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleTech->id,
            'is_active' => true,
        ]);
        $targetUser = User::create([
            'name' => 'Usuario Externo',
            'username' => 'user.ext',
            'email' => 'ext@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleTarget->id,
            'is_active' => true,
        ]);
        $ccoRole = Role::where('slug', 'apoc-cco')->first() ?? Role::create(['name' => 'APOC-CCO', 'slug' => 'apoc-cco', 'badge_color' => 'amber']);
        $ccoUser = User::create([
            'name' => 'Operador CCO 3',
            'username' => 'cco.test3',
            'email' => 'cco.test3@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $ccoRole->id,
            'is_active' => true,
        ]);

        $incTarget = Incident::create([
            'folio' => 'INC-2026-9021',
            'title' => 'Incidencia área externa',
            'description' => 'Requiere atención de especialista',
            'location' => 'Hangar 3',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $targetUser->id,
            'reported_at' => now(),
        ]);

        $incCco = Incident::create([
            'folio' => 'INC-2026-9022',
            'title' => 'Incidencia CCO no atendible por este técnico',
            'description' => 'No está en su alcance de atención',
            'location' => 'Sala A',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $ccoUser->id,
            'reported_at' => now(),
        ]);

        $attendable = Incident::attendableByUser($techUser)->pluck('id')->all();

        $this->assertContains($incTarget->id, $attendable);
        $this->assertNotContains($incCco->id, $attendable);
    }

    public function test_incident_service_available_technicians_and_assignment_validation(): void
    {
        $roleCustomTech = Role::create([
            'name' => 'Soporte Telemático',
            'slug' => 'soporte-tele',
            'description' => 'Técnicos telemáticos',
            'badge_color' => 'cyan',
        ]);
        $permAttend = Permission::where('slug', 'incidents.attend')->firstOrFail();
        $permAssign = Permission::where('slug', 'incidents.assign')->firstOrFail();
        $roleCustomTech->permissions()->attach([$permAttend->id, $permAssign->id]);

        $roleDispatcher = Role::create([
            'name' => 'Despachador Central',
            'slug' => 'despacho-central',
            'description' => 'Despacho central',
            'badge_color' => 'emerald',
        ]);
        $roleDispatcher->permissions()->attach([$permAssign->id]);

        // Despacho Central puede asignar hacia Soporte Telemático
        RoleOperationalScope::create([
            'source_role_id' => $roleDispatcher->id,
            'target_role_id' => $roleCustomTech->id,
            'scope_type' => 'assign',
        ]);

        $dispatcherUser = User::create([
            'name' => 'Despachador Pedro',
            'username' => 'despacho.pedro',
            'email' => 'pedro@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleDispatcher->id,
            'is_active' => true,
        ]);
        $techUser = User::create([
            'name' => 'Ing. Telemático',
            'username' => 'tele.luis',
            'email' => 'luis@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleCustomTech->id,
            'is_active' => true,
        ]);
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        /** @var IncidentService $service */
        $service = app(IncidentService::class);
        $availableForDispatcher = $service->getAvailableTechnicians($dispatcherUser);

        $this->assertTrue($availableForDispatcher->contains('id', $techUser->id));
        $this->assertFalse($availableForDispatcher->contains('id', $apocUser->id));

        // Asignación HTTP por el despachador
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::create([
            'folio' => 'INC-2026-9031',
            'title' => 'Incidencia para asignación dinámica',
            'description' => 'Asignar a soporte telemático',
            'location' => 'Pista 04L',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $dispatcherUser->id,
            'reported_at' => now(),
        ]);

        // 1. Asignar al técnico autorizado debe tener éxito
        $resValid = $this->actingAs($dispatcherUser)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $techUser->id,
            'comment' => 'Despachado a telemática',
        ]);
        $resValid->assertRedirect(route('incidents.show', $incident->folio));
        $resValid->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals($techUser->id, $incident->assigned_to_user_id);
        $this->assertEquals('ASIGNADA', $incident->status->code);

        // 2. Asignar a un usuario fuera del alcance asignable debe fallar la validación
        $resInvalid = $this->actingAs($dispatcherUser)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $apocUser->id,
            'comment' => 'Intento inválido',
        ]);
        $resInvalid->assertSessionHasErrors('assigned_to_user_id');
    }

    public function test_my_incidents_adapts_for_technical_vs_reporting_roles(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        // Rol Técnico
        $roleTech = Role::create([
            'name' => 'Técnico Especial',
            'slug' => 'tech-especial',
            'badge_color' => 'blue',
        ]);
        $permViewAssigned = Permission::where('slug', 'incidents.view_assigned')->firstOrFail();
        $permAttend = Permission::where('slug', 'incidents.attend')->firstOrFail();
        $roleTech->permissions()->attach([$permAttend->id, $permViewAssigned->id]);

        $techUser = User::create([
            'name' => 'Técnico Andrés',
            'username' => 'andres.tech',
            'email' => 'andres@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleTech->id,
            'is_active' => true,
        ]);

        // Rol Reportador
        $roleReporter = Role::create([
            'name' => 'Reportador Especial',
            'slug' => 'reporter-especial',
            'badge_color' => 'gray',
        ]);
        $permCreate = Permission::where('slug', 'incidents.create')->firstOrFail();
        $roleReporter->permissions()->attach([$permCreate->id, $permViewAssigned->id]);

        $reporterUser = User::create([
            'name' => 'Reportador Raúl',
            'username' => 'raul.rep',
            'email' => 'raul@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $roleReporter->id,
            'is_active' => true,
        ]);

        // Incidencia reportada por el reportero y asignada al técnico
        $inc = Incident::create([
            'folio' => 'INC-2026-9041',
            'title' => 'Incidencia conjunta de prueba',
            'description' => 'Test mis incidencias',
            'location' => 'Área técnica',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $reporterUser->id,
            'assigned_to_user_id' => $techUser->id,
            'reported_at' => now(),
        ]);

        // Consulta como técnico: debe verla en Mis incidencias
        $resTech = $this->actingAs($techUser)->get(route('incidents.my'));
        $resTech->assertOk();
        $resTech->assertViewHas('incidents', function ($incidents) use ($inc) {
            return $incidents->contains('id', $inc->id);
        });

        // Consulta como reportero: debe verla en Mis incidencias
        $resRep = $this->actingAs($reporterUser)->get(route('incidents.my'));
        $resRep->assertOk();
        $resRep->assertViewHas('incidents', function ($incidents) use ($inc) {
            return $incidents->contains('id', $inc->id);
        });
    }
}
