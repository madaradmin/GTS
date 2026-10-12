@props(['class' => ''])

<!-- Zona Sensible de Activación por Proximidad (0px - 14px desde el borde izquierdo) -->
<div 
    @mouseenter="openSidebar()" 
    class="fixed top-16 bottom-0 left-0 w-3.5 z-30 pointer-events-auto bg-transparent select-none"
    aria-hidden="true"
></div>

<!-- Panel de Navegación Lateral (Sidebar Oculto con Despliegue Suave por Hover) -->
<aside 
    @mouseenter="openSidebar()"
    @mouseleave="closeSidebar()"
    @focusin="openSidebar()"
    @focusout="closeSidebar()"
    :class="sidebarHover ? 'translate-x-0 shadow-2xl shadow-black/80' : '-translate-x-full shadow-none'"
    class="fixed inset-y-0 left-0 z-40 top-16 w-64 bg-[#070A0F] border-r border-[#1B2129] flex flex-col justify-between -translate-x-full shadow-none transition-transform duration-200 ease-out shrink-0 select-none {{ $class }}"
>
    <!-- 1. Buscador Global del Sistema GTS -->
    @can('viewAny', App\Models\Incident::class)
    <div class="p-3 border-b border-[#1B2129] shrink-0" x-data="{ searchFocused: false }">
        <form action="{{ route('incidents.index') }}" method="GET" class="relative" autocomplete="off">
            <span 
                class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none transition-colors"
                :class="searchFocused ? 'text-[#3D82C6]' : 'text-[#707B8A]'"
            >
                <span class="material-symbols-outlined text-[16px]">search</span>
            </span>
            <input 
                x-ref="sidebarSearch"
                @focus="searchFocused = true"
                @blur="searchFocused = false"
                @keydown.window.prevent.slash="if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') { $refs.sidebarSearch.focus(); }"
                @keydown.window.cmd.k.prevent="$refs.sidebarSearch.focus()"
                @keydown.window.ctrl.k.prevent="$refs.sidebarSearch.focus()"
                type="search" 
                name="search" 
                value="{{ request('search') }}"
                placeholder="Buscar incidencias..."
                autocomplete="off"
                autocorrect="off"
                spellcheck="false"
                class="w-full pl-8 pr-7 py-2 bg-[#0D1117] border border-[#252C35] hover:border-[#344050] focus:border-[#164A8A] focus:ring-1 focus:ring-[#164A8A] rounded-xl text-xs text-[#D6DCE4] placeholder-[#6F7A89] transition-all outline-none"
            >
            @if(request('search'))
                <a 
                    href="{{ route('incidents.index') }}" 
                    class="absolute inset-y-0 right-0 pr-2 flex items-center text-[#707B8A] hover:text-[#E66A7A] transition-colors"
                    title="Limpiar búsqueda"
                >
                    <span class="material-symbols-outlined text-[14px]">close</span>
                </a>
            @else
                <span class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-[#566170] text-[10px] font-mono select-none" title="Presiona / para buscar">
                    /
                </span>
            @endif
        </form>
    </div>
    @endcan

    <!-- Menú de Navegación Vertical -->
    <div class="flex flex-col flex-1 min-h-0 overflow-y-auto scrollbar-thin p-3.5 space-y-1.5">
        
        <!-- 1. Item: Inicio (Botón Activo Azul Oscuro Corporativo) -->
        <a 
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-[#0E2D56] to-[#123A70] text-[#F2F5F8] border border-[#164A8A]/40 shadow-sm' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
        >
            <span class="material-symbols-outlined text-[19px]">home</span>
            <span>Inicio</span>
        </a>

        <!-- 2. Item Acordeón: Incidencias -->
        @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['incidents.create', 'incidents.view_assigned', 'incidents.view_department', 'incidents.view_all']))
        <div x-data="{ openInc: {{ request()->routeIs('incidents.*') && !request()->routeIs('dashboard') ? 'true' : 'false' }} }" class="space-y-1">
            <button 
                @click="openInc = !openInc" 
                type="button"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('incidents.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[19px]">confirmation_number</span>
                    <span>Incidencias</span>
                </div>
                <span 
                    class="material-symbols-outlined text-[16px] text-[#707B8A] transition-transform duration-200"
                    :class="openInc ? 'rotate-180 text-[#F2F5F8]' : ''"
                >
                    expand_more
                </span>
            </button>

            <!-- Submenú Desplegado de Incidencias -->
            <div 
                x-show="openInc" 
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="pl-4 pr-1 py-1 space-y-1"
                style="{{ request()->routeIs('incidents.*') && !request()->routeIs('dashboard') ? '' : 'display: none;' }}"
            >
                @can('create', App\Models\Incident::class)
                    <a 
                        href="{{ route('incidents.create') }}"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('incidents.create') ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                    >
                        <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">add</span>
                        <span>Registrar incidencia</span>
                    </a>
                @endcan

                @if(auth()->user()->isAdmin() || auth()->user()->hasAnyPermission(['incidents.view_assigned', 'incidents.view_department']))
                    <a 
                        href="{{ route('incidents.my') }}"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('incidents.my') || (request()->has('assigned_to') || request()->has('reported_by')) ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                    >
                        <span class="material-symbols-outlined text-[16px] text-[#707B8A]">format_list_bulleted</span>
                        <span>Mis incidencias</span>
                    </a>
                @endif

                @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('incidents.view_all'))
                    <a 
                        href="{{ route('incidents.index') }}"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('incidents.index') && !request()->routeIs('incidents.my') && !request()->has('assigned_to') && !request()->has('reported_by') ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                    >
                        <span class="material-symbols-outlined text-[16px] text-[#707B8A]">view_list</span>
                        <span>Todas las incidencias</span>
                    </a>
                @endif
            </div>
        </div>
        @endif

        <!-- 3. Item: Descargar reporte -->
        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('reports.view'))
        <a 
            href="{{ route('reports.index') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('reports.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
            title="Descargar reporte"
        >
            <span class="material-symbols-outlined text-[19px]">bar_chart</span>
            <span>Descargar reporte</span>
        </a>
        @endif

        <!-- 4. Item: Indicadores -->
        <a 
            href="{{ route('indicators.index') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('indicators.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
        >
            <span class="material-symbols-outlined text-[19px]">speed</span>
            <span>Indicadores</span>
        </a>

        <!-- 5. Item: Auditoría -->
        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('audit.view'))
            <a 
                href="{{ route('audit.index') }}"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('audit.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[19px]">manage_history</span>
                    <span>Auditoría</span>
                </div>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-[#1E293B] text-[#93C5FD]">LOG</span>
            </a>
        @endif

        <!-- 6. Item: Roles y Permisos (Exclusivo Administrador) -->
        @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('roles.view'))
            <a 
                href="{{ route('roles.index') }}"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('roles.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[19px]">admin_panel_settings</span>
                    <span>Roles y Permisos</span>
                </div>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-[#1E293B] text-[#A78BFA]">SEC</span>
            </a>
        @endif

        <!-- 7. Item: Catálogos / Infraestructura -->
        <a 
            href="{{ route('catalogs.index') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('catalogs.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
        >
            <span class="material-symbols-outlined text-[19px]">layers</span>
            <span>Catálogos</span>
        </a>

        <!-- 8. Item: Configuración -->
        <a 
            href="{{ route('profile.edit') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ (request()->routeIs('profile.*') || request()->routeIs('users.*') || request()->routeIs('roles.*')) ? 'bg-gradient-to-r from-[#0E2D56] to-[#123A70] text-[#F2F5F8] border border-[#164A8A]/40 font-semibold shadow-sm' : 'border border-transparent text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
        >
            <span class="material-symbols-outlined text-[19px]">settings</span>
            <span>Configuración</span>
        </a>

    </div>

    <!-- Pie del Sidebar: Versión y Rol del Sistema -->
    <div class="px-4 py-3 border-t border-[#1B2129] bg-[#070A0F]/60 flex items-center justify-between text-[10px] font-mono text-[#707B8A] shrink-0">
        <span>v1.0.0</span>
        <span class="text-[#707B8A] font-mono text-[10px] uppercase">{{ auth()->user()->role->name ?? auth()->user()->role->slug ?? 'GTS' }}</span>
    </div>
</aside>

