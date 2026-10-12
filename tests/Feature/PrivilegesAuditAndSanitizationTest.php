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
use Tests\TestCase;

class PrivilegesAuditAndSanitizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Helper para crear un usuario con un rol personalizado y permisos específicos.
     */
    protected function createCustomUserWithPermissions(string $roleName, string $slug, array $permissions): User
    {
        $role = Role::create([
            'name' => $roleName,
            'slug' => $slug,
            'description' => 'Rol para pruebas de auditoría y depuración',
            'color' => 'blue',
            'is_protected' => false,
        ]);

        $permIds = Permission::whereIn('slug', $permissions)->pluck('id')->all();
        $role->permissions()->sync($permIds);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    /**
     * PRUEBA 1:
     * La opción «Consultar incidencias» en Acciones rápidas (Dashboard) debe ocultarse
     * cuando el usuario no tiene ningún permiso de consulta aplicable.
     */
    public function test_consultar_incidencias_quick_action_is_hidden_without_query_permissions(): void
    {
        // Usuario con permisos solo de creación de incidencias (sin permisos de visualización)
        $creatorOnly = $this->createCustomUserWithPermissions(
            'Operador Solo Creación',
            'creator-only',
            ['incidents.create']
        );

        $response = $this->actingAs($creatorOnly)->get(route('dashboard'));
        $response->assertOk();

        // Debe ver el botón de registrar incidencia
        $response->assertSee('Registrar incidencia');

        // NO debe ver el botón ni el enlace a Consultar incidencias
        $response->assertDontSee('Consultar incidencias');
        $response->assertDontSee(route('incidents.index'));
    }

    /**
     * PRUEBA 2:
     * La opción «Consultar incidencias» en Acciones rápidas (Dashboard) debe mostrarse
     * si el usuario tiene al menos un permiso de consulta (view_assigned, view_department o view_all).
     */
    public function test_consultar_incidencias_quick_action_is_visible_with_query_permission(): void
    {
        $viewerAssigned = $this->createCustomUserWithPermissions(
            'Lector Asignadas',
            'viewer-assigned',
            ['incidents.view_assigned']
        );

        $response = $this->actingAs($viewerAssigned)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Consultar incidencias');
        $response->assertSee(route('incidents.index'));
    }

    /**
     * PRUEBA 3:
     * Acceso directo a rutas de listado de incidencias (/Todas las incidencias, /Mis incidencias)
     * debe retornar 403 Forbidden cuando el usuario carece de permisos de consulta.
     */
    public function test_direct_access_to_incident_index_routes_is_forbidden_without_view_permissions(): void
    {
        $userWithoutView = $this->createCustomUserWithPermissions(
            'Usuario Sin Lectura',
            'no-view',
            ['incidents.create', 'reports.view']
        );

        $this->actingAs($userWithoutView)->get(route('incidents.index'))->assertForbidden();
        $this->actingAs($userWithoutView)->get(route('incidents.my'))->assertForbidden();
        $this->actingAs($userWithoutView)->get('/incidencias')->assertRedirect(route('incidents.index'));
        $this->actingAs($userWithoutView)->get('/incidencias/mis-incidencias')->assertRedirect(route('incidents.my'));
        $this->followingRedirects()->actingAs($userWithoutView)->get('/incidencias')->assertForbidden();
        $this->followingRedirects()->actingAs($userWithoutView)->get('/incidencias/mis-incidencias')->assertForbidden();
    }

    /**
     * PRUEBA 4:
     * CORRECCIÓN FASE 4 — El creador de una incidencia NO debe ver las incidencias que creó
     * si carece de permisos de visualización aplicables (scopeVisibleToUser retorna 0 registros).
     */
    public function test_incident_creator_cannot_view_own_incidents_without_view_permission(): void
    {
        $creatorWithoutView = $this->createCustomUserWithPermissions(
            'Creador Desprovisto',
            'creator-no-view',
            ['incidents.create']
        );

        // Crear una incidencia donde el usuario es el autor directo
        $incident = Incident::factory()->create([
            'folio' => 'ST-2026-9901',
            'reported_by_user_id' => $creatorWithoutView->id,
            'title' => 'Falla en Circuito CCTV Pasillo Central',
        ]);

        // 1. scopeVisibleToUser / forUserScope debe excluir la incidencia
        $scopedCount = Incident::forUserScope($creatorWithoutView)->count();
        $this->assertEquals(0, $scopedCount, 'El usuario sin permisos de visualización no debe ver sus propias incidencias.');

        // 2. Acceso por URL directa /incidencias/{id} debe retornar 403 Forbidden
        $response = $this->actingAs($creatorWithoutView)->get(route('incidents.show', $incident));
        $response->assertForbidden();
    }

    /**
     * PRUEBA 5:
     * Cuando el creador sí tiene un permiso legítimo (ej. view_department), sí puede consultar
     * su propia incidencia dentro de su ámbito y por URL directa.
     */
    public function test_incident_creator_can_view_own_incident_when_granted_view_department(): void
    {
        $creatorWithDept = $this->createCustomUserWithPermissions(
            'Creador Departamental',
            'creator-dept',
            ['incidents.create', 'incidents.view_department']
        );

        $incident = Incident::factory()->create([
            'folio' => 'ST-2026-9902',
            'reported_by_user_id' => $creatorWithDept->id,
            'title' => 'Incidencia Departamental Legítima',
        ]);

        // 1. forUserScope debe incluirla
        $scopedIncidents = Incident::forUserScope($creatorWithDept)->pluck('id')->all();
        $this->assertContains($incident->id, $scopedIncidents);

        // 2. Acceso directo por URL permitido
        $response = $this->actingAs($creatorWithDept)->get(route('incidents.show', $incident));
        $response->assertOk();
        $response->assertSee('Incidencia Departamental Legítima');
    }

    /**
     * PRUEBA 6:
     * Segregación estricta de view_assigned:
     * Un usuario con ÚNICAMENTE view_assigned sólo ve incidencias asignadas a él,
     * no incidencias no asignadas creadas por otros o por él mismo (a menos que se le asigne).
     */
    public function test_view_assigned_permission_strictly_limits_to_assigned_incidents(): void
    {
        $technicianAssignedOnly = $this->createCustomUserWithPermissions(
            'Técnico Asignadas',
            'tech-assigned-only',
            ['incidents.view_assigned']
        );

        $otherUser = User::factory()->create();

        // Incidencia 1: Asignada al técnico
        $assignedIncident = Incident::factory()->create([
            'folio' => 'ST-2026-9903',
            'reported_by_user_id' => $otherUser->id,
            'assigned_to_user_id' => $technicianAssignedOnly->id,
            'title' => 'Ticket Asignado al Técnico',
        ]);

        // Incidencia 2: Creada por otro, NO asignada a este técnico
        $unassignedIncident = Incident::factory()->create([
            'folio' => 'ST-2026-9904',
            'reported_by_user_id' => $otherUser->id,
            'assigned_to_user_id' => $otherUser->id,
            'title' => 'Ticket Ajeno No Asignado',
        ]);

        $visibleIds = Incident::forUserScope($technicianAssignedOnly)->pluck('id')->all();
        $this->assertContains($assignedIncident->id, $visibleIds);
        $this->assertNotContains($unassignedIncident->id, $visibleIds);

        // Acceso directo a asignada: 200 OK
        $this->actingAs($technicianAssignedOnly)->get(route('incidents.show', $assignedIncident))->assertOk();

        // Acceso directo a ajena: 403 Forbidden
        $this->actingAs($technicianAssignedOnly)->get(route('incidents.show', $unassignedIncident))->assertForbidden();
    }

    /**
     * PRUEBA 7:
     * Verificación de depuración en Base de Datos:
     * Los 7 privilegios inútiles o redundantes han sido eliminados de la base de datos y de la matriz.
     */
    public function test_deprecated_and_redundant_privileges_are_deleted_from_database(): void
    {
        $deletedSlugs = [
            'dashboard.technical',
            'dashboard.monitoring',
            'dashboard.executive',
            'audit.export',
            'users.manage_shifts',
            'incidents.edit',
            'incidents.export',
        ];

        $foundCount = Permission::whereIn('slug', $deletedSlugs)->count();
        $this->assertEquals(0, $foundCount, 'Los 7 privilegios depurados no deben existir en PostgreSQL.');

        // El catálogo debe contener exactamente 25 capacidades funcionales
        $totalPermissions = Permission::count();
        $this->assertEquals(25, $totalPermissions, 'El catálogo debe contener exactamente 25 capacidades reales.');
    }

    /**
     * PRUEBA 8:
     * ADMINISTRADOR conserva su bypass global total sobre todas las consultas,
     * detalles y acciones operativas.
     */
    public function test_admin_retains_total_unrestricted_bypass(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();

        $incident = Incident::factory()->create([
            'folio' => 'ST-2026-9905',
            'title' => 'Incidencia Supervisada por Administrador',
        ]);

        // Dashboard ve todas las acciones
        $resDash = $this->actingAs($admin)->get(route('dashboard'));
        $resDash->assertOk();
        $resDash->assertSee('Consultar incidencias');
        $resDash->assertSee('Registrar incidencia');

        // Índice de incidencias accesible
        $this->actingAs($admin)->get(route('incidents.index'))->assertOk();

        // Detalle de cualquier incidencia accesible
        $resShow = $this->actingAs($admin)->get(route('incidents.show', $incident));
        $resShow->assertOk();
        $resShow->assertSee('Incidencia Supervisada por Administrador');

        // scopeVisibleToUser devuelve todas las incidencias
        $allCount = Incident::count();
        $scopedCount = Incident::forUserScope($admin)->count();
        $this->assertEquals($allCount, $scopedCount);
    }
}

