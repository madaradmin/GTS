<x-app-layout>
    <div 
        x-data="{
            showCreateModal: false,
            showEditModal: false,
            showDeleteModal: false,
            createRole: {
                name: '',
                slug: '',
                description: '',
                color: 'emerald'
            },
            editRole: {
                id: null,
                name: '',
                slug: '',
                description: '',
                color: 'slate',
                is_protected: false,
                actionUrl: ''
            },
            deleteRole: {
                id: null,
                name: '',
                usersCount: 0,
                actionUrl: ''
            },
            presets: {{ Js::from($colorPresets) }},
            generateSlug(name) {
                return name
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            },
            openEdit(role, updateUrl) {
                this.editRole = {
                    id: role.id,
                    name: role.name,
                    slug: role.slug,
                    description: role.description || '',
                    color: role.color || 'slate',
                    is_protected: Boolean(role.is_protected),
                    actionUrl: updateUrl
                };
                this.showEditModal = true;
            },
            openDelete(id, name, deleteUrl, usersCount = 0) {
                this.deleteRole = { id: id, name: name, usersCount: usersCount, actionUrl: deleteUrl };
                this.showDeleteModal = true;
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

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm flex flex-col gap-1 shadow-lg">
                <div class="flex items-center gap-2 font-bold text-rose-400">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span>Por favor corrija los siguientes errores:</span>
                </div>
                <ul class="list-disc list-inside pl-2 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Pestañas de Navegación de Configuración -->
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

        <!-- Encabezado y Acción de Alta -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('dashboard') }}" class="text-xs uppercase tracking-widest text-[#707B8A] hover:text-[#3D82C6] transition-colors">Inicio</a>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <a href="{{ route('profile.edit') }}" class="text-xs uppercase tracking-widest text-[#707B8A] hover:text-[#3D82C6] transition-colors">Configuración</a>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-[#3D82C6] font-semibold">Roles</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#F2F5F8] tracking-tight">Gestión de Roles y Permisos</h1>
                    <span class="px-2.5 py-0.5 rounded bg-[#101923] text-[#A78BFA] font-mono text-xs font-semibold uppercase tracking-wider border border-[#263241] flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">shield</span>
                        <span>Matriz Dinámica</span>
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-[#8D98A8] mt-1">
                    Definición de perfiles operativos, capacidades granulares del sistema y alcances de interacción inter-rol (ver, atender y asignar).
                </p>
            </div>

            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.create'))
            <div class="flex items-center gap-3 self-start md:self-auto">
                <button 
                    type="button" 
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#123A70] hover:bg-[#164A8A] text-white font-semibold text-xs rounded-xl shadow-md transition-all group"
                >
                    <span class="material-symbols-outlined text-[18px]">add_moderator</span>
                    <span>Nuevo Rol</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Tarjetas de Resumen de Roles y Permisos -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Total Roles</span>
                <span class="text-xl font-bold font-mono text-[#F2F5F8] mt-1">{{ $metrics['total'] }}</span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Roles de Sistema</span>
                <span class="text-xl font-bold font-mono text-cyan-400 mt-1">{{ $metrics['protected'] }}</span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Roles Personalizados</span>
                <span class="text-xl font-bold font-mono text-emerald-400 mt-1">{{ $metrics['custom'] }}</span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Usuarios Asignados</span>
                <span class="text-xl font-bold font-mono text-purple-400 mt-1">{{ $metrics['total_users'] }}</span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Permisos Disponibles</span>
                <span class="text-xl font-bold font-mono text-amber-400 mt-1">{{ $metrics['total_permissions'] }}</span>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros -->
        <div class="bg-[#11151B] border border-[#263241] p-4 rounded-xl shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
            <form action="{{ route('roles.index') }}" method="GET" class="w-full sm:max-w-md relative flex items-center">
                <span class="absolute left-3 text-[#707B8A] pointer-events-none">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                </span>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Buscar rol por nombre, slug o descripción..."
                    class="w-full pl-9 pr-8 py-2 bg-[#0D1117] border border-[#263241] rounded-xl text-xs text-[#F2F5F8] placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6] transition-colors"
                >
                @if(request('search'))
                    <a href="{{ route('roles.index') }}" class="absolute right-2.5 text-[#707B8A] hover:text-[#F2F5F8]">
                        <span class="material-symbols-outlined text-[16px]">close</span>
                    </a>
                @endif
            </form>

            <div class="text-xs text-[#707B8A]">
                Mostrando <span class="font-bold text-[#F2F5F8]">{{ $roles->count() }}</span> rol(es) configurado(s)
            </div>
        </div>

        <!-- Tabla de Roles -->
        <div class="bg-[#11151B] border border-[#263241] rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#0D1117] text-[#8D98A8] uppercase tracking-wider text-[10px] border-b border-[#263241]">
                        <tr>
                            <th class="py-3 px-4 font-semibold">Rol / Identificador</th>
                            <th class="py-3 px-4 font-semibold text-center">Distintivo Táctico</th>
                            <th class="py-3 px-4 font-semibold text-center">Tipo</th>
                            <th class="py-3 px-4 font-semibold text-center">Usuarios</th>
                            <th class="py-3 px-4 font-semibold text-center">Permisos</th>
                            <th class="py-3 px-4 font-semibold text-center">Alcances</th>
                            <th class="py-3 px-4 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1D2530] text-[#D6DCE4]">
                        @forelse($roles as $role)
                            <tr class="hover:bg-[#161D26] transition-colors">
                                <!-- Rol / Identificador -->
                                <td class="py-3 px-4">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm text-[#F2F5F8]">{{ $role->name }}</span>
                                            <span class="font-mono text-[11px] text-[#707B8A] px-1.5 py-0.2 rounded bg-[#0D1117] border border-[#263241]">
                                                {{ $role->slug }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-[#8D98A8] line-clamp-1 mt-0.5">
                                            {{ $role->description ?: 'Sin descripción registrada' }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Distintivo Táctico -->
                                <td class="py-3 px-4 text-center">
                                    @php
                                        $badgeClasses = $role->getBadgeClasses();
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono tracking-wider {{ $badgeClasses['bg'] }} {{ $badgeClasses['text'] }} border {{ $badgeClasses['border'] }}">
                                        {{ $role->name }}
                                    </span>
                                </td>

                                <!-- Tipo (Protegido / Personalizado) -->
                                <td class="py-3 px-4 text-center">
                                    @if($role->isProtectedFromDeletion())
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-medium bg-cyan-500/10 text-cyan-400 border border-cyan-500/20" title="Rol esencial de Administrador protegido contra eliminación">
                                            <span class="material-symbols-outlined text-[13px]">lock</span>
                                            <span>Sistema</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20" title="Rol eliminable">
                                            <span class="material-symbols-outlined text-[13px]">tune</span>
                                            <span>Operativo</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Usuarios Asignados -->
                                <td class="py-3 px-4 text-center font-mono">
                                    <span class="px-2 py-0.5 rounded {{ $role->users_count > 0 ? 'bg-[#101923] text-[#3D82C6] font-bold border border-[#263241]' : 'text-[#707B8A]' }}">
                                        {{ $role->users_count }}
                                    </span>
                                </td>

                                <!-- Permisos Asignados -->
                                <td class="py-3 px-4 text-center font-mono">
                                    @if($role->slug === 'admin')
                                        <span class="px-2 py-0.5 rounded bg-purple-500/10 text-purple-400 border border-purple-500/20 font-semibold" title="Bypass Total de Permisos">
                                            TODOS (Bypass)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-[#101923] text-[#D6DCE4] border border-[#263241]">
                                            {{ $role->permissions_count }} / {{ $metrics['total_permissions'] }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Alcances Operativos -->
                                <td class="py-3 px-4 text-center font-mono">
                                    <span class="px-2 py-0.5 rounded bg-[#101923] text-[#D6DCE4] border border-[#263241]">
                                        {{ $role->operational_scopes_count }} regla(s)
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5 justify-end">
                                        <!-- Configurar Permisos y Alcances -->
                                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.manage_permissions') || auth()->user()->hasPermission('roles.manage_scopes'))
                                        <a 
                                            href="{{ route('roles.manage', $role) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-[#123A70]/60 hover:bg-[#164A8A] text-white text-[11px] font-semibold border border-[#164A8A]/50 transition-colors shadow-sm"
                                            title="Configurar Permisos y Alcances Operativos"
                                        >
                                            <span class="material-symbols-outlined text-[15px]">security</span>
                                            <span class="hidden sm:inline">Permisos y Alcances</span>
                                        </a>
                                        @endif

                                        <!-- Editar Rol -->
                                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.edit'))
                                        <button 
                                            type="button"
                                            @click="openEdit({{ Js::from($role) }}, '{{ route('roles.update', $role) }}')"
                                            class="p-1.5 rounded-lg bg-[#101923] hover:bg-[#1B2735] text-[#8D98A8] hover:text-[#F2F5F8] border border-[#263241] transition-colors"
                                            title="Editar información básica y apariencia del rol"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </button>
                                        @endif

                                        <!-- Eliminar Rol -->
                                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.delete'))
                                            @if($role->isProtectedFromDeletion())
                                                <button 
                                                    type="button"
                                                    disabled
                                                    class="p-1.5 rounded-lg bg-[#101923]/40 text-[#566170] border border-[#1D2530] cursor-not-allowed opacity-50"
                                                    title="Rol de Administrador protegido: no se puede eliminar"
                                                >
                                                    <span class="material-symbols-outlined text-[16px]">lock</span>
                                                </button>
                                            @else
                                                <button 
                                                    type="button"
                                                    @click="openDelete({{ $role->id }}, '{{ addslashes($role->name) }}', '{{ route('roles.destroy', $role) }}', {{ $role->users_count }})"
                                                    class="p-1.5 rounded-lg bg-[#101923] hover:bg-rose-950/40 text-[#8D98A8] hover:text-rose-400 border border-[#263241] hover:border-rose-900/50 transition-colors"
                                                    title="Eliminar rol"
                                                >
                                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-[#707B8A]">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-[32px] text-[#566170]">shield_question</span>
                                        <span>No se encontraron roles que coincidan con la búsqueda.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL: Crear Nuevo Rol -->
        <div 
            x-show="showCreateModal" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        >
            <div 
                @click.away="showCreateModal = false"
                class="bg-[#11151B] border border-[#263241] rounded-2xl w-full max-w-lg p-6 shadow-2xl flex flex-col gap-5 text-xs text-[#D6DCE4]"
            >
                <div class="flex items-center justify-between border-b border-[#263241] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-[#3D82C6]">add_moderator</span>
                        <h2 class="text-base font-bold text-[#F2F5F8]">Crear Nuevo Rol Operativo</h2>
                    </div>
                    <button type="button" @click="showCreateModal = false" class="text-[#707B8A] hover:text-[#F2F5F8]">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form action="{{ route('roles.store') }}" method="POST" class="flex flex-col gap-4" autocomplete="off">
                    @csrf

                    <!-- Nombre del Rol -->
                    <div class="flex flex-col gap-1">
                        <label for="create_name" class="font-semibold text-[#8D98A8]">Nombre del Rol *</label>
                        <input 
                            type="text" 
                            id="create_name" 
                            name="name" 
                            x-model="createRole.name"
                            @input="if(!createRole.slugTouched) { createRole.slug = generateSlug(createRole.name) }"
                            required 
                            placeholder="Ej. Soporte TICS, Supervisión Operativa"
                            class="px-3 py-2 bg-[#0D1117] border border-[#263241] rounded-xl text-xs text-[#F2F5F8] focus:outline-none focus:border-[#3D82C6]"
                        >
                    </div>

                    <!-- Slug / Identificador -->
                    <div class="flex flex-col gap-1">
                        <label for="create_slug" class="font-semibold text-[#8D98A8]">
                            Identificador Único (Slug) *
                            <span class="font-normal text-[11px] text-[#707B8A]">(solo minúsculas y guiones)</span>
                        </label>
                        <input 
                            type="text" 
                            id="create_slug" 
                            name="slug" 
                            x-model="createRole.slug"
                            @input="createRole.slugTouched = true; createRole.slug = generateSlug(createRole.slug)"
                            required 
                            placeholder="soporte-tics"
                            class="px-3 py-2 font-mono bg-[#0D1117] border border-[#263241] rounded-xl text-xs text-[#F2F5F8] focus:outline-none focus:border-[#3D82C6]"
                        >
                    </div>

                    <!-- Descripción -->
                    <div class="flex flex-col gap-1">
                        <label for="create_description" class="font-semibold text-[#8D98A8]">Descripción Funcional</label>
                        <textarea 
                            id="create_description" 
                            name="description" 
                            x-model="createRole.description"
                            rows="2"
                            placeholder="Propósito operacional de este perfil dentro del flujo de incidencias..."
                            class="px-3 py-2 bg-[#0D1117] border border-[#263241] rounded-xl text-xs text-[#F2F5F8] focus:outline-none focus:border-[#3D82C6]"
                        ></textarea>
                    </div>

                    <!-- Color / Insignia Táctica -->
                    <div class="flex flex-col gap-2">
                        <label class="font-semibold text-[#8D98A8]">Color e Insignia Táctica *</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="(data, key) in presets" :key="key">
                                <label 
                                    :class="createRole.color === key ? 'border-[#3D82C6] bg-[#162232]' : 'border-[#263241] bg-[#0D1117] hover:bg-[#161D26]'"
                                    class="flex items-center gap-2 p-2 rounded-xl border cursor-pointer transition-colors"
                                >
                                    <input type="radio" name="color" :value="key" x-model="createRole.color" class="hidden">
                                    <span 
                                        :class="data.bg + ' ' + data.text + ' border ' + data.border"
                                        class="w-3.5 h-3.5 rounded-full inline-block shrink-0"
                                    ></span>
                                    <span class="text-[11px] font-medium truncate" x-text="data.label.split(' ')[0]"></span>
                                </label>
                            </template>
                        </div>

                        <!-- Preview del Distintivo -->
                        <div class="p-2.5 rounded-xl bg-[#0D1117] border border-[#263241] flex items-center justify-between mt-1">
                            <span class="text-[11px] text-[#707B8A]">Vista previa del distintivo:</span>
                            <span 
                                :class="presets[createRole.color]?.bg + ' ' + presets[createRole.color]?.text + ' border ' + presets[createRole.color]?.border"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono tracking-wider"
                                x-text="createRole.name || 'NUEVO ROL'"
                            ></span>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#263241]">
                        <button 
                            type="button" 
                            @click="showCreateModal = false"
                            class="px-4 py-2 rounded-xl border border-[#263241] bg-[#101923] text-[#8D98A8] hover:text-[#F2F5F8] transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-[#123A70] hover:bg-[#164A8A] text-white font-semibold transition-all shadow-md"
                        >
                            Crear y Configurar Permisos
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Editar Rol -->
        <div 
            x-show="showEditModal" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        >
            <div 
                @click.away="showEditModal = false"
                class="bg-[#11151B] border border-[#263241] rounded-2xl w-full max-w-lg p-6 shadow-2xl flex flex-col gap-5 text-xs text-[#D6DCE4]"
            >
                <div class="flex items-center justify-between border-b border-[#263241] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-[#3D82C6]">edit_note</span>
                        <h2 class="text-base font-bold text-[#F2F5F8]">
                            Editar Rol: <span x-text="editRole.name"></span>
                        </h2>
                    </div>
                    <button type="button" @click="showEditModal = false" class="text-[#707B8A] hover:text-[#F2F5F8]">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form :action="editRole.actionUrl" method="POST" class="flex flex-col gap-4" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <!-- Nombre del Rol -->
                    <div class="flex flex-col gap-1">
                        <label for="edit_name" class="font-semibold text-[#8D98A8]">Nombre del Rol *</label>
                        <input 
                            type="text" 
                            id="edit_name" 
                            name="name" 
                            x-model="editRole.name"
                            required 
                            class="px-3 py-2 bg-[#0D1117] border border-[#263241] rounded-xl text-xs text-[#F2F5F8] focus:outline-none focus:border-[#3D82C6]"
                        >
                    </div>

                    <!-- Slug / Identificador -->
                    <div class="flex flex-col gap-1">
                        <label for="edit_slug" class="font-semibold text-[#8D98A8]">Identificador Único (Slug)</label>
                        <template x-if="editRole.is_protected">
                            <div>
                                <input 
                                    type="text" 
                                    id="edit_slug" 
                                    :value="editRole.slug" 
                                    disabled
                                    class="w-full px-3 py-2 font-mono bg-[#0D1117]/50 border border-[#1D2530] rounded-xl text-xs text-[#707B8A] cursor-not-allowed"
                                >
                                <span class="text-[10px] text-cyan-400 mt-1 block">
                                    El identificador de los roles del sistema está protegido para preservar la integridad del software.
                                </span>
                            </div>
                        </template>
                        <template x-if="!editRole.is_protected">
                            <input 
                                type="text" 
                                id="edit_slug" 
                                name="slug" 
                                x-model="editRole.slug"
                                @input="editRole.slug = generateSlug(editRole.slug)"
                                required
                                class="px-3 py-2 font-mono bg-[#0D1117] border border-[#263241] rounded-xl text-xs text-[#F2F5F8] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </template>
                    </div>

                    <!-- Descripción -->
                    <div class="flex flex-col gap-1">
                        <label for="edit_description" class="font-semibold text-[#8D98A8]">Descripción Funcional</label>
                        <textarea 
                            id="edit_description" 
                            name="description" 
                            x-model="editRole.description"
                            rows="2"
                            class="px-3 py-2 bg-[#0D1117] border border-[#263241] rounded-xl text-xs text-[#F2F5F8] focus:outline-none focus:border-[#3D82C6]"
                        ></textarea>
                    </div>

                    <!-- Color / Insignia Táctica -->
                    <div class="flex flex-col gap-2">
                        <label class="font-semibold text-[#8D98A8]">Color e Insignia Táctica *</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="(data, key) in presets" :key="key">
                                <label 
                                    :class="editRole.color === key ? 'border-[#3D82C6] bg-[#162232]' : 'border-[#263241] bg-[#0D1117] hover:bg-[#161D26]'"
                                    class="flex items-center gap-2 p-2 rounded-xl border cursor-pointer transition-colors"
                                >
                                    <input type="radio" name="color" :value="key" x-model="editRole.color" class="hidden">
                                    <span 
                                        :class="data.bg + ' ' + data.text + ' border ' + data.border"
                                        class="w-3.5 h-3.5 rounded-full inline-block shrink-0"
                                    ></span>
                                    <span class="text-[11px] font-medium truncate" x-text="data.label.split(' ')[0]"></span>
                                </label>
                            </template>
                        </div>

                        <!-- Preview del Distintivo -->
                        <div class="p-2.5 rounded-xl bg-[#0D1117] border border-[#263241] flex items-center justify-between mt-1">
                            <span class="text-[11px] text-[#707B8A]">Vista previa del distintivo:</span>
                            <span 
                                :class="presets[editRole.color]?.bg + ' ' + presets[editRole.color]?.text + ' border ' + presets[editRole.color]?.border"
                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono tracking-wider"
                                x-text="editRole.name || 'ROL'"
                            ></span>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#263241]">
                        <button 
                            type="button" 
                            @click="showEditModal = false"
                            class="px-4 py-2 rounded-xl border border-[#263241] bg-[#101923] text-[#8D98A8] hover:text-[#F2F5F8] transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-[#123A70] hover:bg-[#164A8A] text-white font-semibold transition-all shadow-md"
                        >
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: Confirmar Eliminación -->
        <div 
            x-show="showDeleteModal" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        >
            <div 
                @click.away="showDeleteModal = false"
                class="bg-[#11151B] border border-rose-900/50 rounded-2xl w-full max-w-md p-6 shadow-2xl flex flex-col gap-4 text-xs text-[#D6DCE4]"
            >
                <div class="flex items-center gap-3 text-rose-400">
                    <span class="material-symbols-outlined text-[26px]">warning</span>
                    <h2 class="text-base font-bold text-[#F2F5F8]">Eliminar Rol</h2>
                </div>

                <p class="text-xs text-[#8D98A8]">
                    ¿Está seguro de que desea eliminar permanentemente el rol <strong class="text-[#F2F5F8]" x-text="deleteRole.name"></strong>? Esta acción revocará todos sus permisos y reglas de alcance operativo.
                </p>

                <template x-if="deleteRole.usersCount > 0">
                    <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px] flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-[18px] text-amber-400 shrink-0">group</span>
                        <span>
                            Este rol tiene <strong class="text-amber-200" x-text="deleteRole.usersCount"></strong> usuario(s) asignado(s). Al eliminarlo, dichos usuarios quedarán desvinculados del rol, preservando intactas sus cuentas, accesos e incidencias históricas.
                        </span>
                    </div>
                </template>

                <form :action="deleteRole.actionUrl" method="POST" class="flex items-center justify-end gap-3 mt-2">
                    @csrf
                    @method('DELETE')
                    <button 
                        type="button" 
                        @click="showDeleteModal = false"
                        class="px-4 py-2 rounded-xl border border-[#263241] bg-[#101923] text-[#8D98A8] hover:text-[#F2F5F8] transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold transition-all shadow-md"
                    >
                        Eliminar Definitivamente
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>

