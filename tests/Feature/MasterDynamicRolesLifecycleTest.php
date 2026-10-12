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

class MasterDynamicRolesLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gtsCoordinator;

    protected User $apocCco;

    protected User $apocCsa;

    protected Category $category;

    protected Priority $priority;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->gtsCoordinator = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $ccoRole = Role::where('slug', 'apoc-cco')->first() ?? Role::create(['name' => 'APOC-CCO', 'slug' => 'apoc-cco', 'badge_color' => 'amber']);
        $this->apocCco = User::create([
            'name' => 'Operador CCO Central',
            'username' => 'cco.central',
            'email' => 'cco.central@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $ccoRole->id,
            'is_active' => true,
        ]);

        $csaRole = Role::where('slug', 'apoc-csa')->first() ?? Role::create(['name' => 'APOC-CSA', 'slug' => 'apoc-csa', 'badge_color' => 'orange']);
        $this->apocCsa = User::create([
            'name' => 'Operador CSA Torre',
            'username' => 'csa.torre',
            'email' => 'csa.torre@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $csaRole->id,
            'is_active' => true,
        ]);

        $this->category = Category::where('is_active', true)->firstOrFail();
        $this->priority = Priority::where('is_active', true)->firstOrFail();
    }

    /**
     * Prueba E2E del ciclo de vida operacional con un rol creado dinámicamente en tiempo de ejecución.
     */
    public function test_full_lifecycle_with_dynamically_created_role(): void
    {
        // 1. Administrador crea el nuevo rol "TICS Operaciones" vía HTTP
        $resStoreRole = $this->actingAs($this->admin)->post(route('roles.store'), [
            'name' => 'TICS Operaciones',
            'slug' => 'tics-ops',
            'description' => 'Área de soporte tecnológico de comunicaciones',
            'color' => 'blue',
        ]);
        $resStoreRole->assertRedirect();
        $resStoreRole->assertSessionHas('status_message');

        $ticsRole = Role::where('slug', 'tics-ops')->firstOrFail();
        $this->assertEquals('TICS Operaciones', $ticsRole->name);

        // 2. Administrador asigna permisos granulares al rol TICS
        $permSlugs = [
            'incidents.create',
            'incidents.view_department',
            'incidents.view_assigned',
            'incidents.attend',
            'incidents.take',
            'incidents.assign',
            'incidents.resolve',
            'reports.export',
            'reports.view',
        ];
        $permIds = Permission::whereIn('slug', $permSlugs)->pluck('id')->all();

        $resPerms = $this->actingAs($this->admin)->put(route('roles.update-permissions', $ticsRole), [
            'permissions' => $permIds,
        ]);
        $resPerms->assertRedirect();
        $resPerms->assertSessionHas('status_message');
        $this->assertTrue($ticsRole->fresh()->hasPermission('incidents.attend'));

        // 3. Administrador configura los alcances operativos del rol TICS:
        // - View: CCO y TICS
        // - Attend: CCO y TICS
        // - Assign: TICS
        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();

        $resScopes = $this->actingAs($this->admin)->put(route('roles.update-scopes', $ticsRole), [
            'view_scopes' => [$ccoRole->id, $ticsRole->id],
            'attend_scopes' => [$ccoRole->id, $ticsRole->id],
            'assign_scopes' => [$ticsRole->id],
        ]);
        $resScopes->assertRedirect();
        $resScopes->assertSessionHas('status_message');

        $this->assertTrue($ticsRole->fresh()->canViewRole($ccoRole->id));
        $this->assertTrue($ticsRole->fresh()->canAttendRole($ccoRole->id));
        $this->assertTrue($ticsRole->fresh()->canAssignToRole($ticsRole->id));

        // 4. Administrador registra un usuario con este nuevo rol
        $resUser = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Ing. Roberto Tics',
            'username' => 'roberto.tics',
            'email' => 'roberto.tics@aifa.aero',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'badge_number' => 'TICS-001',
            'role_id' => $ticsRole->id,
            'is_active' => true,
        ]);
        $resUser->assertRedirect(route('users.index'));
        $resUser->assertSessionHas('status_message');

        $ticsUser = User::where('username', 'roberto.tics')->firstOrFail();

        // 5. El nuevo usuario TICS inicia sesión y accede al sistema
        $resDash = $this->actingAs($ticsUser)->get(route('dashboard'));
        $resDash->assertOk();
        $resDash->assertSee('Bienvenido, Ing. Roberto Tics');

        // 6. CCO crea una nueva incidencia
        $resCcoCreate = $this->actingAs($this->apocCco)->post(route('incidents.store'), [
            'title' => 'Caída de switch en sala de embarque CCO',
            'description' => 'Dispositivo no responde a ping en VLAN 40',
            'location' => 'Sala 5 CCO',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
        ]);
        $resCcoCreate->assertRedirect();

        $incident = Incident::where('title', 'Caída de switch en sala de embarque CCO')->firstOrFail();

        // 7. El usuario TICS consulta el expediente y puede verlo gracias al alcance 'view'
        $resTicsView = $this->actingAs($ticsUser)->get(route('incidents.show', $incident->folio));
        $resTicsView->assertOk();
        $resTicsView->assertSee($incident->folio);

        // 8. El usuario TICS toma la incidencia directamente (auto-asignación)
        $resTake = $this->actingAs($ticsUser)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $ticsUser->id,
            'comment' => 'Tomado por personal de guardia TICS.',
        ]);
        $resTake->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertEquals($ticsUser->id, $incident->assigned_to_user_id);

        // 9. El usuario TICS inicia la atención técnica
        $resStart = $this->actingAs($ticsUser)->post("/incidencias/{$incident->folio}/iniciar-atencion", [
            'comment' => 'Iniciando diagnóstico en rack de comunicaciones.',
        ]);
        $resStart->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);

        // 10. El usuario TICS agrega una nota técnica de avance
        $resNote = $this->actingAs($ticsUser)->post(route('incidents.add-note', $incident), [
            'note' => 'Se reinició el transceiver de fibra óptica. Enlace restablecido.',
        ]);
        $resNote->assertRedirect(route('incidents.show', $incident->folio));

        // 11. El usuario TICS marca la incidencia como RESUELTA
        $resResolve = $this->actingAs($ticsUser)->post("/incidencias/{$incident->folio}/resolver", [
            'solution_description' => 'Transceiver reemplazado y tráfico confirmado a 10Gbps.',
        ]);
        $resResolve->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);

        // 12. El operador CCO otorga el Visto Bueno operacional y cierra definitivamente
        $resClose = $this->actingAs($this->apocCco)->post("/incidencias/{$incident->folio}/cerrar", [
            'comment' => 'Conformidad con la conectividad en sala.',
        ]);
        $resClose->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);
        $this->assertNotNull($incident->closed_at);

        // 13. Verificación de bitácora de trazabilidad pericial (RF-08 / RN-04)
        $histories = $incident->histories()->with('user.role')->get();

        // Debe registrar la creación
        $creationLog = $histories->where('action', 'CREADA')->first();
        $this->assertNotNull($creationLog);
        $this->assertEquals($this->apocCco->id, $creationLog->user_id);

        // Debe registrar la asignación a Roberto Tics
        $assignLog = $histories->where('action', 'ASIGNADA')->first();
        $this->assertNotNull($assignLog);
        $this->assertEquals($ticsUser->name, $assignLog->new_value);

        // Debe registrar el cierre con VoBo otorgado por el operador CCO
        $closeLog = $histories->where('action', 'CERRADA')->first();
        $this->assertNotNull($closeLog);
        $this->assertEquals($this->apocCco->id, $closeLog->user_id);
    }

    /**
     * Prueba de restricción y aislamiento para un rol que no tiene alcances operativos hacia otros roles.
     */
    public function test_isolated_role_cannot_view_or_manipulate_other_departments_incidents(): void
    {
        // Crear rol restringido sin alcances hacia CCO ni CSA
        $restrictedRole = Role::create([
            'name' => 'Reporte Externo',
            'slug' => 'reporte-ext',
            'badge_color' => 'slate',
        ]);
        $permCreate = Permission::where('slug', 'incidents.create')->firstOrFail();
        $permViewAssigned = Permission::where('slug', 'incidents.view_assigned')->firstOrFail();
        $restrictedRole->permissions()->attach([$permCreate->id, $permViewAssigned->id]);

        $restrictedUser = User::create([
            'name' => 'Usuario Reporte Externo',
            'username' => 'externo.rep',
            'email' => 'externo@aifa.aero',
            'password' => Hash::make('Password123!'),
            'role_id' => $restrictedRole->id,
            'is_active' => true,
        ]);

        // CCO crea una incidencia interna
        $incCco = Incident::create([
            'folio' => 'INC-2026-CCO-PRIVATE',
            'title' => 'Incidencia privada CCO',
            'description' => 'Exclusiva CCO',
            'location' => 'Sala CCO',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => Status::where('code', 'NUEVA')->value('id'),
            'reported_by_user_id' => $this->apocCco->id,
            'reported_at' => now(),
        ]);

        // El usuario restringido NO debe poder verla (403 Forbidden por Policy)
        $resForbidden = $this->actingAs($restrictedUser)->get(route('incidents.show', $incCco->folio));
        $resForbidden->assertForbidden();

        // En el listado /incidencias no debe aparecer
        $resList = $this->actingAs($restrictedUser)->get(route('incidents.index'));
        $resList->assertOk();
        $resList->assertDontSee($incCco->folio);

        // Tampoco puede intentar asignarla
        $resAssign = $this->actingAs($restrictedUser)->post("/incidencias/{$incCco->folio}/asignar", [
            'assigned_to_user_id' => $restrictedUser->id,
        ]);
        $resAssign->assertForbidden();
    }

    /**
     * Prueba de conservación íntegra de la matriz de roles y capacidades nativas.
     */
    public function test_native_roles_preserve_all_operational_capabilities(): void
    {
        // 1. Administrador puede ver cualquier incidencia y gestionar roles
        $incTest = Incident::create([
            'folio' => 'INC-2026-MATRIX-01',
            'title' => 'Incidencia para auditoría de matriz',
            'description' => 'Test matriz',
            'location' => 'Plataforma',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => Status::where('code', 'NUEVA')->value('id'),
            'reported_by_user_id' => $this->apocCsa->id,
            'reported_at' => now(),
        ]);

        $resAdmin = $this->actingAs($this->admin)->get(route('incidents.show', $incTest->folio));
        $resAdmin->assertOk();

        // 2. GTS puede atender la incidencia reportada por CSA
        $resGts = $this->actingAs($this->gtsCoordinator)->get(route('incidents.show', $incTest->folio));
        $resGts->assertOk();

        $techGts = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();
        $resGtsAssign = $this->actingAs($this->gtsCoordinator)->post("/incidencias/{$incTest->folio}/asignar", [
            'assigned_to_user_id' => $techGts->id,
            'comment' => 'Asignado a Mendoza por coordinación GTS.',
        ]);
        $resGtsAssign->assertRedirect();
        $incTest->refresh();
        $this->assertEquals('ASIGNADA', $incTest->status->code);
        $this->assertEquals($techGts->id, $incTest->assigned_to_user_id);

        // 3. APOC-CCO no puede ver la incidencia creada por APOC-CSA (aislamiento estricto)
        $resCcoIsolation = $this->actingAs($this->apocCco)->get(route('incidents.show', $incTest->folio));
        $resCcoIsolation->assertForbidden();

        // 4. APOC-CSA sí puede ver su propia incidencia
        $resCsaAccess = $this->actingAs($this->apocCsa)->get(route('incidents.show', $incTest->folio));
        $resCsaAccess->assertOk();
    }
}
