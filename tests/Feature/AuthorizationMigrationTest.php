<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Role;
use App\Models\RoleOperationalScope;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\FolioSequenceSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PrioritySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(PrioritySeeder::class);
        $this->seed(StatusSeeder::class);
        $this->seed(FolioSequenceSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_custom_role_with_incident_create_permission_can_create_incidents(): void
    {
        $customRole = Role::create([
            'name' => 'Supervisor Externo',
            'slug' => 'supervisor-externo',
            'color' => 'blue',
            'is_protected' => false,
        ]);
        $customRole->givePermission('incidents.create');

        $user = User::factory()->create(['role_id' => $customRole->id]);

        $category = Category::first();
        $priority = Priority::first();

        $response = $this->actingAs($user)->post(route('incidents.store'), [
            'title' => 'Incidencia desde Rol Personalizado',
            'description' => 'Falla de prueba para verificar autorización dinámica por permisos.',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'Edificio Terminal Piso 2',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('incidents', [
            'title' => 'Incidencia desde Rol Personalizado',
            'reported_by_user_id' => $user->id,
        ]);
    }

    public function test_custom_role_without_incident_create_permission_is_forbidden(): void
    {
        $customRole = Role::create([
            'name' => 'Consultor Solo Lectura',
            'slug' => 'consultor-lectura',
            'color' => 'slate',
            'is_protected' => false,
        ]);
        // Sin permiso incidents.create

        $user = User::factory()->create(['role_id' => $customRole->id]);
        $category = Category::first();
        $priority = Priority::first();

        $response = $this->actingAs($user)->post(route('incidents.store'), [
            'title' => 'Intento No Autorizado',
            'description' => 'Esta acción debe fallar con 403.',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'Edificio Terminal Piso 1',
        ]);

        $response->assertForbidden();
    }

    public function test_custom_role_with_attend_permission_and_scope_can_start_attention(): void
    {
        $technicianRole = Role::create([
            'name' => 'Técnico Especializado',
            'slug' => 'tecnico-especializado',
            'color' => 'cyan',
            'is_protected' => false,
        ]);
        $technicianRole->givePermission('incidents.attend');
        $technicianRole->givePermission('incidents.view_all');

        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();

        // Otorgar alcance para atender incidencias de CCO
        RoleOperationalScope::create([
            'source_role_id' => $technicianRole->id,
            'target_role_id' => $ccoRole->id,
            'scope_type' => 'attend',
        ]);

        $techUser = User::factory()->create(['role_id' => $technicianRole->id]);
        $ccoUser = User::factory()->apocCco()->create();

        $category = Category::first();
        $priority = Priority::first();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::factory()->create([
            'reported_by_user_id' => $ccoUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        $response = $this->actingAs($techUser)->post(route('incidents.start-attention', $incident));
        $response->assertRedirect();
        $this->assertEquals('EN_ATENCION', $incident->fresh()->status->code);
    }

    public function test_custom_role_cannot_attend_incident_outside_its_operational_scope(): void
    {
        $techRole = Role::create([
            'name' => 'Técnico Restringido',
            'slug' => 'tecnico-restringido',
            'color' => 'slate',
            'is_protected' => false,
        ]);
        $techRole->givePermission('incidents.attend');
        // NO tiene alcance attend sobre CCO

        $techUser = User::factory()->create(['role_id' => $techRole->id]);
        $ccoUser = User::factory()->apocCco()->create();

        $category = Category::first();
        $priority = Priority::first();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::factory()->create([
            'reported_by_user_id' => $ccoUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        $response = $this->actingAs($techUser)->post(route('incidents.start-attention', $incident));
        $response->assertForbidden();
    }

    public function test_custom_role_with_vobo_close_permission_can_close_incident(): void
    {
        $auditorRole = Role::create([
            'name' => 'Auditor de Calidad',
            'slug' => 'auditor-calidad',
            'color' => 'amber',
            'is_protected' => false,
        ]);
        $auditorRole->givePermission('incidents.vobo_close');
        $auditorRole->givePermission('incidents.view_all');

        $auditorUser = User::factory()->create(['role_id' => $auditorRole->id]);

        $statusResuelta = Status::where('code', 'RESUELTA')->firstOrFail();
        $category = Category::first();
        $priority = Priority::first();

        $incident = Incident::factory()->create([
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusResuelta->id,
        ]);

        $response = $this->actingAs($auditorUser)->post(route('incidents.close', $incident), [
            'comment' => 'Aprobado y cerrado por auditor de calidad.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('CERRADA', $incident->fresh()->status->code);
    }

    public function test_user_with_audit_view_permission_can_access_audit_log(): void
    {
        $secRole = Role::create([
            'name' => 'Oficial de Seguridad TI',
            'slug' => 'oficial-seguridad-ti',
            'color' => 'purple',
            'is_protected' => false,
        ]);
        $secRole->givePermission('audit.view');

        $secUser = User::factory()->create(['role_id' => $secRole->id]);

        $response = $this->actingAs($secUser)->get(route('audit.index'));
        $response->assertOk();
    }

    public function test_user_with_users_view_and_create_can_manage_users_with_dynamic_role(): void
    {
        $hrRole = Role::create([
            'name' => 'Recursos Humanos',
            'slug' => 'recursos-humanos',
            'color' => 'emerald',
            'is_protected' => false,
        ]);
        $hrRole->givePermission('users.view');
        $hrRole->givePermission('users.create');

        $hrUser = User::factory()->create(['role_id' => $hrRole->id]);

        $responseIndex = $this->actingAs($hrUser)->get(route('users.index'));
        $responseIndex->assertOk();

        // Crear un usuario asignándole un rol personalizado
        $targetRole = Role::create([
            'name' => 'Especialista Radar',
            'slug' => 'especialista-radar',
            'color' => 'cyan',
            'is_protected' => false,
        ]);

        $responseStore = $this->actingAs($hrUser)->post(route('users.store'), [
            'name' => 'Ing. Juan Radar',
            'username' => 'juan.radar',
            'password' => 'Password123!',
            'role_id' => $targetRole->id,
            'badge_number' => 'RAD-001',
        ]);

        $responseStore->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'juan.radar',
            'role_id' => $targetRole->id,
        ]);
    }
}
