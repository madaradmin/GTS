@props(['class' => ''])

<aside 
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-40 top-16 lg:static lg:top-0 w-64 bg-brand-sidebar border-r border-brand-border flex flex-col justify-between transition-transform duration-200 ease-in-out shrink-0 select-none {{ $class }}"
>
    <!-- Menú de Navegación Vertical -->
    <div class="flex flex-col flex-1 min-h-0 overflow-y-auto scrollbar-thin p-3.5 space-y-1.5">
        
        <!-- 1. Item: Inicio (Botón Activo Sólido Azul Royal) -->
        <a 
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-brand-primary text-white shadow-enterprise-sm' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80' }}"
        >
            <span class="material-symbols-outlined text-[19px]">home</span>
            <span>Inicio</span>
        </a>

        <!-- 2. Item Acordeón: Incidencias -->
        <div x-data="{ openInc: {{ request()->routeIs('incidents.*') || request()->routeIs('dashboard') ? 'true' : 'false' }} }" class="space-y-1">
            <button 
                @click="openInc = !openInc" 
                type="button"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('incidents.*') ? 'text-brand-text-primary bg-brand-surface/70' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80' }}"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[19px]">confirmation_number</span>
                    <span>Incidencias</span>
                </div>
                <span 
                    class="material-symbols-outlined text-[16px] text-brand-text-muted transition-transform duration-200"
                    :class="openInc ? 'rotate-180 text-brand-text-primary' : ''"
                >
                    expand_more
                </span>
            </button>

            <!-- Submenú Desplegado de Incidencias -->
            <div 
                x-show="openInc" 
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="pl-4 pr-1 py-1 space-y-1"
            >
                @can('create', App\Models\Incident::class)
                    <a 
                        href="{{ route('incidents.create') }}"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('incidents.create') ? 'text-blue-400 bg-brand-surface font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/50' }}"
                    >
                        <span class="material-symbols-outlined text-[16px] text-blue-400">add</span>
                        <span>Registrar incidencia</span>
                    </a>
                @endcan

                <a 
                    href="{{ route('incidents.index', auth()->user()->isGts() ? ['assigned_to' => auth()->id()] : ['reported_by' => auth()->id()]) }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ (request()->has('assigned_to') || request()->has('reported_by')) ? 'text-blue-400 bg-brand-surface font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/50' }}"
                >
                    <span class="material-symbols-outlined text-[16px] text-brand-text-muted">format_list_bulleted</span>
                    <span>Mis incidencias</span>
                </a>

                <a 
                    href="{{ route('incidents.index') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('incidents.index') && !request()->has('assigned_to') && !request()->has('reported_by') ? 'text-blue-400 bg-brand-surface font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/50' }}"
                >
                    <span class="material-symbols-outlined text-[16px] text-brand-text-muted">view_list</span>
                    <span>Todas las incidencias</span>
                </a>
            </div>
        </div>

        <!-- 3. Item: Reportes -->
        <div x-data="{ openRep: false }" class="space-y-1">
            <button 
                @click="openRep = !openRep" 
                type="button"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80 transition-all"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[19px]">bar_chart</span>
                    <span>Reportes</span>
                </div>
                <span 
                    class="material-symbols-outlined text-[16px] text-brand-text-muted transition-transform duration-200"
                    :class="openRep ? 'rotate-180 text-brand-text-primary' : ''"
                >
                    expand_more
                </span>
            </button>
            <div x-show="openRep" class="pl-7 pr-2 py-1 space-y-1 text-xs text-brand-text-muted">
                <a href="{{ route('dashboard', ['period' => 'today']) }}" class="block px-3 py-1.5 rounded hover:text-white">Reporte Diario</a>
                <a href="{{ route('dashboard', ['period' => 'week']) }}" class="block px-3 py-1.5 rounded hover:text-white">Reporte Semanal</a>
            </div>
        </div>

        <!-- 4. Item: Indicadores -->
        <a 
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80 transition-all"
        >
            <span class="material-symbols-outlined text-[19px]">speed</span>
            <span>Indicadores</span>
        </a>

        <!-- 5. Item: Catálogos -->
        <div x-data="{ openCat: false }" class="space-y-1">
            <button 
                @click="openCat = !openCat" 
                type="button"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80 transition-all"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[19px]">layers</span>
                    <span>Catálogos</span>
                </div>
                <span 
                    class="material-symbols-outlined text-[16px] text-brand-text-muted transition-transform duration-200"
                    :class="openCat ? 'rotate-180 text-brand-text-primary' : ''"
                >
                    expand_more
                </span>
            </button>
            <div x-show="openCat" class="pl-7 pr-2 py-1 space-y-1 text-xs text-brand-text-muted">
                <span class="block px-3 py-1 text-slate-400">• CCTV / Cámaras</span>
                <span class="block px-3 py-1 text-slate-400">• Control de Acceso</span>
                <span class="block px-3 py-1 text-slate-400">• Pantallas FIDS</span>
                <span class="block px-3 py-1 text-slate-400">• Radiocomunicación</span>
            </div>
        </div>

        <!-- 6. Item: Configuración -->
        <a 
            href="{{ route('profile.edit') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('profile.edit') ? 'bg-brand-surface text-brand-text-primary font-semibold' : 'text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle/80' }}"
        >
            <span class="material-symbols-outlined text-[19px]">settings</span>
            <span>Configuración</span>
        </a>

    </div>

    <!-- Pie del Sidebar: Membrete Institucional Oficial AIFA y Versión -->
    <div class="p-4 border-t border-brand-border bg-brand-topbar/40 flex flex-col gap-3 shrink-0">
        <div class="flex items-start gap-2.5">
            <!-- Isotipo Alado AIFA Compacto -->
            <x-aifa-logo :showText="false" class="h-5 text-slate-300 mt-0.5" />
            <div class="flex flex-col text-left leading-tight min-w-0">
                <span class="font-extrabold text-xs tracking-wider text-slate-200 font-sans">
                    AIFA
                </span>
                <span class="text-[9px] text-slate-400 truncate mt-0.5">
                    Dirección de Operaciones
                </span>
                <span class="text-[9px] text-slate-400 truncate">
                    Grupo de Seguridad Tecnológica
                </span>
            </div>
        </div>

        <div class="flex items-center justify-between text-[10px] font-mono text-brand-text-muted pt-1 border-t border-brand-border/40">
            <span>v1.0.0</span>
            <span class="flex items-center gap-1 text-emerald-400">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Conectado</span>
            </span>
        </div>
    </div>
</aside>

<!-- Backdrop para dispositivos móviles -->
<div 
    x-show="sidebarOpen" 
    @click="sidebarOpen = false" 
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-30 bg-black/70 backdrop-blur-sm lg:hidden"
    style="display: none;"
></div>
