<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');

        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');

        $data = $request->validated();

        // APOC y GTS no pueden modificar su propio username
        if (! $request->user()->isAdmin()) {
            unset($data['username']);
        }

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');

        // GTS y APOC tienen estrictamente prohibido eliminar su propia cuenta
        abort_if(
            ! $request->user()->isAdmin(),
            403,
            'Acceso denegado: Los operadores GTS y APOC no están autorizados para eliminar su propia cuenta institucional.'
        );

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Evitar que el sistema se quede sin administradores
        $adminCount = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->count();
        if ($adminCount <= 1) {
            return back()->with('error', 'No es posible eliminar al único Administrador del sistema.');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
