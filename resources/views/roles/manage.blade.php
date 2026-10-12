<x-app-layout>
    <div 
        x-data="{
            activeTab: 'permissions',
            allPermissionIds: {{ Js::from($role->permissions->pluck('id')->all()) }},
            isAdminRole: {{ $role->slug === 'admin' ? 'true' : 'false' }},
            toggleAllModule(moduleKey, check) {
                const checkboxes = document.querySelectorAll(`input[data-module='${moduleKey}']`);
                checkboxes.forEach(cb => { cb.checked = check; });
            },
            toggleAllScopes(type, check) {
                const checkboxes = document.querySelectorAll(`input[data-scope-type='${type}']`);
                checkboxes.forEach(cb => { cb.checked = check; });
            }
        }"
        class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6"
    >

        <!-- Feedback de Operaciones -->
        @if(session('status_message'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[22px] text-emerald-400">check_circle</span>
                    <span class="font-medium">{{ session('status_message') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[22px] text-rose-400">error</span>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-rose-200">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        @endif

        <!-- Pestañas Superiores de Navegación -->
        <div class="flex items-center gap-2 border-b border-[#263241] pb-1">
            <a 
                href="{{ route('profile.edit') }}" 
                class="px-4 py-2 text-xs font-semibold rounded-t-xl transition-colors text-[#8D98A8] hover:text-[#F2F5F8] hover:bg-[#101923]"
            >
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">account_circle</span>
                    <span>Mi Cuenta</span>
                </span>
            </a>
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('users.view'))
            <a 
                href="{{ route('users.index') }}" 
                class="px-4 py-2 text-xs font-semibold rounded-t-xl transition-colors text-[#8D98A8] hover:text-[#F2F5F8] hover:bg-[#101923]"
            >
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">group</span>
                    <span>Gestión de Usuarios y Cuadrillas</span>
                </span>
            </a>
            @endif
            <a 
                href="{{ route('roles.index') }}" 
                class="px-4 py-2 text-xs font-semibold rounded-t-xl bg-[#11151B] border-t border-x border-[#263241] text-[#3D82C6]"
            >
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
                    <span>Gestión de Roles y Permisos</span>
                </span>
            </a>
        </div>

        <!-- Encabezado de la Pantalla de Configuración -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('dashboard') }}" class="text-xs uppercase tracking-widest text-[#707B8A] hover:text-[#3D82C6] transition-colors">Inicio</a>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <a href="{{ route('roles.index') }}" class="text-xs uppercase tracking-widest text-[#707B8A] hover:text-[#3D82C6] transition-colors">Roles</a>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-[#3D82C6] font-semibold">{{ $role->name }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#F2F5F8] tracking-tight">{{ $role->name }}</h1>
                    @php
                        $badgeClasses = $role->getBadgeClasses();
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold font-mono tracking-wider {{ $badgeClasses['bg'] }} {{ $badgeClasses['text'] }} border {{ $badgeClasses['border'] }}">
                        {{ $role->name }}
                    </span>
                    <span class="font-mono text-xs text-[#707B8A] px-2 py-0.5 rounded bg-[#101923] border border-[#263241]">
                        slug: {{ $role->slug }}
                    </span>
                    @if($role->is_protected)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                            <span class="material-symbols-outlined text-[14px]">lock</span>
                            <span>Protegido por Sistema</span>
                        </span>
                    @endif
                </div>
                <p class="text-xs sm:text-sm text-[#8D98A8] mt-1">
                    {{ $role->description ?: 'Configuración de capacidades operativas y alcances de comunicación.' }}
                </p>
            </div>

            <div class="flex items-center gap-3 self-start md:self-auto">
                <a 
                    href="{{ route('roles.index') }}" 
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#101923] hover:bg-[#1B2735] text-[#8D98A8] hover:text-[#F2F5F8] text-xs font-semibold border border-[#263241] transition-all shadow-sm"
                >
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Volver al Catálogo</span>
                </a>
            </div>
        </div>

        <!-- Banner Explicativo si es ADMINISTRADOR -->
        @if($role->slug === 'admin')
            <div class="p-4 rounded-xl bg-purple-500/10 border border-purple-500/30 text-purple-200 text-xs sm:text-sm flex items-start gap-3 shadow-sm">
                <span class="material-symbols-outlined text-[24px] text-purple-400 shrink-0">admin_panel_settings</span>
                <div class="flex flex-col gap-0.5">
                    <strong class="text-purple-300 font-bold">Bypass de Seguridad Irrestricto para ADMINISTRADOR</strong>
                    <span>
                        Por arquitectura de seguridad, la cuenta con rol <strong>ADMINISTRADOR</strong> posee acceso total a todos los módulos y funciones del sistema, sin importar si algún checkbox se encuentra desmarcado.
                    </span>
                </div>
            </div>
        @endif

        <!-- Pestañas de Navegación de la Configuración del Rol -->
        <div class="flex items-center gap-3 border-b border-[#263241]">
            <button 
                type="button"
                @click="activeTab = 'permissions'"
                :class="activeTab === 'permissions' ? 'text-[#3D82C6] border-b-2 border-[#3D82C6] font-bold' : 'text-[#8D98A8] hover:text-[#F2F5F8] font-medium'"
                class="flex items-center gap-2 py-3 px-2 text-xs sm:text-sm transition-all outline-none"
            >
                <span class="material-symbols-outlined text-[18px]">shield</span>
                <span>Matriz de Permisos Granulares</span>
                <span class="px-2 py-0.2 rounded-full text-[11px] font-mono bg-[#101923] text-[#D6DCE4] border border-[#263241]">
                    {{ count($assignedPermissionIds) }} activos
                </span>
            </button>

            <button 
                type="button"
                @click="activeTab = 'scopes'"
                :class="activeTab === 'scopes' ? 'text-[#3D82C6] border-b-2 border-[#3D82C6] font-bold' : 'text-[#8D98A8] hover:text-[#F2F5F8] font-medium'"
                class="flex items-center gap-2 py-3 px-2 text-xs sm:text-sm transition-all outline-none"
            >
                <span class="material-symbols-outlined text-[18px]">hub</span>
                <span>Matriz de Alcances Operativos (Inter-Rol)</span>
                <span class="px-2 py-0.2 rounded-full text-[11px] font-mono bg-[#101923] text-[#D6DCE4] border border-[#263241]">
                    {{ count($viewScopeRoleIds) + count($attendScopeRoleIds) + count($assignScopeRoleIds) }} reglas
                </span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: MATRIZ DE PERMISOS GRANULARES -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'permissions'" x-cloak class="flex flex-col gap-6">
            
            <form action="{{ route('roles.update-permissions', $role) }}" method="POST" class="flex flex-col gap-6">
                @csrf
                @method('PUT')

                @foreach($modules as $moduleKey => $moduleTitle)
                    @php
                        $modulePerms = $permissionsByModule->get($moduleKey, collect());
                    @endphp
                    @if($modulePerms->isNotEmpty())
                        <div class="bg-[#11151B] border border-[#263241] rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                            <!-- Encabezado del Módulo -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-[#1E2734] gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="material-symbols-outlined text-[20px] text-[#3D82C6]">
                                        @if($moduleKey === 'incidents') confirmation_number
                                        @elseif($moduleKey === 'users') group
                                        @elseif($moduleKey === 'roles') admin_panel_settings
                                        @elseif($moduleKey === 'reports') bar_chart
                                        @elseif($moduleKey === 'audit') manage_history
                                        @elseif($moduleKey === 'dashboard') dashboard
                                        @else folder @endif
                                    </span>
                                    <h2 class="text-sm sm:text-base font-bold text-[#F2F5F8]">{{ $moduleTitle }}</h2>
                                    <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-[#0D1117] text-[#8D98A8] border border-[#263241]">
                                        {{ $modulePerms->count() }} capacidades
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-auto">
                                    <button 
                                        type="button" 
                                        @click="toggleAllModule('{{ $moduleKey }}', true)"
                                        class="px-2.5 py-1 rounded-lg bg-[#0D1117] hover:bg-[#162232] text-[#3D82C6] text-[11px] font-semibold border border-[#263241] transition-colors"
                                    >
                                        Marcar todos
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="toggleAllModule('{{ $moduleKey }}', false)"
                                        class="px-2.5 py-1 rounded-lg bg-[#0D1117] hover:bg-[#162232] text-[#8D98A8] hover:text-[#F2F5F8] text-[11px] font-semibold border border-[#263241] transition-colors"
                                    >
                                        Desmarcar
                                    </button>
                                </div>
                            </div>

                            <!-- Grid de Permisos del Módulo -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($modulePerms as $perm)
                                    @php
                                        $isChecked = in_array($perm->id, $assignedPermissionIds, true);
                                    @endphp
                                    <label class="flex items-start gap-3 p-3 rounded-xl bg-[#0D1117] hover:bg-[#161D26] border border-[#263241] cursor-pointer transition-colors group">
                                        <input 
                                            type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $perm->id }}"
                                            data-module="{{ $moduleKey }}"
                                            {{ $isChecked ? 'checked' : '' }}
                                            class="mt-1 rounded bg-[#11151B] border-[#263241] text-[#123A70] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                        >
                                        <div class="flex flex-col gap-0.5 flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="font-semibold text-xs text-[#F2F5F8] group-hover:text-[#3D82C6] transition-colors">
                                                    {{ $perm->name }}
                                                </span>
                                            </div>
                                            <span class="font-mono text-[10px] text-[#707B8A]">
                                                {{ $perm->slug }}
                                            </span>
                                            @if($perm->description)
                                                <p class="text-[11px] text-[#8D98A8] mt-0.5 leading-tight">
                                                    {{ $perm->description }}
                                                </p>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <!-- Barra Inferior de Guardado -->
                <div class="sticky bottom-4 p-4 rounded-2xl bg-[#11151B]/95 backdrop-blur-md border border-[#263241] flex items-center justify-between shadow-2xl z-30">
                    <div class="text-xs text-[#8D98A8]">
                        Los cambios en permisos se aplican de forma inmediata para los usuarios con rol <strong class="text-[#F2F5F8]">{{ $role->name }}</strong>.
                    </div>
                    <div class="flex items-center gap-3">
                        <a 
                            href="{{ route('roles.index') }}" 
                            class="px-4 py-2 rounded-xl border border-[#263241] bg-[#101923] text-[#8D98A8] hover:text-[#F2F5F8] text-xs font-semibold transition-colors"
                        >
                            Cancelar
                        </a>
                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.manage_permissions'))
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#123A70] hover:bg-[#164A8A] text-white font-semibold text-xs rounded-xl shadow-md transition-all"
                        >
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            <span>Guardar Matriz de Permisos</span>
                        </button>
                        @endif
                    </div>
                </div>

            </form>

        </div>

        <!-- ======================================================= -->
        <!-- TAB 2: MATRIZ DE ALCANCES OPERATIVOS (INTER-ROL) -->
        <!-- ======================================================= -->
        <div x-show="activeTab === 'scopes'" x-cloak class="flex flex-col gap-6">

            <!-- Cuadro de Explicación de Arquitectura de Alcances -->
            <div class="p-4 rounded-2xl bg-[#0D1117] border border-[#263241] flex flex-col sm:flex-row items-start gap-4">
                <div class="p-2.5 rounded-xl bg-[#101923] border border-[#263241] text-[#3D82C6] shrink-0">
                    <span class="material-symbols-outlined text-[28px]">hub</span>
                </div>
                <div class="flex flex-col gap-1 text-xs text-[#8D98A8]">
                    <h3 class="text-sm font-bold text-[#F2F5F8]">Arquitectura de Desacoplamiento Operativo</h3>
                    <p>
                        Esta matriz define las reglas de interacción del rol <strong class="text-[#F2F5F8]">{{ $role->name }}</strong> frente a los demás roles del aeropuerto. Los tres tipos de alcance son <strong class="text-cyan-400">totalmente independientes entre sí</strong>:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2">
                        <div class="p-2.5 rounded-xl bg-[#11151B] border border-[#1D2530]">
                            <strong class="text-[#93C5FD] block mb-0.5">1. Visualización (Ver)</strong>
                            <span>Determina qué incidencias aparecen en listados, tableros y búsquedas del usuario.</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-[#11151B] border border-[#1D2530]">
                            <strong class="text-[#A78BFA] block mb-0.5">2. Atención (Atender)</strong>
                            <span>Determina de qué roles puede iniciar diagnóstico, tomar o registrar solución técnica.</span>
                        </div>
                        <div class="p-2.5 rounded-xl bg-[#11151B] border border-[#1D2530]">
                            <strong class="text-[#FBBF24] block mb-0.5">3. Asignación (Asignar)</strong>
                            <span>Determina hacia qué roles puede transferir o asignar tickets el usuario.</span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('roles.update-scopes', $role) }}" method="POST" class="flex flex-col gap-6">
                @csrf
                @method('PUT')

                <!-- ALCANCE 1: ¿QUÉ INCIDENCIAS PUEDE VISUALIZAR ESTE ROL? -->
                <div class="bg-[#11151B] border border-[#263241] rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-[#1E2734] gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[22px] text-blue-400">visibility</span>
                            <div>
                                <h3 class="text-sm font-bold text-[#F2F5F8]">1. Alcance de Visualización</h3>
                                <p class="text-[11px] text-[#8D98A8]">¿Incidencias creadas por qué roles puede ver el usuario con rol {{ $role->name }}?</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            <button 
                                type="button" 
                                @click="toggleAllScopes('view', true)"
                                class="px-2.5 py-1 rounded-lg bg-[#0D1117] hover:bg-[#162232] text-[#3D82C6] text-[11px] font-semibold border border-[#263241] transition-colors"
                            >
                                Seleccionar todos
                            </button>
                            <button 
                                type="button" 
                                @click="toggleAllScopes('view', false)"
                                class="px-2.5 py-1 rounded-lg bg-[#0D1117] hover:bg-[#162232] text-[#8D98A8] hover:text-[#F2F5F8] text-[11px] font-semibold border border-[#263241] transition-colors"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($allRoles as $targetRole)
                            @php
                                $isViewChecked = in_array($targetRole->id, $viewScopeRoleIds, true);
                                $tBadge = $targetRole->getBadgeClasses();
                            @endphp
                            <label class="flex items-center justify-between p-3 rounded-xl bg-[#0D1117] hover:bg-[#161D26] border border-[#263241] cursor-pointer transition-colors group">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input 
                                        type="checkbox" 
                                        name="view_scopes[]" 
                                        value="{{ $targetRole->id }}"
                                        data-scope-type="view"
                                        {{ $isViewChecked ? 'checked' : '' }}
                                        class="rounded bg-[#11151B] border-[#263241] text-[#123A70] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    >
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-semibold text-xs text-[#F2F5F8] truncate group-hover:text-[#3D82C6]">
                                            {{ $targetRole->name }}
                                        </span>
                                        <span class="font-mono text-[10px] text-[#707B8A]">
                                            {{ $targetRole->slug }}
                                        </span>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold font-mono tracking-wider {{ $tBadge['bg'] }} {{ $tBadge['text'] }} border {{ $tBadge['border'] }} shrink-0">
                                    {{ $targetRole->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- ALCANCE 2: ¿QUÉ INCIDENCIAS PUEDE ATENDER ESTE ROL? -->
                <div class="bg-[#11151B] border border-[#263241] rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-[#1E2734] gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[22px] text-purple-400">build</span>
                            <div>
                                <h3 class="text-sm font-bold text-[#F2F5F8]">2. Alcance de Atención Técnica</h3>
                                <p class="text-[11px] text-[#8D98A8]">¿Incidencias originadas por qué roles puede atender, diagnosticar y resolver este rol?</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            <button 
                                type="button" 
                                @click="toggleAllScopes('attend', true)"
                                class="px-2.5 py-1 rounded-lg bg-[#0D1117] hover:bg-[#162232] text-[#3D82C6] text-[11px] font-semibold border border-[#263241] transition-colors"
                            >
                                Seleccionar todos
                            </button>
                            <button 
                                type="button" 
                                @click="toggleAllScopes('attend', false)"
                                class="px-2.5 py-1 rounded-lg bg-[#0D1117] hover:bg-[#162232] text-[#8D98A8] hover:text-[#F2F5F8] text-[11px] font-semibold border border-[#263241] transition-colors"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($allRoles as $targetRole)
                            @php
                                $isAttendChecked = in_array($targetRole->id, $attendScopeRoleIds, true);
                                $tBadge = $targetRole->getBadgeClasses();
                            @endphp
                            <label class="flex items-center justify-between p-3 rounded-xl bg-[#0D1117] hover:bg-[#161D26] border border-[#263241] cursor-pointer transition-colors group">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input 
                                        type="checkbox" 
                                        name="attend_scopes[]" 
                                        value="{{ $targetRole->id }}"
                                        data-scope-type="attend"
                                        {{ $isAttendChecked ? 'checked' : '' }}
                                        class="rounded bg-[#11151B] border-[#263241] text-[#123A70] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    >
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-semibold text-xs text-[#F2F5F8] truncate group-hover:text-purple-300">
                                            {{ $targetRole->name }}
                                        </span>
                                        <span class="font-mono text-[10px] text-[#707B8A]">
                                            {{ $targetRole->slug }}
                                        </span>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold font-mono tracking-wider {{ $tBadge['bg'] }} {{ $tBadge['text'] }} border {{ $tBadge['border'] }} shrink-0">
                                    {{ $targetRole->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- ALCANCE 3: ¿A QUÉ ROLES PUEDE ASIGNAR INCIDENCIAS ESTE ROL? -->
                <div class="bg-[#11151B] border border-[#263241] rounded-2xl p-5 shadow-sm flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-[#1E2734] gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-[22px] text-amber-400">person_add</span>
                            <div>
                                <h3 class="text-sm font-bold text-[#F2F5F8]">3. Alcance de Asignación</h3>
                                <p class="text-[11px] text-[#8D98A8]">¿Hacia usuarios de qué roles puede este perfil delegar o asignar incidencias?</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            <button 
                                type="button" 
                                @click="toggleAllScopes('assign', true)"
                                class="px-2.5 py-1 rounded-lg bg-[#0D1117] hover:bg-[#162232] text-[#3D82C6] text-[11px] font-semibold border border-[#263241] transition-colors"
                            >
                                Seleccionar todos
                            </button>
                            <button 
                                type="button" 
                                @click="toggleAllScopes('assign', false)"
                                class="px-2.5 py-1 rounded-lg bg-[#0D1117] hover:bg-[#162232] text-[#8D98A8] hover:text-[#F2F5F8] text-[11px] font-semibold border border-[#263241] transition-colors"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach($allRoles as $targetRole)
                            @php
                                $isAssignChecked = in_array($targetRole->id, $assignScopeRoleIds, true);
                                $tBadge = $targetRole->getBadgeClasses();
                            @endphp
                            <label class="flex items-center justify-between p-3 rounded-xl bg-[#0D1117] hover:bg-[#161D26] border border-[#263241] cursor-pointer transition-colors group">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input 
                                        type="checkbox" 
                                        name="assign_scopes[]" 
                                        value="{{ $targetRole->id }}"
                                        data-scope-type="assign"
                                        {{ $isAssignChecked ? 'checked' : '' }}
                                        class="rounded bg-[#11151B] border-[#263241] text-[#123A70] focus:ring-0 focus:ring-offset-0 cursor-pointer"
                                    >
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-semibold text-xs text-[#F2F5F8] truncate group-hover:text-amber-300">
                                            {{ $targetRole->name }}
                                        </span>
                                        <span class="font-mono text-[10px] text-[#707B8A]">
                                            {{ $targetRole->slug }}
                                        </span>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold font-mono tracking-wider {{ $tBadge['bg'] }} {{ $tBadge['text'] }} border {{ $tBadge['border'] }} shrink-0">
                                    {{ $targetRole->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Barra Inferior de Guardado -->
                <div class="sticky bottom-4 p-4 rounded-2xl bg-[#11151B]/95 backdrop-blur-md border border-[#263241] flex items-center justify-between shadow-2xl z-30">
                    <div class="text-xs text-[#8D98A8]">
                        Las reglas de alcance delimitan automáticamente los filtros de visualización y asignación del rol <strong class="text-[#F2F5F8]">{{ $role->name }}</strong>.
                    </div>
                    <div class="flex items-center gap-3">
                        <a 
                            href="{{ route('roles.index') }}" 
                            class="px-4 py-2 rounded-xl border border-[#263241] bg-[#101923] text-[#8D98A8] hover:text-[#F2F5F8] text-xs font-semibold transition-colors"
                        >
                            Cancelar
                        </a>
                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.manage_scopes'))
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#123A70] hover:bg-[#164A8A] text-white font-semibold text-xs rounded-xl shadow-md transition-all"
                        >
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            <span>Guardar Matriz de Alcances</span>
                        </button>
                        @endif
                    </div>
                </div>

            </form>

        </div>

    </div>
</x-app-layout>

