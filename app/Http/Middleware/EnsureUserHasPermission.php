<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Valida que el usuario autenticado posea al menos uno de los permisos requeridos.
     * El Administrador del Sistema cuenta con bypass total.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        // 1. Debe estar autenticado
        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'No autenticado.'], 401)
                : redirect()->route('login');
        }

        // 2. La cuenta institucional debe encontrarse activa
        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['username' => 'Su cuenta institucional se encuentra inactiva. Contacte al Administrador.']);
        }

        // 3. Bypass irrestricto para el Administrador del Sistema
        if ($user->isAdmin()) {
            return $next($request);
        }

        // 4. Validar tenencia de permisos
        if (! empty($permissions)) {
            $authorized = false;
            foreach ($permissions as $permission) {
                if ($user->hasPermission($permission)) {
                    $authorized = true;
                    break;
                }
            }

            if (! $authorized) {
                abort(403, 'Acceso denegado: No posee los permisos requeridos para acceder a esta función.');
            }
        }

        return $next($request);
    }
}
