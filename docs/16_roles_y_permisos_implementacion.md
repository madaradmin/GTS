# IMPLEMENTACIÓN DE ROLES Y PERMISOS (RBAC)
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Mecanismo:** Middleware `role` + Gates de Autorización  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. COMPONENTES IMPLEMENTADOS

1. **Middleware de Roles (`app/Http/Middleware/EnsureUserHasRole.php`):**
   * Valida que el usuario tenga una sesión activa.
   * Valida que la cuenta institucional esté activa (`is_active = true`); si está suspendida, invalida la sesión de inmediato.
   * Concede acceso universal y transparente a los usuarios con rol `admin`.
   * Verifica la pertenencia a los roles solicitados (`role:apoc`, `role:gts` o combinados `role:apoc,gts`), respondiendo con HTTP `403 Forbidden` ante accesos no autorizados.

2. **Registro de Alias en Kernel (`bootstrap/app.php`):**
   ```php
   $middleware->alias([
       'role' => \App\Http\Middleware\EnsureUserHasRole::class,
   ]);
   ```

3. **Métodos de Identidad en `User` (`app/Models/User.php`):**
   * `$user->isApoc()`
   * `$user->isGts()`
   * `$user->isAdmin()`
   * `$user->hasRole(['apoc', 'gts'])`

4. **Gates Globales (`app/Providers/AppServiceProvider.php`):**
   * `Gate::before(...)` para el bypass de superusuario `admin`.
   * `access-apoc`, `access-gts`, `access-admin`.

---

### 2. RESULTADOS DE LA SUITE DE PRUEBAS AUTOMATIZADAS

Se ejecutó la suite `tests/Feature/RoleMiddlewareTest.php`:
* `test_unauthenticated_users_are_redirected_to_login`: **PASÓ**
* `test_inactive_users_are_logged_out_and_redirected`: **PASÓ**
* `test_apoc_user_can_access_apoc_route_but_not_gts_route`: **PASÓ**
* `test_gts_user_can_access_gts_route_but_not_apoc_route`: **PASÓ**
* `test_admin_has_universal_access_to_all_role_routes`: **PASÓ**

Total: 5 pruebas ejecutadas, 16 aserciones superadas al 100%.

