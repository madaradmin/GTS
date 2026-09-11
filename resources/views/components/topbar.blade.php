<header 
    class="h-16 px-4 sm:px-6 bg-brand-topbar border-b border-brand-border flex items-center justify-between gap-4 shrink-0 select-none z-30 w-full"
>
    <!-- 1. Lado Izquierdo: Emblema GTS con Escudo Tecnológico -->
    <div class="flex items-center gap-3 shrink-0">
        <!-- Toggle en Dispositivos Móviles -->
        <button 
            @click="sidebarOpen = !sidebarOpen" 
            type="button"
            class="p-1.5 rounded-lg text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface lg:hidden focus:outline-none transition-colors"
            title="Alternar Menú"
        >
            <span class="material-symbols-outlined text-[22px]">menu</span>
        </button>

        <!-- Logotipo Institucional GTS -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group focus:outline-none">
            <x-application-logo :showBrandText="true" class="w-8 h-8" />
        </a>
    </div>

    <!-- 2. Centro: Logotipo Oficial del AIFA (Alas y Tipografía de Pista) -->
    <div class="hidden md:flex items-center justify-center flex-1 px-4">
        <x-aifa-logo class="h-6 lg:h-7 text-white" subtext="AEROPUERTO INTERNACIONAL FELIPE ÁNGELES" />
    </div>

    <!-- 3. Lado Derecho: Buscador Global con Caret, Notificaciones y Perfil -->
    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
        
        <!-- Buscador Global con Caret de Filtros -->
        <form action="{{ route('incidents.index') }}" method="GET" class="relative hidden sm:block w-64 lg:w-80">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-brand-text-muted">
                <span class="material-symbols-outlined text-[17px]">search</span>
            </span>
            <input 
                type="search" 
                name="search" 
                value="{{ request('search') }}"
                placeholder="Buscar por folio, descripción, equipo o usuario..."
                class="w-full pl-8 pr-8 py-1.5 bg-[#0b142d] border border-brand-border hover:border-brand-border-focus/60 focus:border-brand-primary focus:ring-1 focus:ring-brand-primary rounded-lg text-xs text-brand-text-primary placeholder-brand-text-muted transition-all outline-none"
            >
            <span class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-brand-text-muted">
                <span class="material-symbols-outlined text-[16px]">expand_more</span>
            </span>
        </form>

        <!-- Campana de Alertas con Badge Contador (3) -->
        <a 
            href="{{ route('incidents.index') }}" 
            class="relative p-2 rounded-lg text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface transition-colors"
            title="Centro de Alertas de Incidencias"
        >
            <span class="material-symbols-outlined text-[20px]">notifications</span>
            <span class="absolute top-1 right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[9px] font-bold flex items-center justify-center font-mono ring-2 ring-brand-topbar animate-pulse">
                3
            </span>
        </a>

        <!-- Menú Desplegable de Usuario (Avatar y Rol Operacional) -->
        <div x-data="{ userMenuOpen: false }" class="relative">
            <button 
                @click="userMenuOpen = !userMenuOpen"
                @click.away="userMenuOpen = false"
                type="button" 
                class="flex items-center gap-2.5 p-1 rounded-lg hover:bg-brand-surface transition-colors focus:outline-none"
            >
                <!-- Avatar Circular con Iniciales / Imagen -->
                @php
                    $parts = explode(' ', auth()->user()->name);
                    $initials = count($parts) >= 2 
                        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                        : strtoupper(substr(auth()->user()->name, 0, 2));
                @endphp
                <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center border border-blue-400/40 shrink-0 font-mono shadow-sm">
                    {{ $initials }}
                </div>

                <!-- Nombre y Rol -->
                <div class="hidden md:flex flex-col text-left leading-tight">
                    <span class="text-xs font-semibold text-brand-text-primary">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="text-[10px] text-brand-text-muted font-medium">
                        {{ auth()->user()->isGts() ? 'GTS • Operaciones' : (auth()->user()->isApoc() ? 'APOC • Control' : 'ADMIN • Sistema') }}
                    </span>
                </div>

                <span class="material-symbols-outlined text-[16px] text-brand-text-muted hidden md:inline">
                    expand_more
                </span>
            </button>

            <!-- Menú Desplegable -->
            <div 
                x-show="userMenuOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-52 bg-brand-surface border border-brand-border rounded-xl shadow-enterprise py-1.5 z-50 divide-y divide-brand-border-subtle"
                style="display: none;"
            >
                <div class="px-3 py-2">
                    <p class="text-xs font-semibold text-brand-text-primary">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-brand-text-muted font-mono truncate">{{ auth()->user()->email }}</p>
                </div>
                <div class="py-1">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle transition-colors">
                        <span class="material-symbols-outlined text-[16px]">person</span>
                        <span>Mi Perfil</span>
                    </a>
                    <a href="{{ route('audit.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface-subtle transition-colors">
                        <span class="material-symbols-outlined text-[16px]">history</span>
                        <span>Bitácora de Auditoría</span>
                    </a>
                </div>
                <div class="py-1">
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-rose-400 hover:bg-rose-500/10 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">logout</span>
                            <span>Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</header>
