<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleOperationalScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Muestra el catálogo de roles, usuarios asignados e insignias tácticas.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if(! $user->isAdmin() && ! $user->hasPermission('roles.view'), 403, 'Acceso denegado: No posee permisos para consultar roles.');

        $query = Role::withCount(['users', 'permissions', 'operationalScopes']);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($q) use ($search, $operator) {
                $q->where('name', $operator, "%{$search}%")
                    ->orWhere('slug', $operator, "%{$search}%")
                    ->orWhere('description', $operator, "%{$search}%");
            });
        }

        $roles = $query->orderBy('is_protected', 'desc')
            ->orderBy('name')
            ->get();

        $metrics = [
            'total' => Role::count(),
            'protected' => Role::where('is_protected', true)->count(),
            'custom' => Role::where('is_protected', false)->count(),
            'total_users' => DB::table('users')->whereNotNull('role_id')->count(),
            'total_permissions' => Permission::count(),
        ];

        return view('roles.index', [
            'roles' => $roles,
            'metrics' => $metrics,
            'colorPresets' => Role::getColorPresets(),
        ]);
    }

    /**
     * Registra un nuevo rol operativo en el sistema.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = new Role;
        $role->name = $validated['name'];
        $role->slug = $validated['slug'];
        $role->description = $validated['description'] ?? null;
        $role->is_protected = false;
        $role->setColorPreset($validated['color']);
        $role->save();

        Log::info("Nuevo rol operativo creado [{$role->slug} - {$role->name}] por usuario [{$request->user()->username}].");

        return redirect()->route('roles.manage', $role)
            ->with('status_message', "Rol \"{$role->name}\" creado exitosamente. Configure ahora sus permisos y alcances operativos.");
    }

    /**
     * Vista de configuración integral de permisos y alcances de un rol.
     */
    public function manage(Request $request, Role $role): View
    {
        $user = $request->user();
        abort_if(! $user->isAdmin() && ! $user->hasPermission('roles.view'), 403, 'Acceso denegado: No posee permisos para consultar roles.');

        $role->load(['permissions', 'operationalScopes']);

        $modules = [
            'incidents' => 'Incidencias Operativas',
            'users' => 'Usuarios y Cuadrillas',
            'roles' => 'Roles y Permisos',
            'reports' => 'Reportes e Indicadores',
            'audit' => 'Auditoría y Seguridad',
        ];

        $permissionsByModule = Permission::all()->groupBy('module');

        $allRoles = Role::orderBy('is_protected', 'desc')->orderBy('name')->get();

        $assignedPermissionIds = $role->permissions->pluck('id')->all();

        $viewScopeRoleIds = $role->operationalScopes
            ->where('scope_type', 'view')
            ->pluck('target_role_id')
            ->all();

        $attendScopeRoleIds = $role->operationalScopes
            ->where('scope_type', 'attend')
            ->pluck('target_role_id')
            ->all();

        $assignScopeRoleIds = $role->operationalScopes
            ->where('scope_type', 'assign')
            ->pluck('target_role_id')
            ->all();

        return view('roles.manage', [
            'role' => $role,
            'modules' => $modules,
            'permissionsByModule' => $permissionsByModule,
            'allRoles' => $allRoles,
            'assignedPermissionIds' => $assignedPermissionIds,
            'viewScopeRoleIds' => $viewScopeRoleIds,
            'attendScopeRoleIds' => $attendScopeRoleIds,
            'assignScopeRoleIds' => $assignScopeRoleIds,
            'colorPresets' => Role::getColorPresets(),
        ]);
    }

    /**
     * Actualiza información básica y estilo visual de un rol.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $role->name = $validated['name'];
        $role->description = $validated['description'] ?? null;
        $role->setColorPreset($validated['color']);

        if (! $role->is_protected && isset($validated['slug'])) {
            $role->slug = $validated['slug'];
        }

        $role->save();

        Log::info("Rol [{$role->slug}] actualizado por usuario [{$request->user()->username}].");

        return back()->with('status_message', "Rol \"{$role->name}\" actualizado correctamente.");
    }

    /**
     * Elimina permanentemente un rol del sistema y limpia sus relaciones.
     * Única excepción: El rol ADMINISTRADOR (admin) está institucionalmente protegido.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->isAdmin() && ! $user->hasPermission('roles.delete'), 403, 'Acceso denegado: No posee permiso para eliminar roles.');

        if ($role->isProtectedFromDeletion()) {
            return redirect()->route('roles.index')->with('error', 'El rol de ADMINISTRADOR está protegido y no puede ser eliminado.');
        }

        $roleName = $role->name;
        $roleSlug = $role->slug;
        $usersCount = $role->users()->count();

        DB::transaction(function () use ($role) {
            // Desvincular usuarios asignados para preservar sus cuentas, historiales e incidencias intactos
            $role->users()->update(['role_id' => null]);

            // Desvincular permisos asociados
            $role->permissions()->detach();

            // Limpiar alcances operativos donde este rol sea origen o destino
            RoleOperationalScope::where('source_role_id', $role->id)->delete();
            RoleOperationalScope::where('target_role_id', $role->id)->delete();

            // Eliminar el rol
            $role->delete();
        });

        Log::warning("Rol [{$roleSlug} - {$roleName}] eliminado por usuario [{$request->user()->username}]. Usuarios desvinculados: {$usersCount}.");

        return redirect()->route('roles.index')->with('status_message', "El rol \"{$roleName}\" ha sido eliminado exitosamente del sistema.");
    }

    /**
     * Actualiza la matriz de permisos asignados a un rol.
     */
    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->isAdmin() && ! $user->hasPermission('roles.manage_permissions'), 403, 'Acceso denegado: No posee permiso para gestionar matriz de permisos.');

        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $request->input('permissions', []);

        DB::transaction(function () use ($role, $permissionIds) {
            $role->permissions()->sync($permissionIds);
        });

        Log::info("Permisos del rol [{$role->name}] sincronizados por [{$user->username}]. Total: ".count($permissionIds));

        return back()->with('status_message', "Matriz de permisos actualizada correctamente para el rol \"{$role->name}\".");
    }

    /**
     * Actualiza la matriz de alcances operativos (view, attend, assign) entre roles.
     */
    public function updateScopes(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->isAdmin() && ! $user->hasPermission('roles.manage_scopes'), 403, 'Acceso denegado: No posee permiso para configurar alcances operativos.');

        $request->validate([
            'view_scopes' => ['nullable', 'array'],
            'view_scopes.*' => ['integer', 'exists:roles,id'],
            'attend_scopes' => ['nullable', 'array'],
            'attend_scopes.*' => ['integer', 'exists:roles,id'],
            'assign_scopes' => ['nullable', 'array'],
            'assign_scopes.*' => ['integer', 'exists:roles,id'],
        ]);

        $viewScopes = $request->input('view_scopes', []);
        $attendScopes = $request->input('attend_scopes', []);
        $assignScopes = $request->input('assign_scopes', []);

        DB::transaction(function () use ($role, $viewScopes, $attendScopes, $assignScopes) {
            // Eliminar los alcances existentes del rol origen
            RoleOperationalScope::where('source_role_id', $role->id)->delete();

            // Insertar alcances de visualización
            foreach ($viewScopes as $targetId) {
                RoleOperationalScope::create([
                    'source_role_id' => $role->id,
                    'target_role_id' => (int) $targetId,
                    'scope_type' => 'view',
                ]);
            }

            // Insertar alcances de atención técnica
            foreach ($attendScopes as $targetId) {
                RoleOperationalScope::create([
                    'source_role_id' => $role->id,
                    'target_role_id' => (int) $targetId,
                    'scope_type' => 'attend',
                ]);
            }

            // Insertar alcances de asignación
            foreach ($assignScopes as $targetId) {
                RoleOperationalScope::create([
                    'source_role_id' => $role->id,
                    'target_role_id' => (int) $targetId,
                    'scope_type' => 'assign',
                ]);
            }
        });

        Log::info("Alcances operativos del rol [{$role->name}] actualizados por [{$user->username}]. (Ver: ".count($viewScopes).', Atender: '.count($attendScopes).', Asignar: '.count($assignScopes).')');

        return back()->with('status_message', "Alcances operativos actualizados correctamente para el rol \"{$role->name}\".");
    }
}
