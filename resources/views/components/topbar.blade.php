<header 
    class="h-16 px-4 sm:px-6 bg-brand-topbar border-b border-[#1B2129] flex items-center justify-between gap-4 shrink-0 select-none z-30 w-full"
>
    <!-- 1. Lado Izquierdo: Emblema GTS con Escudo Tecnológico -->
    <div class="flex items-center gap-3 shrink-0">
        <!-- Logotipo Institucional GTS -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group focus:outline-none">
            <x-application-logo :showBrandText="true" class="w-10 h-10" />
        </a>
    </div>

    <!-- 2. Centro: Logotipo Oficial del AIFA (Alas y Tipografía Institucional) -->
    <div class="hidden md:flex items-center justify-center flex-1 px-4">
        <x-aifa-logo class="h-7 lg:h-8" subtext="AEROPUERTO INTERNACIONAL FELIPE ÁNGELES" />
    </div>

    <!-- 3. Lado Derecho: Notificaciones y Perfil -->
    <div class="flex items-center gap-3 sm:gap-4 shrink-0">
        <!-- Componente Interactivo de Notificaciones con Dropdown Flotante -->
        <x-notifications-dropdown />

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
                        {{ auth()->user()->isGts() ? 'GTS • Operaciones' : (auth()->user()->isApoc() ? 'APOC • Control' : (auth()->user()->isAdmin() ? 'ADMIN • Sistema' : (auth()->user()->role?->name ?? 'Usuario') . ' • Sistema')) }}
                    </span>
                </div>

                <span class="material-symbols-outlined text-[16px] text-[#778394] hidden md:inline">
                    expand_more
                </span>
            </button>

            <!-- Menú Desplegable -->
            <div 
                x-show="userMenuOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="transform opacity-0 scale-95 -translate-y-1"
                class="absolute right-0 mt-2 w-52 bg-[#11151B] border border-[#252C35] rounded-xl shadow-enterprise py-1.5 z-50 divide-y divide-[#1B2129]"
                style="display: none;"
            >
                <div class="px-3 py-2">
                    <p class="text-xs font-semibold text-[#F2F5F8]">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-[#8D98A8] font-mono truncate">{{ auth()->user()->email ? auth()->user()->email : '@' . auth()->user()->username }}</p>
                    <div class="mt-1.5 flex items-center gap-1.5 text-[10px] font-mono text-[#707B8A]">
                        <span>{{ auth()->user()->role?->name ?? 'Usuario' }}</span>
                    </div>
                </div>
                <div class="py-1">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs text-[#9AA5B4] hover:text-[#F2F5F8] hover:bg-[#151B23] transition-colors">
                        <span class="material-symbols-outlined text-[16px]">person</span>
                        <span>Mi Perfil</span>
                    </a>
                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('audit.view'))
                        <a href="{{ route('audit.index') }}" class="flex items-center gap-2 px-3 py-1.5 text-xs text-[#9AA5B4] hover:text-[#F2F5F8] hover:bg-[#151B23] transition-colors">
                            <span class="material-symbols-outlined text-[16px]">history</span>
                            <span>Bitácora de Auditoría</span>
                        </a>
                    @endif
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
