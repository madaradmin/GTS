<x-app-layout>
    <div 
        x-data="{
            showCreateModal: false,
            showEditModal: false,
            showDeleteModal: false,
            editUser: { id: null, name: '', username: '', email: '', role_id: '', badge_number: '' },
            deleteUser: { id: null, name: '', actionUrl: '' },
            openEdit(user) {
                this.editUser = { ...user };
                this.showEditModal = true;
            },
            openDelete(id, name, url) {
                this.deleteUser = { id: id, name: name, actionUrl: url };
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
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm flex flex-col gap-1">
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
            <a 
                href="{{ route('users.index') }}" 
                class="px-4 py-2 text-xs font-semibold rounded-t-xl bg-[#11151B] border-t border-x border-[#263241] text-[#3D82C6]"
            >
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">group</span>
                    <span>Gestión de Usuarios y Cuadrillas</span>
                </span>
            </a>
            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.view'))
            <a 
                href="{{ route('roles.index') }}" 
                class="px-4 py-2 text-xs font-semibold rounded-t-xl transition-colors text-[#8D98A8] hover:text-[#F2F5F8] hover:bg-[#101923]"
            >
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
                    <span>Gestión de Roles y Permisos</span>
                </span>
            </a>
            @endif
        </div>

        <!-- Encabezado y Acción de Alta -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('dashboard') }}" class="text-xs uppercase tracking-widest text-[#707B8A] hover:text-[#3D82C6] transition-colors">Inicio</a>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <a href="{{ route('profile.edit') }}" class="text-xs uppercase tracking-widest text-[#707B8A] hover:text-[#3D82C6] transition-colors">Configuración</a>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-[#3D82C6] font-semibold">Usuarios</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#F2F5F8] tracking-tight">Gestión de Usuarios y Cuadrillas</h1>
                    <span class="px-2.5 py-0.5 rounded bg-[#101923] text-[#3D82C6] font-mono text-xs font-semibold uppercase tracking-wider border border-[#263241] flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">admin_panel_settings</span>
                        <span>Control de Cuentas</span>
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-[#8D98A8] mt-1">
                    Administración de personal operativo APOC, especialistas técnicos GTS y administradores del sistema bajo norma aeronáutica.
                </p>
            </div>

            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('users.create'))
            <div class="flex items-center gap-3 self-start md:self-auto">
                <button 
                    type="button" 
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#123A70] hover:bg-[#164A8A] text-white font-semibold text-xs rounded-xl shadow-md transition-all group"
                >
                    <span class="material-symbols-outlined text-[18px]">person_add</span>
                    <span>Registrar Usuario</span>
                </button>
            </div>
            @endif
        </div>

        <!-- Tarjetas de Resumen de Usuarios -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Total Usuarios</span>
                <span class="text-xl font-bold font-mono text-[#F2F5F8] mt-1">{{ $metrics['total'] }}</span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Activos</span>
                <span class="text-xl font-bold font-mono text-emerald-400 mt-1">{{ $metrics['active'] }}</span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Inactivos</span>
                <span class="text-xl font-bold font-mono text-[#8D98A8] mt-1">{{ $metrics['inactive'] }}</span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">GTS Técnicos</span>
                <span class="text-xl font-bold font-mono text-cyan-400 mt-1">{{ $metrics['gts'] }}</span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">APOC (CCO / CSA)</span>
                <span class="text-xl font-bold font-mono text-amber-400 mt-1">
                    {{ $metrics['apoc_cco'] }} <span class="text-[11px] text-[#8D98A8] font-sans">CCO</span> · {{ $metrics['apoc_csa'] }} <span class="text-[11px] text-[#8D98A8] font-sans">CSA</span>
                </span>
            </div>
            <div class="bg-[#11151B] border border-[#263241] p-3 rounded-xl flex flex-col shadow-sm">
                <span class="text-[10px] uppercase tracking-wider text-[#8D98A8] font-bold">Administradores</span>
                <span class="text-xl font-bold font-mono text-purple-400 mt-1">{{ $metrics['admin'] }}</span>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros -->
        <div class="bg-[#11151B] border border-[#263241] p-4 rounded-2xl shadow-sm">
            <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center" autocomplete="off">
                <div class="lg:col-span-5 relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[#707B8A] text-[18px] pointer-events-none">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Buscar por nombre, email o placa..."
                        class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl pl-10 pr-3.5 py-2 text-xs text-[#DDE3EA] placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6]"
                    >
                </div>

                <div class="lg:col-span-3">
                    <select 
                        name="role_id" 
                        class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                    >
                        <option value="">Todos los roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ ($filters['role_id'] ?? '') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select 
                        name="is_active" 
                        class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                    >
                        <option value="">Estado: Todos</option>
                        <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Solo Activos</option>
                        <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Solo Inactivos</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-center gap-2">
                    <button 
                        type="submit" 
                        class="flex-1 px-3 py-2 bg-[#1E293B] hover:bg-[#334155] text-[#DDE3EA] rounded-xl text-xs font-semibold transition-colors text-center"
                    >
                        Filtrar
                    </button>
                    @if(!empty($filters['search']) || !empty($filters['role_id']) || isset($filters['is_active']))
                        <a 
                            href="{{ route('users.index') }}" 
                            class="p-2 bg-[#1E293B] hover:bg-[#334155] text-[#707B8A] hover:text-[#DDE3EA] rounded-xl transition-colors"
                            title="Limpiar filtros"
                        >
                            <span class="material-symbols-outlined text-[18px]">filter_alt_off</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="bg-[#11151B] border border-[#263241] rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 bg-[#0B0F14] border-b border-[#263241] flex items-center justify-between text-xs">
                <span class="font-bold text-[#F2F5F8] flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-[#3D82C6]">badge</span>
                    <span>Usuarios Registrados ({{ $users->total() }})</span>
                </span>
                <span class="text-[#707B8A] text-[11px]">Control de acceso basado en roles RBAC</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#101923] border-b border-[#263241] text-[#8D98A8] text-[11px] font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Usuario</th>
                            <th class="py-3 px-4">Placa / Credencial</th>
                            <th class="py-3 px-4">Rol Institucional</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1F2937] text-xs text-[#DDE3EA]">
                        @forelse($users as $user)
                            <tr class="hover:bg-[#151B23] transition-colors {{ !$user->is_active ? 'opacity-60 bg-black/20' : '' }}">
                                
                                <!-- Nombre y Usuario / Email -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-[#1E293B] border border-[#263241] flex items-center justify-center font-bold text-xs text-[#3D82C6] shrink-0">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-[#F2F5F8] flex items-center gap-1.5">
                                                <span>{{ $user->name }}</span>
                                                @if($user->id === auth()->id())
                                                    <span class="px-1.5 py-0.2 bg-[#3D82C6]/20 text-[#3D82C6] rounded text-[9px] font-semibold">TÚ</span>
                                                @endif
                                            </span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-mono text-[#3D82C6]">@<span>{{ $user->username }}</span></span>
                                                @if($user->email)
                                                    <span class="text-[10px] text-[#707B8A]">({{ $user->email }})</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Placa / Credencial -->
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs text-[#A8B0BC]">
                                    {{ $user->badge_number ?: '—' }}
                                </td>

                                <!-- Rol Institucional -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($user->isAdmin())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                                            <span class="material-symbols-outlined text-[13px]">shield_person</span>
                                            <span>ADMINISTRADOR</span>
                                        </span>
                                    @elseif($user->isGts())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                            <span class="material-symbols-outlined text-[13px]">build</span>
                                            <span>TÉCNICO GTS</span>
                                        </span>
                                    @elseif($user->isApocCco())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            <span class="material-symbols-outlined text-[13px]">desktop_windows</span>
                                            <span>APOC-CCO</span>
                                        </span>
                                    @elseif($user->isApocCsa())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-orange-500/10 text-orange-400 border border-orange-500/20">
                                            <span class="material-symbols-outlined text-[13px]">desktop_windows</span>
                                            <span>APOC-CSA</span>
                                        </span>
                                    @elseif($user->isApoc())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                            <span class="material-symbols-outlined text-[13px]">desktop_windows</span>
                                            <span>OPERADOR APOC</span>
                                        </span>
                                    @else
                                        @php
                                            $badgeColor = $user->role?->badge_color ?? 'blue';
                                            $colorMap = [
                                                'blue' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                                'purple' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                                                'emerald' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                'amber' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                'orange' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                                                'cyan' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                                                'rose' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                                'indigo' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                            ];
                                            $badgeCls = $colorMap[$badgeColor] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                                        @endphp
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeCls }}">
                                            <span class="material-symbols-outlined text-[13px]">badge</span>
                                            <span>{{ $user->role?->name ?? 'USUARIO' }}</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Estado de Cuenta -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            ACTIVO
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/10 text-slate-400 border border-slate-500/20">
                                            INACTIVO
                                        </span>
                                    @endif
                                </td>

                                <!-- Acciones -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Botón Editar -->
                                        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('users.edit'))
                                            <button 
                                                type="button" 
                                                @click="openEdit({ id: {{ $user->id }}, name: '{{ addslashes($user->name) }}', username: '{{ addslashes($user->username) }}', email: '{{ addslashes($user->email ?? '') }}', role_id: '{{ $user->role_id }}', badge_number: '{{ addslashes($user->badge_number ?? '') }}' })"
                                                class="p-1.5 text-[#8D98A8] hover:text-[#F2F5F8] hover:bg-[#1E293B] rounded-lg transition-colors"
                                                title="Editar información"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                            </button>
                                        @endif

                                        <!-- Botón Activar / Desactivar -->
                                        @if($user->id !== auth()->id() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('users.edit')))
                                            <form method="POST" action="{{ route('users.toggle-status', $user) }}" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit" 
                                                    class="p-1.5 rounded-lg transition-colors {{ $user->is_active ? 'text-amber-400 hover:text-amber-200 hover:bg-amber-500/10' : 'text-emerald-400 hover:text-emerald-200 hover:bg-emerald-500/10' }}"
                                                    title="{{ $user->is_active ? 'Desactivar cuenta' : 'Activar cuenta' }}"
                                                >
                                                    <span class="material-symbols-outlined text-[16px]">
                                                        {{ $user->is_active ? 'block' : 'check_circle' }}
                                                    </span>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Botón Eliminar permanentemente -->
                                        @if($user->id !== auth()->id() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('users.delete')))
                                            <button 
                                                type="button" 
                                                @click="openDelete({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ route('users.destroy', $user) }}')"
                                                class="p-1.5 text-rose-400 hover:text-rose-200 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer"
                                                title="Eliminar cuenta permanentemente"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-[#707B8A]">
                                    No se encontraron usuarios que coincidan con los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if ($users->hasPages())
                <div class="p-4 bg-[#0B0F14] border-t border-[#263241]">
                    {{ $users->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- Modal Alta de Usuario -->
        <div 
            x-show="showCreateModal" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    x-show="showCreateModal" 
                    @click="showCreateModal = false"
                    class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div 
                    x-show="showCreateModal"
                    class="relative inline-block align-bottom bg-[#11151B] border border-[#263241] rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full p-6"
                >
                    <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]">
                        <h3 class="text-base font-bold text-[#F2F5F8] flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-[#3D82C6]">person_add</span>
                            <span>Alta de Usuario Institucional</span>
                        </h3>
                        <button type="button" @click="showCreateModal = false" class="text-[#707B8A] hover:text-[#DDE3EA]">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('users.store') }}" class="mt-4 flex flex-col gap-4" autocomplete="off">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Nombre Completo:</label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                placeholder="Ej. Ing. Juan Pérez"
                                class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Nombre de Usuario (Login):</label>
                            <input 
                                type="text" 
                                name="username" 
                                required 
                                pattern="[a-zA-Z0-9._-]+"
                                placeholder="ej. jperez"
                                class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Rol Asignado:</label>
                                <select 
                                    name="role_id" 
                                    required 
                                    class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                                >
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Placa / Credencial:</label>
                                <input 
                                    type="text" 
                                    name="badge_number" 
                                    placeholder="Ej. GTS-312"
                                    class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Contraseña Inicial:</label>
                            <input 
                                type="password" 
                                name="password" 
                                required 
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Mínimo 8 caracteres"
                                class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-3 pt-3 border-t border-[#1F2937]">
                            <button 
                                type="button" 
                                @click="showCreateModal = false" 
                                class="px-4 py-2 bg-[#1E293B] hover:bg-[#334155] text-[#DDE3EA] rounded-xl text-xs font-semibold"
                            >
                                Cancelar
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-[#123A70] hover:bg-[#164A8A] text-white rounded-xl text-xs font-bold"
                            >
                                Guardar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Editar Usuario -->
        <div 
            x-show="showEditModal" 
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    x-show="showEditModal" 
                    @click="showEditModal = false"
                    class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div 
                    x-show="showEditModal"
                    class="relative inline-block align-bottom bg-[#11151B] border border-[#263241] rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full p-6"
                >
                    <div class="flex items-center justify-between pb-3 border-b border-[#1F2937]">
                        <h3 class="text-base font-bold text-[#F2F5F8] flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px] text-[#3D82C6]">edit</span>
                            <span>Editar Usuario</span>
                        </h3>
                        <button type="button" @click="showEditModal = false" class="text-[#707B8A] hover:text-[#DDE3EA]">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>

                    <form :action="'{{ url('/configuracion/usuarios') }}/' + editUser.id" method="POST" class="mt-4 flex flex-col gap-4" autocomplete="off">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Nombre Completo:</label>
                            <input 
                                type="text" 
                                name="name" 
                                x-model="editUser.name"
                                required 
                                class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Nombre de Usuario (Login):</label>
                                <input 
                                    type="text" 
                                    name="username" 
                                    x-model="editUser.username"
                                    required 
                                    pattern="[a-zA-Z0-9._-]+"
                                    class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Correo Institucional (Opcional):</label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    x-model="editUser.email"
                                    class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                                >
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Rol Asignado:</label>
                                <select 
                                    name="role_id" 
                                    x-model="editUser.role_id"
                                    required 
                                    class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                                >
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Placa / Credencial:</label>
                                <input 
                                    type="text" 
                                    name="badge_number" 
                                    x-model="editUser.badge_number"
                                    class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#8D98A8] mb-1">Nueva Contraseña (Opcional):</label>
                            <input 
                                type="password" 
                                name="password" 
                                minlength="8"
                                autocomplete="new-password"
                                placeholder="Dejar en blanco para conservar actual"
                                class="w-full bg-[#0B0F14] border border-[#263241] rounded-xl px-3 py-2 text-xs text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-3 pt-3 border-t border-[#1F2937]">
                            <button 
                                type="button" 
                                @click="showEditModal = false" 
                                class="px-4 py-2 bg-[#1E293B] hover:bg-[#334155] text-[#DDE3EA] rounded-xl text-xs font-semibold"
                            >
                                Cancelar
                            </button>
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-[#3D82C6] hover:bg-[#316cb0] text-white rounded-xl text-xs font-bold"
                            >
                                Actualizar Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Confirmación Eliminación Permanente de Usuario -->
        <div 
            x-show="showDeleteModal" 
            x-cloak
            @keydown.escape.window="showDeleteModal = false"
            class="fixed inset-0 z-50 overflow-y-auto" 
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    x-show="showDeleteModal" 
                    @click="showDeleteModal = false"
                    class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div 
                    x-show="showDeleteModal"
                    class="relative inline-block align-bottom bg-[#11151B] border border-rose-500/30 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6"
                >
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[28px]">warning</span>
                        </div>
                        <div class="flex flex-col">
                            <h3 class="text-base font-bold text-[#F2F5F8]">
                                Confirmar Eliminación Permanente de Cuenta
                            </h3>
                            <p class="text-xs text-[#8D98A8] mt-1.5 leading-relaxed">
                                ¿Está seguro de que desea eliminar permanentemente al usuario <strong class="text-rose-400 font-bold" x-text="deleteUser.name"></strong>?
                            </p>
                            <div class="mt-3 p-3 rounded-xl bg-rose-500/5 border border-rose-500/15 text-[11px] text-rose-300 leading-relaxed flex flex-col gap-1">
                                <span class="font-semibold text-rose-400 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">shield</span>
                                    <span>Gestión segura de dependencias y auditoría OACI:</span>
                                </span>
                                <span>• La cuenta de usuario será purgada físicamente de PostgreSQL de manera definitiva.</span>
                                <span>• Las incidencias y registros históricos vinculados serán reasignados y documentados bajo la administración para resguardar la trazabilidad pericial.</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-[#1F2937]">
                        <button 
                            type="button" 
                            @click="showDeleteModal = false" 
                            class="px-4 py-2 bg-[#1E293B] hover:bg-[#334155] text-[#DDE3EA] rounded-xl text-xs font-semibold cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <form :action="deleteUser.actionUrl" method="POST">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-rose-900/30 transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                                <span>Eliminar Cuenta Definitivamente</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>

