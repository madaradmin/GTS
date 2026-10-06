<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApocRoleSplitPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
    }

    public function test_user_management_screen_displays_only_valid_assignable_roles(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('users.index'));

        $response->assertOk();
        // Should contain new role options
        $response->assertSee('APOC-CCO');
        $response->assertSee('APOC-CSA');
        $response->assertSee('GTS');
        $response->assertSee('ADMINISTRADOR');

        // The selectable options list passed to view
        $roles = $response->viewData('roles');
        $this->assertCount(4, $roles);
        $slugs = $roles->pluck('slug')->all();
        $this->assertEquals(['apoc-cco', 'apoc-csa', 'gts', 'admin'], $slugs);
        $this->assertNotContains('apoc', $slugs);
    }

    public function test_admin_can_create_apoc_cco_user(): void
    {
        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'Operador CCO Nuevo',
            'username' => 'cco.nuevo',
            'password' => 'Password123!',
            'role_id' => $ccoRole->id,
            'badge_number' => 'CCO-999',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Operador CCO Nuevo',
            'username' => 'cco.nuevo',
            'role_id' => $ccoRole->id,
            'badge_number' => 'CCO-999',
        ]);

        $user = User::where('username', 'cco.nuevo')->firstOrFail();
        $this->assertTrue($user->isApocCco());
        $this->assertFalse($user->isApocCsa());
        $this->assertTrue($user->isApoc());
    }

    public function test_admin_can_create_apoc_csa_user(): void
    {
        $csaRole = Role::where('slug', 'apoc-csa')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'Operador CSA Nuevo',
            'username' => 'csa.nuevo',
            'password' => 'Password123!',
            'role_id' => $csaRole->id,
            'badge_number' => 'CSA-999',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Operador CSA Nuevo',
            'username' => 'csa.nuevo',
            'role_id' => $csaRole->id,
            'badge_number' => 'CSA-999',
        ]);

        $user = User::where('username', 'csa.nuevo')->firstOrFail();
        $this->assertTrue($user->isApocCsa());
        $this->assertFalse($user->isApocCco());
        $this->assertTrue($user->isApoc());
    }

    public function test_admin_cannot_create_user_with_legacy_apoc_role(): void
    {
        $legacyApocRole = Role::where('slug', 'apoc')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'Intento Legacy APOC',
            'username' => 'legacy.apoc',
            'password' => 'Password123!',
            'role_id' => $legacyApocRole->id,
            'badge_number' => 'LEG-001',
        ]);

        $response->assertSessionHasErrors(['role_id']);
        $this->assertDatabaseMissing('users', [
            'username' => 'legacy.apoc',
        ]);
    }

    public function test_admin_can_update_user_role_to_apoc_cco_and_apoc_csa(): void
    {
        $user = User::factory()->gts()->create();
        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();
        $csaRole = Role::where('slug', 'apoc-csa')->firstOrFail();

        // Actualizar a CCO
        $response1 = $this->actingAs($this->adminUser)->put(route('users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'role_id' => $ccoRole->id,
        ]);
        $response1->assertRedirect(route('users.index'));
        $this->assertEquals($ccoRole->id, $user->fresh()->role_id);
        $this->assertTrue($user->fresh()->isApocCco());

        // Actualizar a CSA
        $response2 = $this->actingAs($this->adminUser)->put(route('users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'role_id' => $csaRole->id,
        ]);
        $response2->assertRedirect(route('users.index'));
        $this->assertEquals($csaRole->id, $user->fresh()->role_id);
        $this->assertTrue($user->fresh()->isApocCsa());
    }

    public function test_user_table_renders_apoc_cco_and_apoc_csa_badges(): void
    {
        $ccoUser = User::factory()->apocCco()->create(['name' => 'Usuario Test CCO']);
        $csaUser = User::factory()->apocCsa()->create(['name' => 'Usuario Test CSA']);

        $response = $this->actingAs($this->adminUser)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Usuario Test CCO');
        $response->assertSee('APOC-CCO');
        $response->assertSee('Usuario Test CSA');
        $response->assertSee('APOC-CSA');
    }
}
