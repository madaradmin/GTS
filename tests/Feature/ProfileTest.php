<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_admin_profile_information_can_be_updated_including_username(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->patch('/profile', [
                'name' => 'Admin Actualizado',
                'username' => 'admin.updated',
                'email' => 'admin.updated@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $admin->refresh();

        $this->assertSame('Admin Actualizado', $admin->name);
        $this->assertSame('admin.updated', $admin->username);
        $this->assertSame('admin.updated@example.com', $admin->email);
        $this->assertNull($admin->email_verified_at);
    }

    public function test_apoc_and_gts_users_cannot_change_their_username(): void
    {
        $apocUser = User::factory()->apoc()->create(['username' => 'apoc.original']);
        $gtsUser = User::factory()->gts()->create(['username' => 'gts.original']);

        // 1. Intento de APOC de modificar su username -> Rechazado por validación
        $responseApoc = $this
            ->actingAs($apocUser)
            ->patch('/profile', [
                'name' => 'APOC Modificado',
                'username' => 'apoc.hacked',
                'email' => 'apoc@example.com',
            ]);

        $responseApoc->assertSessionHasErrors('username');
        $this->assertSame('apoc.original', $apocUser->fresh()->username);

        // 2. Intento de GTS de modificar su username -> Rechazado por validación
        $responseGts = $this
            ->actingAs($gtsUser)
            ->patch('/profile', [
                'name' => 'GTS Modificado',
                'username' => 'gts.hacked',
                'email' => 'gts@example.com',
            ]);

        $responseGts->assertSessionHasErrors('username');
        $this->assertSame('gts.original', $gtsUser->fresh()->username);
    }

    public function test_apoc_and_gts_can_update_name_and_email_while_username_is_omitted_or_unchanged(): void
    {
        $gtsUser = User::factory()->gts()->create(['username' => 'gts.fijo', 'name' => 'Nombre Viejo']);

        // Al enviar el formulario desde el navegador, el campo disabled no envía username
        $response = $this
            ->actingAs($gtsUser)
            ->patch('/profile', [
                'name' => 'Nombre Nuevo GTS',
                'email' => 'nuevo.email@aifa.aero',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');

        $gtsUser->refresh();
        $this->assertSame('Nombre Nuevo GTS', $gtsUser->name);
        $this->assertSame('gts.fijo', $gtsUser->username);
        $this->assertSame('nuevo.email@aifa.aero', $gtsUser->email);
    }

    public function test_profile_view_renders_locked_username_indicator_for_gts_and_apoc(): void
    {
        $gtsUser = User::factory()->gts()->create();
        $adminUser = User::factory()->admin()->create();

        // GTS ve campo bloqueado y mensaje informativo
        $responseGts = $this->actingAs($gtsUser)->get('/profile');
        $responseGts->assertOk();
        $responseGts->assertSee('No editable');
        $responseGts->assertSee('disabled', false);

        // Admin ve campo editable
        $responseAdmin = $this->actingAs($adminUser)->get('/profile');
        $responseAdmin->assertOk();
        $responseAdmin->assertDontSee('No editable');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'username' => $user->username,
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_admin_can_delete_their_account_when_multiple_admins_exist(): void
    {
        $admin = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($admin->fresh());
        $this->assertNotNull($admin2->fresh());
    }

    public function test_admin_cannot_delete_their_account_if_they_are_the_only_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertSessionHas('error');
        $this->assertNotNull($admin->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($admin->fresh());
    }

    public function test_gts_and_apoc_users_cannot_delete_their_own_account_and_view_omits_delete_section(): void
    {
        $gtsUser = User::factory()->gts()->create();
        $apocUser = User::factory()->apoc()->create();

        // 1. GTS en UI: No ve la sección ni modal de eliminación
        $responseGtsView = $this->actingAs($gtsUser)->get('/profile');
        $responseGtsView->assertOk();
        $responseGtsView->assertDontSee('Delete Account');
        $responseGtsView->assertDontSee('confirm-user-deletion');
        $responseGtsView->assertDontSee('Zona de Riesgo / Baja de Cuenta');

        // 2. GTS en Backend: Petición DELETE rechazada con 403 Forbidden
        $responseGtsDelete = $this->actingAs($gtsUser)->delete('/profile', [
            'password' => 'password',
        ]);
        $responseGtsDelete->assertForbidden();
        $this->assertNotNull($gtsUser->fresh());

        // 3. APOC en UI: No ve la sección ni modal de eliminación
        $responseApocView = $this->actingAs($apocUser)->get('/profile');
        $responseApocView->assertOk();
        $responseApocView->assertDontSee('Delete Account');
        $responseApocView->assertDontSee('confirm-user-deletion');
        $responseApocView->assertDontSee('Zona de Riesgo / Baja de Cuenta');

        // 4. APOC en Backend: Petición DELETE rechazada con 403 Forbidden
        $responseApocDelete = $this->actingAs($apocUser)->delete('/profile', [
            'password' => 'password',
        ]);
        $responseApocDelete->assertForbidden();
        $this->assertNotNull($apocUser->fresh());
    }
}
