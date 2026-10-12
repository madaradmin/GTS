<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // 1. Debe estar autenticado
        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'No autenticado.'], 401)
                : redirect()->route('login');
        }

        // 2. La cuenta institucional debe estar activa
        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['username' => 'Su cuenta institucional se encuentra inactiva. Contacte al Administrador.']);
        }

        // 3. El Administrador del Sistema posee acceso universal
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Si la ruta requiere admin, permitir acceso a usuarios con permisos granulares equivalentes
        if (in_array('admin', $roles, true)) {
            $path = $request->path();
            if (str_starts_with($path, 'configuracion/roles') && $user->hasPermission('roles.view')) {
                return $next($request);
            }
            if (str_starts_with($path, 'configuracion/usuarios') && $user->hasPermission('users.view')) {
                return $next($request);
            }
            if (str_starts_with($path, 'auditoria') && $user->hasPermission('audit.view')) {
                return $next($request);
            }
        }

        // 4. Verificar si el usuario posee alguno de los roles autorizados
        if (! empty($roles) && ! $user->hasRole($roles)) {
            abort(403, 'Acceso denegado: Su rol institucional ('.($user->role?->name ?? 'Sin Rol').') no está autorizado para este recurso.');
        }

        return $next($request);
    }
}
