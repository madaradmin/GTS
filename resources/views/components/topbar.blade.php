<header 
    x-data="{ 
        clock: new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
        dateStr: new Date().toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' })
    }"
    x-init="
        setInterval(() => {
            const now = new Date();
            clock = now.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }, 1000);
    "
    class="h-14 px-4 sm:px-6 bg-brand-topbar border-b border-brand-border flex items-center justify-between gap-4 shrink-0 select-none z-20"
>
    <!-- 1. Lado Izquierdo: Botón Toggle y Buscador Global Rápido -->
    <div class="flex items-center gap-3 flex-1 max-w-xl">
        
        <!-- Toggle Sidebar (Móvil / Tablet) -->
        <button 
            @click="sidebarOpen = !sidebarOpen" 
            type="button"
            class="p-1.5 rounded-lg text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface lg:hidden focus:outline-none transition-colors"
            title="Alternar Menú"
        >
            <span class="material-symbols-outlined text-[20px]">menu</span>
        </button>

        <!-- Buscador Global Operativo -->
        <form action="{{ route('incidents.index') }}" method="GET" class="relative w-full max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-brand-text-muted">
                <span class="material-symbols-outlined text-[18px]">search</span>
            </span>
            <input 
                type="search" 
                name="search" 
                value="{{ request('search') }}"
                placeholder="Buscar folio, título, usuario o área..."
                class="w-full pl-9 pr-4 py-1.5 bg-brand-surface border border-brand-border hover:border-brand-border-focus/50 focus:border-brand-primary focus:ring-1 focus:ring-brand-primary rounded-lg text-xs text-brand-text-primary placeholder-brand-text-muted transition-all outline-none"
            >
        </form>

    </div>

    <!-- 2. Lado Derecho: Telemetría de Guardia, Rol y Reloj de Conexión -->
    <div class="flex items-center gap-3.5 shrink-0">
        
        <!-- Campana de Alertas / Notificaciones -->
        <a 
            href="{{ route('incidents.index') }}" 
            class="relative p-1.5 rounded-lg text-brand-text-secondary hover:text-brand-text-primary hover:bg-brand-surface transition-colors"
            title="Centro de Alertas de Incidencias"
        >
            <span class="material-symbols-outlined text-[20px]">notifications</span>
            <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-brand-topbar animate-pulse"></span>
        </a>

        <div class="h-4 w-px bg-brand-border"></div>

        <!-- Chip de Rol Operacional -->
        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-brand-surface border border-brand-border text-xs">
            <span class="w-1.5 h-1.5 rounded-full {{ auth()->user()->isGts() ? 'bg-blue-400' : (auth()->user()->isApoc() ? 'bg-amber-400' : 'bg-emerald-400') }}"></span>
            <span class="font-mono text-[11px] font-bold tracking-wide {{ auth()->user()->isGts() ? 'text-blue-400' : (auth()->user()->isApoc() ? 'text-amber-400' : 'text-emerald-400') }}">
                {{ auth()->user()->isGts() ? 'GTS' : (auth()->user()->isApoc() ? 'APOC' : 'ADMIN') }}
            </span>
        </div>

        <div class="hidden sm:block h-4 w-px bg-brand-border"></div>

        <!-- Reloj y Fecha Sincronizada en Vivo -->
        <div class="hidden sm:flex flex-col text-right font-mono text-xs leading-tight">
            <span class="text-brand-text-primary font-semibold tracking-wider text-[11px]" x-text="clock"></span>
            <span class="text-[10px] text-brand-text-muted" x-text="dateStr"></span>
        </div>

    </div>
</header>