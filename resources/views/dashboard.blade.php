<x-app-layout>
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6">

        <!-- 1. Encabezado de Bienvenida y Selector de Turno / Período -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl sm:text-3xl font-bold text-on-surface tracking-tight">Dashboard Operativo</h1>
                    <span class="px-2.5 py-0.5 rounded bg-surface-container text-primary font-mono text-xs font-semibold tracking-wide uppercase border border-border-subtle">
                        AIFA {{ auth()->user()->isGts() ? 'GTS TÉCNICO' : (auth()->user()->isApoc() ? 'APOC CONTROL' : 'ADMINISTRACIÓN') }}
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-on-surface-variant">
                    Telemetría situacional en tiempo real y estado general de la guardia
                </p>
            </div>

            <!-- Filtros Rápidos de Guardia y Período -->
            <div class="flex flex-wrap items-center gap-2.5 self-start md:self-auto">
                <!-- Chip de Guardia Activa -->
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-container-low border border-border-subtle shadow-sm text-xs">
                    <span class="w-2 h-2 rounded-full bg-status-resolved animate-pulse"></span>
                    <span class="text-outline font-medium">Turno:</span>
                    <span class="text-on-surface font-semibold font-mono">{{ $currentShift['name'] }}</span>
                </div>

                <!-- Botones de Período -->
                <div class="flex items-center gap-1 bg-surface-container-low border border-border-subtle p-1 rounded-xl shadow-sm text-xs">
                    <a
                        href="{{ route('dashboard', ['period' => 'today']) }}"
                        class="px-3 py-1 rounded-lg font-semibold transition-colors {{ $metrics['period'] === 'today' ? 'bg-surface-container-high text-primary border border-border-active shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}"
                    >
                        Hoy
                    </a>
                    <a
                        href="{{ route('dashboard', ['period' => '24h']) }}"
                        class="px-3 py-1 rounded-lg font-semibold transition-colors {{ $metrics['period'] === '24h' ? 'bg-surface-container-high text-primary border border-border-active shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}"
                    >
                        24h
                    </a>
                    <a
                        href="{{ route('dashboard', ['period' => 'week']) }}"
                        class="px-3 py-1 rounded-lg font-semibold transition-colors {{ $metrics['period'] === 'week' ? 'bg-surface-container-high text-primary border border-border-active shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}"
                    >
                        Semana
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Métricas Esenciales (4 Tarjetas KPI Tácticas) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            
            <!-- Tarjeta 1: Abiertas -->
            <div class="bg-surface-subtle border border-border-subtle p-5 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-medium text-on-surface-variant">Incidencias Abiertas</span>
                    <div class="w-9 h-9 rounded-xl bg-surface-container-high text-amber-400 flex items-center justify-center border border-border-subtle">
                        <span class="material-symbols-outlined text-[20px]">notification_important</span>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="font-mono text-3xl sm:text-4xl text-on-surface font-bold">
                        {{ sprintf('%02d', $metrics['total_open']) }}
                    </span>
                    <span class="text-xs text-outline font-mono">activas</span>
                </div>
                <div class="mt-3 pt-3 border-t border-border-subtle/60 flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 text-rose-400 font-semibold font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                        {{ $metrics['open_critical'] }} Críticas
                    </span>
                    <span class="text-outline">•</span>
                    <span class="inline-flex items-center gap-1 text-amber-400 font-semibold font-mono">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        {{ $metrics['open_high'] }} Altas
                    </span>
                </div>
            </div>

            <!-- Tarjeta 2: En Atención -->
            <div class="bg-surface-subtle border border-border-subtle p-5 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-medium text-on-surface-variant">En Atención Técnica</span>
                    <div class="w-9 h-9 rounded-xl bg-surface-container-high text-primary flex items-center justify-center border border-border-subtle">
                        <span class="material-symbols-outlined text-[20px]">engineering</span>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="font-mono text-3xl sm:text-4xl text-primary font-bold">
                        {{ sprintf('%02d', $metrics['in_attention']) }}
                    </span>
                    <span class="text-xs text-outline font-mono">en curso</span>
                </div>
                <div class="mt-3 pt-3 border-t border-border-subtle/60 flex items-center justify-between text-xs text-on-surface-variant">
                    <span>Especialistas GTS en campo</span>
                    <a href="{{ route('incidents.index', ['status' => 'EN_ATENCION']) }}" class="text-primary hover:underline flex items-center gap-0.5">
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- Tarjeta 3: Resueltas -->
            <div class="bg-surface-subtle border border-border-subtle p-5 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-medium text-on-surface-variant">Resueltas en Período</span>
                    <div class="w-9 h-9 rounded-xl bg-surface-container-high text-emerald-400 flex items-center justify-center border border-border-subtle">
                        <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="font-mono text-3xl sm:text-4xl text-on-surface font-bold">
                        {{ sprintf('%02d', $metrics['resolved_in_period']) }}
                    </span>
                    <span class="text-xs text-emerald-400 font-mono font-medium">certificadas</span>
                </div>
                <div class="mt-3 pt-3 border-t border-border-subtle/60 flex items-center justify-between text-xs text-on-surface-variant">
                    <span>Pendientes de VoBo APOC:</span>
                    <span class="px-2 py-0.5 rounded bg-surface-container-high text-amber-400 font-mono font-bold">
                        {{ $metrics['pending_vobo'] }}
                    </span>
                </div>
            </div>

            <!-- Tarjeta 4: MTTR / Tiempo Promedio -->
            <div class="bg-surface-subtle border border-border-subtle p-5 rounded-2xl shadow-sm flex flex-col justify-between relative overflow-hidden group">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-medium text-on-surface-variant">Tiempo Promedio (MTTR)</span>
                    <div class="w-9 h-9 rounded-xl bg-surface-container-high text-cyan-400 flex items-center justify-center border border-border-subtle">
                        <span class="material-symbols-outlined text-[20px]">schedule</span>
                    </div>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="font-mono text-3xl sm:text-4xl text-on-surface font-bold">
                        {{ $metrics['mttr_minutes'] }}
                    </span>
                    <span class="text-xs text-outline font-mono">minutos</span>
                </div>
                <div class="mt-3 pt-3 border-t border-border-subtle/60 flex items-center gap-1.5 text-xs">
                    @if($metrics['mttr_within_target'])
                        <span class="material-symbols-outlined text-[16px] text-emerald-400">verified</span>
                        <span class="text-emerald-400 font-medium font-mono">Dentro del objetivo (&lt; 25m)</span>
                    @else
                        <span class="material-symbols-outlined text-[16px] text-amber-400">warning</span>
                        <span class="text-amber-400 font-medium font-mono">Excede meta (&gt; 25m)</span>
                    @endif
                </div>
            </div>

        </div>

        <!-- 3. Sección Central: Dos Columnas (65% / 35%) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Columna Izquierda: Incidencias Recientes y Prioritarias (65%) -->
            <div class="lg:col-span-8 flex flex-col bg-surface-subtle border border-border-subtle rounded-2xl shadow-sm overflow-hidden">
                <!-- Barra superior de la tabla -->
                <div class="p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-surface-subtle border-b border-border-subtle">
                    <div class="flex flex-col gap-0.5">
                        <h2 class="text-base font-bold text-on-surface tracking-tight">Incidencias Recientes y Prioritarias</h2>
                        <span class="text-xs text-on-surface-variant">Operaciones activas registradas en APOC / GTS</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('incidents.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                            <span>Ver todas ({{ $metrics['total_open'] }})</span>
                            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <!-- Tabla limpia y de alta densidad -->
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low/70 text-outline text-[11px] font-semibold uppercase tracking-wider border-b border-border-subtle">
                                <th class="py-3 px-4">Folio</th>
                                <th class="py-3 px-4">Asunto / Falla</th>
                                <th class="py-3 px-4">Ubicación</th>
                                <th class="py-3 px-4">Prioridad</th>
                                <th class="py-3 px-4">Estado</th>
                                <th class="py-3 px-4 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle/40 text-xs text-on-surface">
                            @forelse ($recentIncidents as $inc)
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <!-- Folio -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <a href="{{ route('incidents.show', $inc->folio) }}" class="font-mono font-bold text-primary hover:underline">
                                            {{ $inc->folio }}
                                        </a>
                                    </td>

                                    <!-- Asunto / Falla -->
                                    <td class="py-3.5 px-4 max-w-xs">
                                        <div class="font-semibold text-on-surface truncate" title="{{ $inc->title }}">
                                            {{ $inc->title }}
                                        </div>
                                        <div class="text-[11px] text-outline mt-0.5">
                                            {{ $inc->category->name }} • {{ $inc->created_at->diffForHumans() }}
                                        </div>
                                    </td>

                                    <!-- Ubicación -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-on-surface-variant">
                                        {{ $inc->location }}
                                    </td>

                                    <!-- Prioridad -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold uppercase font-mono
                                            @if($inc->priority->slug === 'critica') bg-rose-500/15 text-rose-400 border border-rose-500/30
                                            @elseif($inc->priority->slug === 'alta') bg-amber-500/15 text-amber-400 border border-amber-500/30
                                            @elseif($inc->priority->slug === 'media') bg-blue-500/15 text-blue-400 border border-blue-500/30
                                            @else bg-slate-500/15 text-slate-400 border border-slate-500/30 @endif">
                                            <span class="w-1.5 h-1.5 rounded-full
                                                @if($inc->priority->slug === 'critica') bg-rose-400
                                                @elseif($inc->priority->slug === 'alta') bg-amber-400
                                                @elseif($inc->priority->slug === 'media') bg-blue-400
                                                @else bg-slate-400 @endif"></span>
                                            {{ $inc->priority->name }}
                                        </span>
                                    </td>

                                    <!-- Estado -->
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            @if($inc->status->code === 'NUEVA') bg-blue-500/15 text-blue-400 border border-blue-500/30
                                            @elseif($inc->status->code === 'ASIGNADA') bg-purple-500/15 text-purple-400 border border-purple-500/30
                                            @elseif($inc->status->code === 'EN_ATENCION') bg-amber-500/15 text-amber-400 border border-amber-500/30
                                            @elseif($inc->status->code === 'RESUELTA') bg-emerald-500/15 text-emerald-400 border border-emerald-500/30
                                            @else bg-slate-500/15 text-slate-400 border border-slate-500/30 @endif">
                                            {{ $inc->status->name }}
                                        </span>
                                    </td>

                                    <!-- Acción -->
                                    <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                        <a
                                            href="{{ route('incidents.show', $inc->folio) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container-high hover:bg-surface-container text-on-surface hover:text-primary font-semibold text-xs transition-colors border border-border-subtle"
                                        >
                                            <span>Detalle</span>
                                            <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 px-4 text-center text-outline text-xs">
                                        No hay incidencias registradas para mostrar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pie de tabla -->
                <div class="p-3.5 bg-surface-container-low border-t border-border-subtle flex items-center justify-between text-xs text-outline">
                    <span>Mostrando {{ $recentIncidents->count() }} de {{ $metrics['total_open'] }} incidencias activas</span>
                    <a href="{{ route('incidents.index') }}" class="text-primary hover:underline font-semibold">
                        Ir al Tablero Completo →
                    </a>
                </div>
            </div>

            <!-- Columna Derecha: Cola Inmediata y Distribución (35%) -->
            <div class="lg:col-span-4 flex flex-col gap-6">

                <!-- Tarjeta 1: Cola de Atención Inmediata -->
                <div class="bg-surface-subtle border border-border-subtle p-5 rounded-2xl shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-rose-400 text-[20px]">priority_high</span>
                            <h2 class="text-sm font-bold text-on-surface">Atención Inmediata</h2>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-400 text-[11px] font-bold border border-rose-500/30">
                            {{ $immediateAttention->count() }} Requieren Acción
                        </span>
                    </div>
                    <p class="text-[11px] text-on-surface-variant -mt-1 leading-relaxed">
                        Incidencias con mayor severidad aeroportuaria o SLA próximo a expirar
                    </p>

                    <!-- Lista de Incidencias Críticas -->
                    <div class="flex flex-col gap-2.5">
                        @forelse ($immediateAttention as $urg)
                            <div class="p-3.5 rounded-xl bg-surface-container-low border border-border-subtle hover:border-border-active flex items-center justify-between gap-3 transition-colors">
                                <div class="flex flex-col min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold {{ $urg->priority->slug === 'critica' ? 'text-rose-400' : 'text-amber-400' }}">
                                            {{ $urg->folio }}
                                        </span>
                                        <span class="text-[10px] text-outline font-mono">{{ $urg->created_at->diffForHumans() }}</span>
                                    </div>
                                    <span class="text-xs text-on-surface font-semibold truncate mt-0.5" title="{{ $urg->title }}">
                                        {{ $urg->title }}
                                    </span>
                                    <span class="text-[11px] text-outline truncate mt-0.5">
                                        Asignado: {{ $urg->assignedTo?->name ?? 'Sin asignar' }}
                                    </span>
                                </div>
                                <a
                                    href="{{ route('incidents.show', $urg->folio) }}"
                                    class="shrink-0 px-3 py-1.5 bg-primary hover:bg-primary/90 text-on-primary font-bold text-xs rounded-xl shadow-sm transition-all"
                                >
                                    Atender
                                </a>
                            </div>
                        @empty
                            <div class="p-4 rounded-xl bg-surface-container-low text-center text-xs text-outline border border-border-subtle">
                                No hay incidencias críticas activas pendientes de atención.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Tarjeta 2: Incidencias por Categoría / Subsistemas -->
                <div class="bg-surface-subtle border border-border-subtle p-5 rounded-2xl shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">category</span>
                            <h2 class="text-sm font-bold text-on-surface">Carga por Subsistema</h2>
                        </div>
                        <span class="text-[11px] font-mono text-outline">
                            Total: {{ $distribution['total_active'] }} activas
                        </span>
                    </div>
                    <p class="text-[11px] text-on-surface-variant -mt-1 leading-relaxed">
                        Distribución de carga técnica por infraestructura tecnológica del AIFA
                    </p>

                    <!-- Barras de Distribución Tácticas -->
                    <div class="flex flex-col gap-3.5">
                        @foreach ($distribution['categories'] as $cat)
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="material-symbols-outlined text-[16px]" style="color: {{ $cat['color'] }};">{{ $cat['icon'] }}</span>
                                        <span class="text-on-surface font-medium truncate">{{ $cat['name'] }}</span>
                                    </div>
                                    <span class="font-mono text-xs font-semibold text-on-surface shrink-0">
                                        {{ $cat['count'] }} <span class="text-outline font-normal">({{ $cat['percentage'] }}%)</span>
                                    </span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-surface-container-low overflow-hidden border border-border-subtle/50">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        style="width: {{ $cat['percentage'] }}%; background-color: {{ $cat['color'] }};"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Resumen de Guardia al Pie -->
                    <div class="mt-2 pt-3 border-t border-border-subtle/60 flex items-center justify-between text-xs text-on-surface-variant">
                        <span>Personal GTS activo: <strong class="text-on-surface font-mono">{{ $activeTechnicians }} agentes</strong></span>
                        <span class="text-status-resolved flex items-center gap-1.5 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-status-resolved animate-pulse"></span>
                            <span>Red Operativa</span>
                        </span>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>
