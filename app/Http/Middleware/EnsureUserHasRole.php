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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // 1. Debe estar autenticado
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'No autenticado.'], 401)
                : redirect()->route('login');
        }

        // 2. La cuenta institucional debe estar activa
        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Su cuenta institucional se encuentra inactiva. Contacte al Administrador.']);
        }

        // 3. El Administrador del Sistema posee acceso universal
        if ($user->isAdmin()) {
            return $next($request);
        }

        // 4. Verificar si el usuario posee alguno de los roles autorizados
        if (!empty($roles) && !$user->hasRole($roles)) {
            abort(403, 'Acceso denegado: Su rol institucional (' . ($user->role?->name ?? 'Sin Rol') . ') no está autorizado para este recurso.');
        }

        return $next($request);
    }
}

