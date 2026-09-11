@props(['class' => ''])

<aside 
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-40 top-16 lg:static lg:top-0 w-64 bg-[#070A0F] border-r border-[#1B2129] flex flex-col justify-between transition-transform duration-200 ease-in-out shrink-0 select-none {{ $class }}"
>
    <!-- 1. Buscador Global del Sistema GTS -->
    <div class="p-3 border-b border-[#1B2129] shrink-0" x-data="{ searchFocused: false }">
        <form action="{{ route('incidents.index') }}" method="GET" class="relative">
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

    <!-- Menú de Navegación Vertical -->
    <div class="flex flex-col flex-1 min-h-0 overflow-y-auto scrollbar-thin p-3.5 space-y-1.5">
        
        <!-- 1. Item: Inicio (Botón Activo Azul Oscuro Corporativo) -->
        <a 
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-[#0E2D56] to-[#123A70] text-[#F2F5F8] border border-[#164A8A]/40 shadow-sm' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923] hover:border hover:border-[#263241]' }}"
        >
            <span class="material-symbols-outlined text-[19px]">home</span>
            <span>Inicio</span>
        </a>

        <!-- 2. Item Acordeón: Incidencias -->
        <div x-data="{ openInc: {{ request()->routeIs('incidents.*') || request()->routeIs('dashboard') ? 'true' : 'false' }} }" class="space-y-1">
            <button 
                @click="openInc = !openInc" 
                type="button"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('incidents.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923] hover:border hover:border-[#263241]' }}"
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
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="pl-4 pr-1 py-1 space-y-1"
            >
                @can('create', App\Models\Incident::class)
                    <a 
                        href="{{ route('incidents.create') }}"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('incidents.create') ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                    >
                        <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">add</span>
                        <span>Registrar incidencia</span>
                    </a>
                @endcan

                <a 
                    href="{{ route('incidents.index', auth()->user()->isGts() ? ['assigned_to' => auth()->id()] : ['reported_by' => auth()->id()]) }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ (request()->has('assigned_to') || request()->has('reported_by')) ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                >
                    <span class="material-symbols-outlined text-[16px] text-[#707B8A]">format_list_bulleted</span>
                    <span>Mis incidencias</span>
                </a>

                <a 
                    href="{{ route('incidents.index') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('incidents.index') && !request()->has('assigned_to') && !request()->has('reported_by') ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                >
                    <span class="material-symbols-outlined text-[16px] text-[#707B8A]">view_list</span>
                    <span>Todas las incidencias</span>
                </a>
            </div>
        </div>

        <!-- 3. Item: Reportes -->
        <div x-data="{ openRep: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }" class="space-y-1">
            <button 
                @click="openRep = !openRep" 
                type="button"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('reports.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923] hover:border hover:border-[#263241]' }}"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[19px]">bar_chart</span>
                    <span>Reportes</span>
                </div>
                <span 
                    class="material-symbols-outlined text-[16px] text-[#707B8A] transition-transform duration-200"
                    :class="openRep ? 'rotate-180 text-[#F2F5F8]' : ''"
                >
                    expand_more
                </span>
            </button>
            <div x-show="openRep" class="pl-4 pr-1 py-1 space-y-1">
                <a 
                    href="{{ route('reports.index') }}" 
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('reports.index') && !request()->has('period_preset') ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                >
                    <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">summarize</span>
                    <span>Generador de reportes</span>
                </a>
                <a 
                    href="{{ route('reports.index', ['period_preset' => 'today']) }}" 
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->get('period_preset') === 'today' ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                >
                    <span class="material-symbols-outlined text-[16px] text-[#707B8A]">today</span>
                    <span>Reporte Diario</span>
                </a>
                <a 
                    href="{{ route('reports.index', ['period_preset' => 'week']) }}" 
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->get('period_preset') === 'week' ? 'text-[#F2F5F8] bg-[#101923] border border-[#263241] font-semibold' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923]' }}"
                >
                    <span class="material-symbols-outlined text-[16px] text-[#707B8A]">date_range</span>
                    <span>Reporte Semanal</span>
                </a>
            </div>
        </div>

        <!-- 4. Item: Indicadores -->
        <a 
            href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923] hover:border hover:border-[#263241] transition-all"
        >
            <span class="material-symbols-outlined text-[19px]">speed</span>
            <span>Indicadores</span>
        </a>

        <!-- 5. Item: Auditoría -->
        <a 
            href="{{ route('audit.index') }}"
            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('audit.*') ? 'text-[#F2F5F8] bg-[#11151B] border border-[#263241]' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923] hover:border hover:border-[#263241]' }}"
        >
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[19px]">manage_history</span>
                <span>Auditoría</span>
            </div>
            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-[#1E293B] text-[#93C5FD]">LOG</span>
        </a>

        <!-- 6. Item: Catálogos -->
        <div x-data="{ openCat: false }" class="space-y-1">
            <button 
                @click="openCat = !openCat" 
                type="button"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-medium text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923] hover:border hover:border-[#263241] transition-all"
            >
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[19px]">layers</span>
                    <span>Catálogos</span>
                </div>
                <span 
                    class="material-symbols-outlined text-[16px] text-[#707B8A] transition-transform duration-200"
                    :class="openCat ? 'rotate-180 text-[#F2F5F8]' : ''"
                >
                    expand_more
                </span>
            </button>
            <div x-show="openCat" class="pl-7 pr-2 py-1 space-y-1 text-xs text-[#707B8A]">
                <span class="block px-3 py-1 text-[#8D98A8]">• CCTV</span>
                <span class="block px-3 py-1 text-[#8D98A8]">• Control de Acceso</span>
                <span class="block px-3 py-1 text-[#8D98A8]">• Pantallas FIDS</span>
                <span class="block px-3 py-1 text-[#8D98A8]">• Radiocomunicación</span>
                <span class="block px-3 py-1 text-[#8D98A8]">• Periféricos</span>
            </div>
        </div>

        <!-- 7. Item: Configuración -->
        <a 
            href="{{ route('profile.edit') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('profile.edit') ? 'bg-gradient-to-r from-[#0E2D56] to-[#123A70] text-[#F2F5F8] border border-[#164A8A]/40 font-semibold shadow-sm' : 'text-[#A8B0BC] hover:text-[#F2F5F8] hover:bg-[#101923] hover:border hover:border-[#263241]' }}"
        >
            <span class="material-symbols-outlined text-[19px]">settings</span>
            <span>Configuración</span>
        </a>

    </div>

    <!-- Pie del Sidebar: Membrete Institucional Oficial AIFA y Versión -->
    <div class="p-4 border-t border-[#1B2129] bg-[#070A0F]/60 flex flex-col gap-3 shrink-0">
        <div class="flex items-start gap-2.5">
            <!-- Isotipo Alado AIFA Compacto -->
            <x-aifa-logo :showText="false" class="h-5 text-slate-300 mt-0.5" />
            <div class="flex flex-col text-left leading-tight min-w-0">
                <span class="font-extrabold text-xs tracking-wider text-slate-200 font-sans">
                    AIFA
                </span>
                <span class="text-[9px] text-[#8D98A8] truncate mt-0.5">
                    Dirección de Operaciones
                </span>
                <span class="text-[9px] text-[#8D98A8] truncate">
                    Grupo de Seguridad Tecnológica
                </span>
            </div>
        </div>

        <div class="flex items-center justify-between text-[10px] font-mono text-[#707B8A] pt-1 border-t border-[#1B2129]">
            <span>v1.0.0</span>
            <span class="flex items-center gap-1 text-[#20C997]">
                <span class="w-1.5 h-1.5 rounded-full bg-[#20C997] animate-pulse"></span>
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
