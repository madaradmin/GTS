<header 
    class="h-16 px-4 sm:px-6 bg-brand-topbar border-b border-[#1B2129] flex items-center justify-between gap-4 shrink-0 select-none z-30 w-full"
>
    <!-- 1. Lado Izquierdo: Emblema GTS con Escudo Tecnológico -->
    <div class="flex items-center gap-3 shrink-0">
        <!-- Toggle en Dispositivos Móviles -->
        <button 
            @click="sidebarOpen = !sidebarOpen" 
            type="button"
            class="p-1.5 rounded-lg text-[#8D98A8] hover:text-[#F2F5F8] hover:bg-[#11151B] lg:hidden focus:outline-none transition-colors"
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

    <!-- 3. Lado Derecho: Notificaciones y Perfil -->
    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
        <!-- Componente Interactivo de Notificaciones con Dropdown Flotante -->
        <x-notifications-dropdown />

        <!-- Selector Rápido de Estado de Turno para Agentes GTS y Administradores -->
        @if(auth()->user()->isGts() || auth()->user()->isAdmin())
            <div x-data="{ openShift: false }" class="relative">
                <button 
                    @click="openShift = !openShift" 
                    @click.away="openShift = false"
                    type="button" 
                    class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-medium font-mono transition-all {{ auth()->user()->isShiftActive() ? 'bg-[#063C2E] text-[#10B981] border-[#10B981]/30 hover:border-[#10B981]/60' : 'bg-[#1F2732] text-[#94A3B8] border-[#475569]/30 hover:border-[#475569]/60' }}"
                    title="Alternar disponibilidad operacional en turno"
                >
                    <span class="w-2 h-2 rounded-full {{ auth()->user()->isShiftActive() ? 'bg-[#10B981] animate-pulse' : 'bg-[#94A3B8]' }}"></span>
                    <span>{{ auth()->user()->isShiftActive() ? 'ACTIVO' : 'NO DISPONIBLE' }}</span>
                    <span class="material-symbols-outlined text-[14px] text-[#707B8A]">expand_more</span>
                </button>

                <!-- Dropdown rápido de cambio de turno -->
                <div 
                    x-show="openShift" 
                    x-cloak
                    class="absolute right-0 mt-2 w-48 bg-[#11151B] border border-[#263241] rounded-xl shadow-2xl py-1.5 z-50 text-xs"
                >
                    <div class="px-3 py-1.5 text-[10px] font-mono text-[#707B8A] uppercase border-b border-[#1E293B]">
                        Disponibilidad GTS
                    </div>
                    <form method="POST" action="{{ route('profile.shift-status') }}">
                        @csrf
                        <input type="hidden" name="status" value="ACTIVO">
                        <input type="hidden" name="reason" value="Cambio rápido desde barra superior">
                        <button type="submit" class="w-full text-left px-3 py-1.5 hover:bg-[#151D28] flex items-center justify-between transition-colors {{ auth()->user()->isShiftActive() ? 'text-[#10B981] font-bold' : 'text-[#A8B0BC]' }}">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                                <span>🟢 ACTIVO</span>
                            </span>
                            @if(auth()->user()->isShiftActive())
                                <span class="material-symbols-outlined text-[14px]">check</span>
                            @endif
                        </button>
                    </form>
                    <form method="POST" action="{{ route('profile.shift-status') }}">
                        @csrf
                        <input type="hidden" name="status" value="NO DISPONIBLE">
                        <input type="hidden" name="reason" value="Cambio rápido desde barra superior">
                        <button type="submit" class="w-full text-left px-3 py-1.5 hover:bg-[#151D28] flex items-center justify-between transition-colors {{ auth()->user()->isShiftUnavailable() ? 'text-[#94A3B8] font-bold' : 'text-[#A8B0BC]' }}">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#94A3B8]"></span>
                                <span>⚪ NO DISPONIBLE</span>
                            </span>
                            @if(auth()->user()->isShiftUnavailable())
                                <span class="material-symbols-outlined text-[14px]">check</span>
                            @endif
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Menú Desplegable de Usuario (Avatar y Rol Operacional) -->
        <div x-data="{ userMenuOpen: false }" class="relative">
            <button 
                @click="userMenuOpen = !userMenuOpen"
                @click.away="userMenuOpen = false"
                type="button" 
                class="flex items-center gap-2.5 p-1 rounded-lg hover:bg-[#11151B] transition-colors focus:outline-none"
            >
                <!-- Avatar Circular con Iniciales / Imagen -->
                @php
                    $parts = explode(' ', auth()->user()->name);
                    $initials = count($parts) >= 2 
                        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
                        : strtoupper(substr(auth()->user()->name, 0, 2));
                @endphp
                <div class="w-8 h-8 rounded-full bg-[#123A70] text-white font-bold text-xs flex items-center justify-center border border-[#164A8A] shrink-0 font-mono shadow-sm">
                    {{ $initials }}
                </div>

                <!-- Nombre y Rol -->
                <div class="hidden md:flex flex-col text-left leading-tight">
                    <span class="text-xs font-semibold text-[#F2F5F8]">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="text-[10px] text-[#8D98A8] font-medium">
                        {{ auth()->user()->isGts() ? 'GTS • Operaciones' : (auth()->user()->isApoc() ? 'APOC • Control' : 'ADMIN • Sistema') }}
                    </span>
                </div>

                <span class="material-symbols-outlined text-[16px] text-[#778394] hidden md:inline">
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
                class="absolute right-0 mt-2 w-52 bg-[#11151B] border border-[#252C35] rounded-xl shadow-enterprise py-1.5 z-50 divide-y divide-[#1B2129]"
                style="display: none;"
            >
                <div class="px-3 py-2">
                    <p class="text-xs font-semibold text-[#F2F5F8]">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-[#8D98A8] font-mono truncate">{{ auth()->user()->email }}</p>
                    <div class="mt-1.5 flex items-center gap-1.5 text-[10px] font-mono">
                        <span class="w-1.5 h-1.5 rounded-full {{ auth()->user()->isShiftActive() ? 'bg-[#10B981]' : 'bg-[#94A3B8]' }}"></span>
                        <span class="{{ auth()->user()->isShiftActive() ? 'text-[#10B981]' : 'text-[#94A3B8]' }}">
                            Turno: {{ auth()->user()->isShiftActive() ? 'ACTIVO' : 'NO DISPONIBLE' }}
                        </span>
                    </div>
                </div>
                <div class="py-1">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs text-[#9AA5B4] hover:text-[#F2F5F8] hover:bg-[#151B23] transition-colors">
                        <span class="material-symbols-outlined text-[16px]">person</span>
                        <span>Mi Perfil</span>
                    </a>
                    <a href="{{ route('audit.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs text-[#9AA5B4] hover:text-[#F2F5F8] hover:bg-[#151B23] transition-colors">
                        <span class="material-symbols-outlined text-[16px]">history</span>
                        <span>Bitácora de Auditoría</span>
                    </a>
                </div>
                <div class="py-1">
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-1.5 text-xs text-[#E66A7A] hover:bg-[#D83B52]/10 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">logout</span>
                            <span>Cerrar Sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</header>
