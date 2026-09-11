@props(['class' => ''])

<aside 
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-40 w-64 bg-brand-sidebar border-r border-brand-border flex flex-col justify-between transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 shrink-0 select-none {{ $class }}"
>
    <!-- Contenedor Superior: Logo y Navegación Principal -->
    <div class="flex flex-col flex-1 min-h-0 overflow-y-auto scrollbar-thin">
        
        <!-- 1. Header con Emblema Institucional GIT -->
        <div class="h-14 px-4 flex items-center gap-3 border-b border-brand-border shrink-0 bg-brand-topbar/60">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group focus:outline-none">
                <div class="w-8 h-8 rounded-lg bg-brand-primary-muted/40 border border-blue-500/30 flex items-center justify-center group-hover:border-brand-primary transition-colors shadow-sm">
                    <x-application-logo class="w-5 h-5 text-brand-primary" />
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-bold text-sm text-brand-text-primary tracking-wider leading-tight group-hover:text-blue-400 transition-colors">
                        GIT
                    </span>
                    <span class="text-[10px] text-brand-text-muted truncate leading-tight font-medium">
                        Gestión de Incidencias
                    </span>
                </div>
            </a>
        </div>

        <!-- 2. Menú de Navegación Vertical -->
        <nav class="p-3 space-y-1.5 flex-1">
            
            <!-- Item: Dashboard -->
            <a 
                href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-brand-primary text-white shadow-enterprise-sm' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80' }}"
            >
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                <span>Dashboard</span>
            </a>

            <!-- Item con Acordeón: Incidencias -->
            <div x-data="{ open: {{ request()->routeIs('incidents.*') ? 'true' : 'false' }} }" class="space-y-1">
                <button 
                    @click="open = !open" 
                    type="button"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('incidents.*') ? 'text-brand-text-primary bg-brand-surface/70' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80' }}"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[18px]">confirmation_number</span>
                        <span>Incidencias</span>
                    </div>
                    <span 
                        class="material-symbols-outlined text-[16px] text-brand-text-muted transition-transform duration-200"
                        :class="open ? 'rotate-180 text-brand-text-primary' : ''"
                    >
                        expand_more
                    </span>
                </button>

                <!-- Submenú de Incidencias -->
                <div 
                    x-show="open" 
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="pl-9 pr-2 py-1 space-y-1"
                >
                    <a 
                        href="{{ route('incidents.index') }}"
                        class="block px-2.5 py-1.5 rounded-md text-[11px] font-medium transition-colors {{ request()->routeIs('incidents.index') && !request()->has('assigned_to') ? 'text-blue-400 bg-brand-primary-muted/30 font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/50' }}"
                    >
                        Todas
                    </a>

                    @if(auth()->user()->isGts())
                        <a 
                            href="{{ route('incidents.index', ['assigned_to' => auth()->id()]) }}"
                            class="block px-2.5 py-1.5 rounded-md text-[11px] font-medium transition-colors {{ request()->query('assigned_to') == auth()->id() ? 'text-blue-400 bg-brand-primary-muted/30 font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/50' }}"
                        >
                            Mis Asignadas
                        </a>
                    @elseif(auth()->user()->isApoc())
                        <a 
                            href="{{ route('incidents.index', ['reported_by' => auth()->id()]) }}"
                            class="block px-2.5 py-1.5 rounded-md text-[11px] font-medium transition-colors {{ request()->query('reported_by') == auth()->id() ? 'text-blue-400 bg-brand-primary-muted/30 font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/50' }}"
                        >
                            Mis Reportadas
                        </a>
                    @endif

                    @can('create', App\Models\Incident::class)
                        <a 
                            href="{{ route('incidents.create') }}"
                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-md text-[11px] font-medium transition-colors {{ request()->routeIs('incidents.create') ? 'text-blue-400 bg-brand-primary-muted/30 font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/50' }}"
                        >
                            <span>Nueva incidencia</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 ml-auto"></span>
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Item: Auditoría e Historial Pericial -->
            <a 
                href="{{ route('audit.index') }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('audit.index') ? 'bg-brand-primary text-white shadow-enterprise-sm font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80' }}"
            >
                <span class="material-symbols-outlined text-[18px]">manage_history</span>
                <span>Auditoría</span>
            </a>

            <!-- Divisor de Sección -->
            <div class="pt-2 pb-1 px-3">
                <div class="h-px bg-brand-border-subtle"></div>
            </div>

            <!-- Item: Reportes (Informativo / Filtro de Telemetría) -->
            <a 
                href="{{ route('dashboard', ['period' => 'week']) }}"
                class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80 transition-all"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[18px]">monitoring</span>
                    <span>Reportes</span>
                </div>
                <span class="material-symbols-outlined text-[14px] text-brand-text-muted">chevron_right</span>
            </a>

            <!-- Item: Usuarios / Personal de Guardia -->
            <div 
                x-data="{ openUsers: false }"
                class="space-y-1"
            >
                <button 
                    @click="openUsers = !openUsers"
                    type="button"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80 transition-all"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[18px]">badge</span>
                        <span>Personal</span>
                    </div>
                    <span 
                        class="material-symbols-outlined text-[16px] text-brand-text-muted transition-transform duration-200"
                        :class="openUsers ? 'rotate-180 text-brand-text-primary' : ''"
                    >
                        expand_more
                    </span>
                </button>

                <div x-show="openUsers" class="pl-9 pr-3 py-1 text-[11px] text-brand-text-muted space-y-1">
                    <span class="block text-[10px] uppercase font-semibold text-slate-400">Guardia Operativa</span>
                    <span class="block text-slate-300">• Tco. M. Reyes (APOC)</span>
                    <span class="block text-slate-300">• Ing. R. Morales (GTS)</span>
                    <span class="block text-slate-300">• Ing. C. Mendoza (GTS)</span>
                </div>
            </div>

            <!-- Item: Configuración / Perfil -->
            <a 
                href="{{ route('profile.edit') }}"
                class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('profile.edit') ? 'bg-brand-primary text-white font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80' }}"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[18px]">settings</span>
                    <span>Configuración</span>
                </div>
                <span class="material-symbols-outlined text-[14px] text-brand-text-muted">chevron_right</span>
            </a>

        </nav>

    </div>

    <!-- 3. Footer del Sidebar: Perfil del Usuario Activo y Cierre de Sesión -->
    <div class="p-3 border-t border-brand-border bg-brand-topbar/40 space-y-2 shrink-0">
        
        <!-- Tarjeta de Usuario Actual -->
        <a 
            href="{{ route('profile.edit') }}" 
            class="flex items-center justify-between p-2 rounded-lg hover:bg-brand-surface transition-colors group"
        >
            <div class="flex items-center gap-2.5 min-w-0">
                <!-- Avatar Circular con Iniciales -->
                @php
                    $parts = explode(' ', auth()->user()->name);
                    $initials = count($parts) >= 2 
                        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                        : strtoupper(substr(auth()->user()->name, 0, 2));
                @endphp
                <div class="w-8 h-8 rounded-full bg-blue-900/60 text-blue-300 font-bold text-xs flex items-center justify-center border border-blue-500/40 shrink-0 font-mono shadow-sm">
                    {{ $initials }}
                </div>
                
                <!-- Nombre y Rol -->
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-semibold text-brand-text-primary truncate group-hover:text-blue-300 transition-colors">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="text-[10px] text-brand-text-muted font-mono uppercase tracking-wider">
                        {{ auth()->user()->role?->name ?? 'Operador' }}
                    </span>
                </div>
            </div>
            
            <span class="material-symbols-outlined text-[16px] text-brand-text-muted group-hover:text-brand-text-primary transition-colors">
                chevron_right
            </span>
        </a>

        <!-- Botón de Cerrar Sesión -->
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button 
                type="submit"
                class="w-full flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
            >
                <span class="material-symbols-outlined text-[16px]">logout</span>
                <span>Cerrar sesión</span>
            </button>
        </form>

    </div>
</aside>

<!-- Backdrop de cierre en móviles -->
<div 
    x-show="sidebarOpen" 
    @click="sidebarOpen = false" 
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden"
></div>