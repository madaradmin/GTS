<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Muestra la lista de usuarios y cuadrillas del sistema (Exclusivo Administrador).
     */
    public function index(Request $request): View
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('users.view'), 403, 'Acceso denegado: No posee permisos para consultar usuarios.');

        $query = User::with('role');

        // Filtro de búsqueda libre
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $operator = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($q) use ($search, $operator) {
                $q->where('name', $operator, "%{$search}%")
                    ->orWhere('username', $operator, "%{$search}%")
                    ->orWhere('email', $operator, "%{$search}%")
                    ->orWhere('badge_number', $operator, "%{$search}%");
            });
        }

        // Filtro por Rol
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->query('role_id'));
        }

        // Filtro por Estado de Cuenta
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->query('is_active') === '1');
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $roles = Role::where('slug', '!=', 'apoc')
            ->orderByRaw("CASE slug WHEN 'apoc-cco' THEN 1 WHEN 'apoc-csa' THEN 2 WHEN 'gts' THEN 3 WHEN 'admin' THEN 4 ELSE 5 END")
            ->orderBy('name')
            ->get();

        // Métricas de Usuarios
        $metrics = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'gts' => User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->count(),
            'apoc' => User::whereHas('role', fn ($q) => $q->whereIn('slug', ['apoc', 'apoc-cco', 'apoc-csa']))->count(),
            'apoc_cco' => User::whereHas('role', fn ($q) => $q->where('slug', 'apoc-cco'))->count(),
            'apoc_csa' => User::whereHas('role', fn ($q) => $q->where('slug', 'apoc-csa'))->count(),
            'admin' => User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->count(),
        ];

        return view('users.index', [
            'users' => $users,
            'roles' => $roles,
            'metrics' => $metrics,
            'filters' => $request->only(['search', 'role_id', 'is_active']),
        ]);
    }

    /**
     * Da de alta un nuevo usuario institucional con rol asignado (Exclusivo Administrador).
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('users.create'), 403, 'Acceso denegado: No posee permisos para registrar usuarios.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'password' => ['required', 'string', Password::defaults()],
            'role_id' => ['required', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('slug', '!=', 'apoc'))],
            'badge_number' => ['nullable', 'string', 'max:50'],
        ], [
            'username.regex' => 'El nombre de usuario sólo puede contener letras, números, puntos, guiones y guiones bajos.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'email' => null,
            'password' => $validated['password'],
            'role_id' => $validated['role_id'],
            'badge_number' => $validated['badge_number'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('users.index')
            ->with('status_message', "El usuario {$user->name} (@{$user->username}) ha sido registrado exitosamente con credenciales de acceso.");
    }

    /**
     * Actualiza la información operativa y rol del usuario (Exclusivo Administrador).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('users.edit'), 403, 'Acceso denegado: No posee permisos para editar usuarios.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role_id' => ['required', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('slug', '!=', 'apoc'))],
            'badge_number' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', Password::defaults()],
        ], [
            'username.regex' => 'El nombre de usuario sólo puede contener letras, números, puntos, guiones y guiones bajos.',
        ]);

        // Evitar que el administrador se quite su propio rol de administrador si es el único
        if ($user->id === $request->user()->id && $user->isAdmin()) {
            $newRole = Role::find($validated['role_id']);
            if ($newRole && $newRole->slug !== 'admin') {
                return back()->with('error', 'No puede revocar su propio rol de Administrador.');
            }
        }

        $user->name = $validated['name'];
        $user->username = strtolower($validated['username']);
        $user->email = ! empty($validated['email']) ? $validated['email'] : null;
        $user->role_id = $validated['role_id'];
        $user->badge_number = $validated['badge_number'] ?? null;

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('users.index')
            ->with('status_message', "Los datos del usuario {$user->name} (@{$user->username}) han sido actualizados correctamente.");
    }

    /**
     * Alterna el estado de activación de una cuenta de usuario (Exclusivo Administrador).
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('users.edit'), 403, 'Acceso denegado: No posee permisos para modificar el estado de usuarios.');

        // 1. Prohibir auto-desactivación
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No puede desactivar su propia cuenta de Administrador en sesión activa.');
        }

        // 2. Prohibir desactivar al último administrador activo
        if ($user->isAdmin() && $user->is_active) {
            $activeAdminsCount = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                ->where('is_active', true)
                ->count();

            if ($activeAdminsCount <= 1) {
                return back()->with('error', 'No es posible desactivar al único Administrador activo del sistema.');
            }
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $actionText = $user->is_active ? 'activada' : 'desactivada';

        return redirect()->route('users.index')
            ->with('status_message', "La cuenta del usuario {$user->name} ha sido {$actionText} exitosamente.");
    }

    /**
     * Eliminación permanente de cuentas de usuario gestionando dependencias relacionales en PostgreSQL (Exclusivo Administrador).
     * Transfiere la titularidad de incidencias, evidencias e historial de auditoría al Administrador
     * activo para salvaguardar la trazabilidad aeronáutica OACI sin violar llaves foráneas.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if(! $request->user()->is_active, 403, 'Usuario inactivo.');
        abort_if(! $request->user()->isAdmin() && ! $request->user()->hasPermission('users.delete'), 403, 'Acceso denegado: No posee permisos para eliminar usuarios.');

        // 1. Prohibir auto-eliminación
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No puede eliminar su propia cuenta de Administrador en sesión activa.');
        }

        // 2. Prohibir eliminar al último administrador
        if ($user->isAdmin()) {
            $adminCount = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'No es posible eliminar al único Administrador del sistema.');
            }
        }

        $admin = $request->user();
        $userName = $user->name;
        $userLogin = $user->username;

        DB::transaction(function () use ($user, $admin, $userName, $userLogin) {
            // A. Reasignar incidencias donde el usuario es técnico asignado -> NULL (Sin Asignar)
            Incident::where('assigned_to_user_id', $user->id)
                ->update(['assigned_to_user_id' => null]);

            // B. Reasignar incidencias reportadas por el usuario al administrador activo
            Incident::where('reported_by_user_id', $user->id)
                ->update(['reported_by_user_id' => $admin->id]);

            // C. Reasignar evidencias cargadas por el usuario al administrador activo
            IncidentAttachment::where('uploaded_by_user_id', $user->id)
                ->update(['uploaded_by_user_id' => $admin->id]);

            // D. Transferir registros en la bitácora de auditoría pericial documentando el operador original
            DB::table('incident_histories')
                ->where('user_id', $user->id)
                ->get()
                ->each(function ($history) use ($admin, $userName, $userLogin) {
                    $note = "[Operador de origen purgado del sistema: {$userName} (@{$userLogin})]";
                    $newComment = $history->comment ? "{$history->comment} {$note}" : $note;
                    DB::table('incident_histories')
                        ->where('id', $history->id)
                        ->update([
                            'user_id' => $admin->id,
                            'comment' => $newComment,
                        ]);
                });

            // E. Purgar notificaciones dirigidas al usuario
            DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class)
                ->delete();

            // F. Purgar registros de disponibilidad de turno si existen
            if (Schema::hasTable('agent_availability_logs')) {
                DB::table('agent_availability_logs')
                    ->where('user_id', $user->id)
                    ->delete();
            }

            // G. Eliminación permanente física del usuario en PostgreSQL
            $user->delete();
        });

        return redirect()->route('users.index')
            ->with('status_message', "La cuenta del usuario {$userName} (@{$userLogin}) ha sido eliminada permanentemente del sistema.");
    }
}
