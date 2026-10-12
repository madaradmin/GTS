<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleOperationalScope;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Depuración de privilegios sin función real o redundantes
        Permission::whereIn('slug', [
            'dashboard.technical',
            'dashboard.monitoring',
            'dashboard.executive',
            'audit.export',
            'users.manage_shifts',
            'incidents.edit',
            'incidents.export',
        ])->delete();

        // 1. Catálogo de permisos granulares funcionales del sistema GTS (25 privilegios)
        $permissions = [
            // Módulo: Incidencias (12 capacidades funcionales)
            [
                'name' => 'Crear incidencias',
                'slug' => 'incidents.create',
                'module' => 'incidents',
                'description' => 'Permite registrar y abrir nuevas incidencias en la plataforma.',
            ],
            [
                'name' => 'Ver incidencias asignadas',
                'slug' => 'incidents.view_assigned',
                'module' => 'incidents',
                'description' => 'Permite visualizar incidencias asignadas directamente al usuario autenticado.',
            ],
            [
                'name' => 'Ver incidencias de área/departamento',
                'slug' => 'incidents.view_department',
                'module' => 'incidents',
                'description' => 'Permite ver incidencias originadas o pertenecientes a su área funcional.',
            ],
            [
                'name' => 'Ver todas las incidencias',
                'slug' => 'incidents.view_all',
                'module' => 'incidents',
                'description' => 'Permite consultar el catálogo completo de todas las incidencias del sistema.',
            ],
            [
                'name' => 'Atender incidencias',
                'slug' => 'incidents.attend',
                'module' => 'incidents',
                'description' => 'Permite iniciar atención técnica, diagnóstico y transición a estado EN PROCESO.',
            ],
            [
                'name' => 'Resolver incidencias',
                'slug' => 'incidents.resolve',
                'module' => 'incidents',
                'description' => 'Permite registrar notas técnicas de solución y marcar incidencia como RESUELTA.',
            ],
            [
                'name' => 'Dar visto bueno y cierre formal',
                'slug' => 'incidents.vobo_close',
                'module' => 'incidents',
                'description' => 'Permite validar la conformidad de la solución y cerrar formalmente la incidencia.',
            ],
            [
                'name' => 'Reabrir incidencias',
                'slug' => 'incidents.reopen',
                'module' => 'incidents',
                'description' => 'Permite rechazar la solución y reabrir una incidencia no conforme.',
            ],
            [
                'name' => 'Eliminar incidencias',
                'slug' => 'incidents.delete',
                'module' => 'incidents',
                'description' => 'Permite eliminar definitivamente registros de incidencias y sus adjuntos.',
            ],
            [
                'name' => 'Asignar incidencias',
                'slug' => 'incidents.assign',
                'module' => 'incidents',
                'description' => 'Permite asignar o reasignar incidencias a especialistas o cuadrillas.',
            ],
            [
                'name' => 'Tomar incidencias',
                'slug' => 'incidents.take',
                'module' => 'incidents',
                'description' => 'Permite que un especialista técnico se autoasigne una incidencia disponible.',
            ],
            [
                'name' => 'Liberar incidencias',
                'slug' => 'incidents.release',
                'module' => 'incidents',
                'description' => 'Permite desvincular la asignación para regresar la incidencia a la cola disponible.',
            ],

            // Módulo: Usuarios
            [
                'name' => 'Ver usuarios',
                'slug' => 'users.view',
                'module' => 'users',
                'description' => 'Permite listar y consultar detalles de las cuentas de usuario y cuadrillas.',
            ],
            [
                'name' => 'Crear usuarios',
                'slug' => 'users.create',
                'module' => 'users',
                'description' => 'Permite registrar nuevos usuarios con credenciales y roles asignados.',
            ],
            [
                'name' => 'Editar usuarios',
                'slug' => 'users.edit',
                'module' => 'users',
                'description' => 'Permite actualizar datos de contacto, contraseñas, placas y estado de usuarios.',
            ],
            [
                'name' => 'Eliminar usuarios',
                'slug' => 'users.delete',
                'module' => 'users',
                'description' => 'Permite desactivar o eliminar cuentas de usuario del sistema.',
            ],

            // Módulo: Roles y Permisos
            [
                'name' => 'Ver roles y permisos',
                'slug' => 'roles.view',
                'module' => 'roles',
                'description' => 'Permite consultar el catálogo de roles, matriz de permisos y alcances operativos.',
            ],
            [
                'name' => 'Crear roles',
                'slug' => 'roles.create',
                'module' => 'roles',
                'description' => 'Permite crear nuevos roles con identificadores y configuración de insignias.',
            ],
            [
                'name' => 'Editar roles',
                'slug' => 'roles.edit',
                'module' => 'roles',
                'description' => 'Permite modificar nombres, descripciones y apariencia visual de los roles.',
            ],
            [
                'name' => 'Eliminar roles',
                'slug' => 'roles.delete',
                'module' => 'roles',
                'description' => 'Permite eliminar roles personalizados no protegidos.',
            ],
            [
                'name' => 'Configurar permisos de roles',
                'slug' => 'roles.manage_permissions',
                'module' => 'roles',
                'description' => 'Permite asignar o revocar permisos específicos en la matriz por rol.',
            ],
            [
                'name' => 'Configurar alcances de roles',
                'slug' => 'roles.manage_scopes',
                'module' => 'roles',
                'description' => 'Permite definir la matriz de alcances operativos (ver, atender, asignar) entre roles.',
            ],

            // Módulo: Reportes
            [
                'name' => 'Ver reportes',
                'slug' => 'reports.view',
                'module' => 'reports',
                'description' => 'Permite consultar reportes estadísticos, gráficos e indicadores de gestión.',
            ],
            [
                'name' => 'Exportar reportes',
                'slug' => 'reports.export',
                'module' => 'reports',
                'description' => 'Permite descargar reportes consolidados y resúmenes ejecutivos en PDF o Excel.',
            ],

            // Módulo: Auditoría
            [
                'name' => 'Ver auditoría',
                'slug' => 'audit.view',
                'module' => 'audit',
                'description' => 'Permite consultar la bitácora global de trazabilidad, eventos y cambios de estado.',
            ],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );
        }

        // 2. Configurar metadatos visuales y protección de roles existentes
        $roleConfigs = [
            'admin' => [
                'name' => 'ADMINISTRADOR',
                'description' => 'Supervisión general, administración de usuarios, roles, catálogos y auditoría global.',
                'color' => 'purple',
                'badge_bg' => 'bg-purple-500/10',
                'badge_text' => 'text-purple-400',
                'badge_border' => 'border-purple-500/20',
                'is_protected' => true,
            ],
            'gts' => [
                'name' => 'GTS',
                'description' => 'Grupo de Seguridad Tecnológica - Asignación, diagnóstico, atención y solución técnica en campo.',
                'color' => 'cyan',
                'badge_bg' => 'bg-cyan-500/10',
                'badge_text' => 'text-cyan-400',
                'badge_border' => 'border-cyan-500/20',
                'is_protected' => false,
            ],
            'apoc-cco' => [
                'name' => 'APOC-CCO',
                'description' => 'Centro de Control Operativo - Reporte, seguimiento y cierre de incidencias CCO.',
                'color' => 'amber',
                'badge_bg' => 'bg-amber-500/10',
                'badge_text' => 'text-amber-400',
                'badge_border' => 'border-amber-500/20',
                'is_protected' => false,
            ],
            'apoc-csa' => [
                'name' => 'APOC-CSA',
                'description' => 'Centro de Servicios Aeroportuarios - Reporte, seguimiento y cierre de incidencias CSA.',
                'color' => 'orange',
                'badge_bg' => 'bg-orange-500/10',
                'badge_text' => 'text-orange-400',
                'badge_border' => 'border-orange-500/20',
                'is_protected' => false,
            ],
            'apoc' => [
                'name' => 'Operador APOC',
                'description' => 'Centro de Operaciones Aeroportuarias - Rol base heredado.',
                'color' => 'blue',
                'badge_bg' => 'bg-blue-500/10',
                'badge_text' => 'text-blue-400',
                'badge_border' => 'border-blue-500/20',
                'is_protected' => false,
            ],
        ];

        foreach ($roleConfigs as $slug => $data) {
            Role::updateOrCreate(
                ['slug' => $slug],
                $data
            );
        }

        $allRoles = Role::all();
        $adminRole = $allRoles->firstWhere('slug', 'admin');
        $gtsRole = $allRoles->firstWhere('slug', 'gts');
        $ccoRole = $allRoles->firstWhere('slug', 'apoc-cco');
        $csaRole = $allRoles->firstWhere('slug', 'apoc-csa');
        $apocRole = $allRoles->firstWhere('slug', 'apoc');

        $allPermissionIds = Permission::pluck('id')->all();

        // 3. Asignación de permisos por rol
        // ADMINISTRADOR: todos los permisos
        if ($adminRole) {
            $adminRole->permissions()->sync($allPermissionIds);
        }

        // GTS: permisos operativos técnicos
        if ($gtsRole) {
            $gtsPermSlugs = [
                'incidents.create',
                'incidents.view_assigned',
                'incidents.view_department',
                'incidents.view_all',
                'incidents.attend',
                'incidents.resolve',
                'incidents.take',
                'incidents.release',
                'incidents.assign',
                'reports.view',
                'reports.export',
            ];
            $gtsPermIds = Permission::whereIn('slug', $gtsPermSlugs)->pluck('id')->all();
            $gtsRole->permissions()->sync($gtsPermIds);
        }

        // APOC-CCO: permisos de reporte, conformidad y monitoreo CCO
        if ($ccoRole) {
            $ccoPermSlugs = [
                'incidents.create',
                'incidents.view_department',
                'incidents.vobo_close',
                'incidents.reopen',
                'reports.view',
                'reports.export',
            ];
            $ccoPermIds = Permission::whereIn('slug', $ccoPermSlugs)->pluck('id')->all();
            $ccoRole->permissions()->sync($ccoPermIds);
        }

        // APOC-CSA: permisos de reporte, conformidad y monitoreo CSA
        if ($csaRole) {
            $csaPermSlugs = [
                'incidents.create',
                'incidents.view_department',
                'incidents.vobo_close',
                'incidents.reopen',
                'reports.view',
                'reports.export',
            ];
            $csaPermIds = Permission::whereIn('slug', $csaPermSlugs)->pluck('id')->all();
            $csaRole->permissions()->sync($csaPermIds);
        }

        // APOC legacy
        if ($apocRole) {
            $apocPermSlugs = [
                'incidents.create',
                'incidents.view_department',
                'incidents.vobo_close',
                'incidents.reopen',
                'reports.view',
                'reports.export',
            ];
            $apocPermIds = Permission::whereIn('slug', $apocPermSlugs)->pluck('id')->all();
            $apocRole->permissions()->sync($apocPermIds);
        }

        // 4. Configuración de alcances operativos (RoleOperationalScope)
        // Limpiar alcances previos para sincronización limpia y consistente
        RoleOperationalScope::query()->delete();

        // ADMINISTRADOR: alcance total sobre todos los roles (view, attend, assign)
        if ($adminRole) {
            foreach ($allRoles as $target) {
                foreach (['view', 'attend', 'assign'] as $type) {
                    RoleOperationalScope::firstOrCreate([
                        'source_role_id' => $adminRole->id,
                        'target_role_id' => $target->id,
                        'scope_type' => $type,
                    ]);
                }
            }
        }

        // GTS:
        // - View: ve incidencias de todos los roles (admin, gts, cco, csa, apoc)
        // - Attend: atiende incidencias de todos los roles
        // - Assign: asigna incidencias a especialistas de su propio rol (gts)
        if ($gtsRole) {
            foreach ($allRoles as $target) {
                RoleOperationalScope::firstOrCreate([
                    'source_role_id' => $gtsRole->id,
                    'target_role_id' => $target->id,
                    'scope_type' => 'view',
                ]);
                RoleOperationalScope::firstOrCreate([
                    'source_role_id' => $gtsRole->id,
                    'target_role_id' => $target->id,
                    'scope_type' => 'attend',
                ]);
            }
            // GTS asigna hacia GTS
            RoleOperationalScope::firstOrCreate([
                'source_role_id' => $gtsRole->id,
                'target_role_id' => $gtsRole->id,
                'scope_type' => 'assign',
            ]);
        }

        // APOC-CCO:
        // - View: incidencias pertenecientes a CCO
        if ($ccoRole) {
            RoleOperationalScope::firstOrCreate([
                'source_role_id' => $ccoRole->id,
                'target_role_id' => $ccoRole->id,
                'scope_type' => 'view',
            ]);
        }

        // APOC-CSA:
        // - View: incidencias pertenecientes a CSA
        if ($csaRole) {
            RoleOperationalScope::firstOrCreate([
                'source_role_id' => $csaRole->id,
                'target_role_id' => $csaRole->id,
                'scope_type' => 'view',
            ]);
        }

        // APOC legacy:
        if ($apocRole) {
            RoleOperationalScope::firstOrCreate([
                'source_role_id' => $apocRole->id,
                'target_role_id' => $apocRole->id,
                'scope_type' => 'view',
            ]);
        }
    }
}
