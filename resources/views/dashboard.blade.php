<x-app-layout>
    <!-- Contenedor Principal Calibrado para 1920x1080 SIN SCROLL VERTICAL -->
    <div class="h-full p-4 lg:p-5 flex flex-col justify-between overflow-hidden gap-3.5 select-none">

        <!-- 1. Encabezado de Bienvenida y Telemetría Operativa (h-10) -->
        <div class="flex items-center justify-between gap-4 shrink-0">
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] uppercase font-bold tracking-widest text-blue-400">
                        Dashboard Operativo
                    </span>
                    <span class="text-brand-text-faint">•</span>
                    <span class="text-[11px] font-mono text-brand-text-muted">
                        AIFA • Centro de Control NOC/APOC
                    </span>
                    @if(auth()->user()->isGts())
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                            AIFA GTS TÉCNICO
                        </span>
                    @elseif(auth()->user()->isApoc())
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/15 text-blue-400 border border-blue-500/30">
                            AIFA APOC CONTROL
                        </span>
                    @endif
                </div>
                <div class="flex items-baseline gap-3 mt-0.5">
                    <h1 class="text-xl lg:text-2xl font-bold text-brand-text-primary tracking-tight">
                        Hola, {{ auth()->user()->name }}
                    </h1>
                    <span class="hidden md:inline-block text-xs text-brand-text-muted">
                        Monitoreo continuo y despacho de incidencias tecnológicas en tiempo real.
                    </span>
                </div>
            </div>

            <!-- Insignia Corporativa y Selector de Período -->
            <div class="flex items-center gap-3 shrink-0">
                <!-- Selector Sutil de Período -->
                <div class="flex items-center gap-1 bg-brand-surface border border-brand-border p-1 rounded-lg text-xs shadow-enterprise-sm">
                    <a 
                        href="{{ route('dashboard', ['period' => 'today']) }}"
                        class="px-2.5 py-0.5 rounded text-[11px] font-semibold transition-colors {{ $metrics['period'] === 'today' ? 'bg-brand-primary text-white shadow-sm' : 'text-brand-text-muted hover:text-brand-text-primary' }}"
                    >
                        Hoy
                    </a>
                    <a 
                        href="{{ route('dashboard', ['period' => '24h']) }}"
                        class="px-2.5 py-0.5 rounded text-[11px] font-semibold transition-colors {{ $metrics['period'] === '24h' ? 'bg-brand-primary text-white shadow-sm' : 'text-brand-text-muted hover:text-brand-text-primary' }}"
                    >
                        24h
                    </a>
                    <a 
                        href="{{ route('dashboard', ['period' => 'week']) }}"
                        class="px-2.5 py-0.5 rounded text-[11px] font-semibold transition-colors {{ $metrics['period'] === 'week' ? 'bg-brand-primary text-white shadow-sm' : 'text-brand-text-muted hover:text-brand-text-primary' }}"
                    >
                        Semana
                    </a>
                </div>

                <!-- Insignia Hexagonal GIT -->
                <div class="hidden xl:flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-brand-surface border border-brand-border shadow-enterprise-sm">
                    <x-application-logo class="w-6 h-6 text-brand-primary shrink-0" />
                    <div class="flex flex-col text-left leading-none">
                        <span class="text-xs font-bold text-brand-text-primary tracking-wider">GIT</span>
                        <span class="text-[9px] text-brand-text-muted font-medium">AIFA OPS</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Fila de 4 KPIs Tácticos Principales (h-22 / 88px) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 shrink-0">
            
            <!-- KPI 1: INCIDENCIAS ABIERTAS (con desglose por prioridad) -->
            <div class="bg-brand-surface border border-brand-border rounded-xl p-3.5 flex items-center justify-between gap-3 shadow-enterprise-sm transition-all hover:border-[#24385e] group">
                <div class="flex flex-col min-w-0 flex-1">
                    <span class="text-[11px] uppercase font-bold text-brand-text-muted tracking-wider">
                        Incidencias Abiertas
                    </span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="font-mono text-2xl lg:text-3xl font-bold text-brand-text-primary tabular-nums">
                            {{ $metrics['total_open'] }}
                        </span>
                        <span class="text-[11px] text-rose-400 font-medium">
                            Requieren atención
                        </span>
                    </div>
                    <!-- Desglose compacto de prioridades -->
                    <div class="flex items-center gap-2 mt-1 text-[10px] font-mono text-brand-text-muted">
                        <span class="flex items-center gap-1 text-rose-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            {{ $metrics['open_critical'] }} crít.
                        </span>
                        <span class="text-brand-text-faint">•</span>
                        <span class="flex items-center gap-1 text-orange-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                            {{ $metrics['open_high'] }} alta
                        </span>
                        <span class="text-brand-text-faint">•</span>
                        <span class="flex items-center gap-1 text-yellow-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span>
                            {{ max(0, $metrics['total_open'] - $metrics['open_critical'] - $metrics['open_high']) }} med/baja
                        </span>
                    </div>
                </div>
                <div class="w-10 h-10 rounded-full bg-state-critical-bg border border-state-critical-border flex items-center justify-center text-state-critical shrink-0">
                    <span class="material-symbols-outlined text-[20px]">priority_high</span>
                </div>
            </div>

            <!-- KPI 2: EN ATENCIÓN -->
            <div class="bg-brand-surface border border-brand-border rounded-xl p-3.5 flex items-center justify-between gap-3 shadow-enterprise-sm transition-all hover:border-[#24385e] group">
                <div class="flex flex-col min-w-0 flex-1">
                    <span class="text-[11px] uppercase font-bold text-brand-text-muted tracking-wider">
                        EN ATENCIÓN
                    </span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="font-mono text-2xl lg:text-3xl font-bold text-brand-text-primary tabular-nums">
                            {{ $metrics['in_attention'] }}
                        </span>
                        <span class="text-[11px] text-amber-400 font-medium">
                            En proceso técnico
                        </span>
                    </div>
                    <span class="text-[10px] text-brand-text-muted mt-1">
                        {{ $metrics['pending_vobo'] }} pendientes de VoBo APOC
                    </span>
                </div>
                <div class="w-10 h-10 rounded-full bg-state-high-bg border border-state-high-border flex items-center justify-center text-state-high shrink-0">
                    <span class="material-symbols-outlined text-[20px]">schedule</span>
                </div>
            </div>

            <!-- KPI 3: RESUELTAS -->
            <div class="bg-brand-surface border border-brand-border rounded-xl p-3.5 flex items-center justify-between gap-3 shadow-enterprise-sm transition-all hover:border-[#24385e] group">
                <div class="flex flex-col min-w-0 flex-1">
                    <span class="text-[11px] uppercase font-bold text-brand-text-muted tracking-wider">
                        RESUELTAS
                    </span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="font-mono text-2xl lg:text-3xl font-bold text-brand-text-primary tabular-nums">
                            {{ $metrics['resolved_in_period'] }}
                        </span>
                        <span class="text-[11px] text-emerald-400 font-medium">
                            En el período
                        </span>
                    </div>
                    <span class="text-[10px] text-brand-text-muted mt-1">
                        Tasa de efectividad operativa alta
                    </span>
                </div>
                <div class="w-10 h-10 rounded-full bg-state-low-bg border border-state-low-border flex items-center justify-center text-state-low shrink-0">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                </div>
            </div>

            <!-- KPI 4: TIEMPO PROMEDIO (MTTR Sanitizado Positivo Real) -->
            <div class="bg-brand-surface border border-brand-border rounded-xl p-3.5 flex items-center justify-between gap-3 shadow-enterprise-sm transition-all hover:border-[#24385e] group">
                <div class="flex flex-col min-w-0 flex-1">
                    <span class="text-[11px] uppercase font-bold text-brand-text-muted tracking-wider">
                        TIEMPO PROMEDIO (MTTR)
                    </span>
                    <div class="flex items-baseline gap-2 mt-0.5">
                        <span class="font-mono text-2xl lg:text-3xl font-bold text-brand-text-primary tabular-nums">
                            {{ max(0, $metrics['mttr_minutes']) }} min
                        </span>
                        <span class="text-[11px] font-medium {{ $metrics['mttr_within_target'] ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $metrics['mttr_within_target'] ? 'Objetivo cumplido' : 'Excede SLA' }}
                        </span>
                    </div>
                    <span class="text-[10px] text-brand-text-muted mt-1">
                        Meta operativa institucional: ≤ 25 min
                    </span>
                </div>
                <div class="w-10 h-10 rounded-full bg-state-info-bg border border-state-info-border flex items-center justify-center text-state-info shrink-0">
                    <span class="material-symbols-outlined text-[20px]">timer</span>
                </div>
            </div>

        </div>

        <!-- 3. Grid Principal Operativo (Flex-1 sin scroll vertical) -->
        <div class="flex-1 min-h-0 grid grid-cols-12 gap-3.5">
            
            <!-- Columna Izquierda: Incidencias Recientes (8 de 12 columnas ~ 67%) -->
            <div class="col-span-12 lg:col-span-8 bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col overflow-hidden h-full">
                
                <!-- Encabezado de la Tabla -->
                <div class="px-4 py-2.5 border-b border-brand-border-subtle flex items-center justify-between bg-brand-surface-subtle/50 shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-brand-text-muted">bookmark</span>
                        <h2 class="text-xs font-bold text-brand-text-primary uppercase tracking-wider">
                            Incidencias recientes
                        </h2>
                    </div>
                    <a 
                        href="{{ route('incidents.index') }}" 
                        class="text-xs font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1 transition-colors"
                    >
                        <span>Ver todas</span>
                        <span class="text-[14px]">→</span>
                    </a>
                </div>

                <!-- Tabla de Alta Densidad (Exactamente 6-7 filas visibles) -->
                <div class="flex-1 overflow-y-auto scrollbar-thin flex flex-col">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-brand-surface-subtle/30 text-[10px] uppercase font-bold text-brand-text-muted tracking-wider border-b border-brand-border-subtle sticky top-0 bg-brand-surface z-10">
                                <th class="py-2.5 px-3.5 font-mono">Folio</th>
                                <th class="py-2.5 px-3">Título</th>
                                <th class="py-2.5 px-3">Categoría</th>
                                <th class="py-2.5 px-3">Prioridad</th>
                                <th class="py-2.5 px-3">Estado</th>
                                <th class="py-2.5 px-3">Área</th>
                                <th class="py-2.5 px-3">Fecha</th>
                                <th class="py-2.5 px-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-border-subtle text-xs text-brand-text-primary">
                            @forelse ($recentIncidents->take(7) as $inc)
                                <tr class="hover:bg-brand-surface-subtle/60 transition-colors group">
                                    
                                    <!-- Folio Monoespaciado -->
                                    <td class="py-2.5 px-3.5 whitespace-nowrap font-mono font-bold text-[11px] text-blue-400">
                                        <a href="{{ route('incidents.show', $inc->folio) }}" class="hover:underline">
                                            {{ $inc->folio }}
                                        </a>
                                    </td>

                                    <!-- Título / Asunto -->
                                    <td class="py-2.5 px-3 max-w-[180px] xl:max-w-[240px]">
                                        <span class="truncate block font-medium text-slate-200" title="{{ $inc->title }}">
                                            {{ $inc->title }}
                                        </span>
                                    </td>

                                    <!-- Categoría -->
                                    <td class="py-2.5 px-3 whitespace-nowrap text-brand-text-secondary text-[11px]">
                                        {{ $inc->category?->name ?? 'Tecnología' }}
                                    </td>

                                    <!-- Prioridad (Componente Funcional) -->
                                    <td class="py-2.5 px-3 whitespace-nowrap">
                                        <x-badge-priority :priority="$inc->priority" />
                                    </td>

                                    <!-- Estado (Componente Funcional) -->
                                    <td class="py-2.5 px-3 whitespace-nowrap">
                                        <x-badge-status :status="$inc->status" />
                                    </td>

                                    <!-- Área / Ubicación -->
                                    <td class="py-2.5 px-3 whitespace-nowrap text-brand-text-muted text-[11px] max-w-[100px] truncate" title="{{ $inc->location }}">
                                        {{ $inc->location ?? 'AIFA' }}
                                    </td>

                                    <!-- Fecha Formateada -->
                                    <td class="py-2.5 px-3 whitespace-nowrap font-mono text-[10px] text-brand-text-muted">
                                        {{ $inc->created_at->format('d M. Y') }}
                                        <span class="text-brand-text-faint ml-1">{{ $inc->created_at->format('H:i') }}</span>
                                    </td>

                                    <!-- Acción: Link Directo -->
                                    <td class="py-2.5 px-3 whitespace-nowrap text-right text-brand-text-muted group-hover:text-brand-text-primary">
                                        <a 
                                            href="{{ route('incidents.show', $inc->folio) }}"
                                            class="p-1 rounded hover:bg-brand-surface inline-flex items-center justify-center transition-colors"
                                            title="Ver expediente pericial"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        </a>
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-brand-text-muted text-xs">
                                        No hay incidencias registradas en la plataforma.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pie de tabla informativo -->
                <div class="px-4 py-2 bg-brand-surface-subtle/30 border-t border-brand-border-subtle flex items-center justify-between text-xs text-brand-text-muted shrink-0">
                    <span class="font-mono text-[11px]">
                        Mostrando {{ $recentIncidents->take(7)->count() }} de {{ $metrics['total_open'] }} incidencias activas
                    </span>
                    <a href="{{ route('incidents.index') }}" class="text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1 transition-colors">
                        <span>Ir al Tablero Completo</span>
                        <span class="text-[13px]">→</span>
                    </a>
                </div>

            </div>

            <!-- Columna Derecha: Atención Inmediata + Carga/Actividad (4 de 12 columnas ~ 33%) -->
            <div class="col-span-12 lg:col-span-4 flex flex-col gap-3.5 h-full overflow-hidden">
                
                <!-- Panel 1: Cola de Atención Inmediata (Criticidad Alta / SLA Urgente) -->
                <div class="bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col overflow-hidden h-[45%] shrink-0">
                    
                    <div class="px-4 py-2 border-b border-brand-border-subtle flex items-center justify-between bg-brand-surface-subtle/50 shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-rose-400">priority_high</span>
                            <h2 class="text-xs font-bold text-brand-text-primary uppercase tracking-wider">
                                Atención Inmediata
                            </h2>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-400 text-[10px] font-bold border border-rose-500/30">
                            {{ $immediateAttention->count() }} Requieren Acción
                        </span>
                    </div>

                    <!-- Lista de Tickets de Atención Inmediata -->
                    <div class="p-3 space-y-2 flex-1 overflow-y-auto scrollbar-thin">
                        @forelse ($immediateAttention as $urg)
                            <div class="p-2.5 rounded-lg bg-brand-surface-subtle/60 border border-brand-border hover:border-brand-border-active flex items-center justify-between gap-2.5 transition-colors">
                                <div class="flex flex-col min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold {{ $urg->priority->slug === 'critica' ? 'text-rose-400' : 'text-amber-400' }}">
                                            {{ $urg->folio }}
                                        </span>
                                        <span class="text-[10px] text-brand-text-muted font-mono">
                                            {{ $urg->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                    <span class="text-xs text-slate-200 font-semibold truncate mt-0.5" title="{{ $urg->title }}">
                                        {{ $urg->title }}
                                    </span>
                                    <span class="text-[10px] text-brand-text-muted truncate">
                                        Asignado: {{ $urg->assignedTo?->name ?? 'Sin asignar' }}
                                    </span>
                                </div>
                                <a
                                    href="{{ route('incidents.show', $urg->folio) }}"
                                    class="shrink-0 px-2.5 py-1 bg-brand-primary hover:bg-blue-600 text-white font-bold text-[11px] rounded-lg shadow-enterprise-sm transition-all"
                                >
                                    Atender
                                </a>
                            </div>
                        @empty
                            <div class="p-4 rounded-lg bg-brand-surface-subtle/30 text-center text-xs text-brand-text-muted border border-brand-border-subtle">
                                No hay incidencias críticas activas pendientes de atención.
                            </div>
                        @endforelse
                    </div>

                </div>

                <!-- Panel 2: Tabs Carga por Subsistema & Actividad Reciente -->
                <div 
                    x-data="{ activeTab: 'subsistemas' }"
                    class="bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col overflow-hidden flex-1 min-h-0"
                >
                    
                    <!-- Encabezado con Tabs Tácticos -->
                    <div class="px-3 py-1.5 border-b border-brand-border-subtle flex items-center justify-between bg-brand-surface-subtle/50 shrink-0">
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="activeTab = 'subsistemas'"
                                :class="activeTab === 'subsistemas' ? 'bg-brand-surface text-brand-text-primary border-brand-border shadow-enterprise-sm' : 'text-brand-text-muted hover:text-brand-text-primary border-transparent'"
                                class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider border transition-all flex items-center gap-1.5"
                            >
                                <span class="material-symbols-outlined text-[15px]">category</span>
                                <span>Carga por Subsistema</span>
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'actividad'"
                                :class="activeTab === 'actividad' ? 'bg-brand-surface text-brand-text-primary border-brand-border shadow-enterprise-sm' : 'text-brand-text-muted hover:text-brand-text-primary border-transparent'"
                                class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider border transition-all flex items-center gap-1.5"
                            >
                                <span class="material-symbols-outlined text-[15px]">history</span>
                                <span>Actividad reciente</span>
                            </button>
                        </div>
                        <span x-show="activeTab === 'subsistemas'" class="text-[10px] font-mono text-brand-text-muted">
                            {{ $distribution['total_active'] }} activas
                        </span>
                        <a 
                            x-show="activeTab === 'actividad'"
                            href="{{ route('audit.index') }}" 
                            class="text-xs font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1 transition-colors"
                        >
                            <span>Bitácora</span>
                            <span class="text-[13px]">→</span>
                        </a>
                    </div>

                    <!-- Contenido Tab 1: Carga por Subsistema -->
                    <div x-show="activeTab === 'subsistemas'" class="p-3.5 space-y-2.5 flex-1 overflow-y-auto scrollbar-thin">
                        <div class="flex flex-col gap-2.5">
                            @foreach ($distribution['categories'] as $cat)
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="material-symbols-outlined text-[15px]" style="color: {{ $cat['color'] }};">{{ $cat['icon'] }}</span>
                                            <span class="text-slate-200 font-medium truncate text-[11px]">{{ $cat['name'] }}</span>
                                        </div>
                                        <span class="font-mono text-xs font-semibold text-brand-text-primary shrink-0">
                                            {{ $cat['count'] }} <span class="text-brand-text-muted font-normal text-[10px]">({{ $cat['percentage'] }}%)</span>
                                        </span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-brand-surface-subtle overflow-hidden border border-brand-border-subtle/50">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            style="width: {{ $cat['percentage'] }}%; background-color: {{ $cat['color'] }};"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Resumen de Personal Técnico al Pie -->
                        <div class="mt-2 pt-2.5 border-t border-brand-border-subtle flex items-center justify-between text-[11px] text-brand-text-muted">
                            <span>Personal GTS en guardia: <strong class="text-brand-text-primary font-mono">{{ $activeTechnicians }} agentes</strong></span>
                            <span class="text-emerald-400 flex items-center gap-1 font-semibold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Red Operativa AIFA</span>
                            </span>
                        </div>
                    </div>

                    <!-- Contenido Tab 2: Actividad Reciente (Timeline Pericial) -->
                    <div x-show="activeTab === 'actividad'" class="p-3 space-y-2.5 flex-1 overflow-y-auto scrollbar-thin" style="display: none;">
                        @forelse($recentActivities as $act)
                            <div class="flex items-start gap-2.5 group">
                                <div class="mt-1 w-2 h-2 rounded-full shrink-0 
                                    @if($act->action === 'RESUELTA' || $act->action === 'CERRADA') bg-emerald-400
                                    @elseif($act->action === 'ASIGNADA' || $act->action === 'CAMBIO_ESTADO') bg-blue-400
                                    @elseif($act->action === 'NOTA_TECNICA') bg-amber-400
                                    @elseif($act->action === 'RECHAZO_SOLUCION') bg-rose-400
                                    @else bg-slate-400 @endif">
                                </div>
                                
                                <div class="flex flex-col min-w-0 flex-1 leading-tight">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-mono font-bold text-[10px] text-blue-400 truncate">
                                            {{ $act->incident?->folio ?? 'INC-AEROPUERTO' }}
                                        </span>
                                        <span class="font-mono text-[9px] text-brand-text-muted whitespace-nowrap">
                                            {{ $act->created_at->diffForHumans(null, true) }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-slate-300 truncate mt-0.5" title="{{ $act->comment ?? $act->action }}">
                                        @if($act->action === 'CAMBIO_ESTADO')
                                            Estado: {{ $act->old_value }} → {{ $act->new_value }}
                                        @elseif($act->action === 'ASIGNADA')
                                            Asignada a: {{ $act->new_value }}
                                        @elseif($act->action === 'NOTA_TECNICA')
                                            Nota por {{ $act->user?->name ?? 'GTS' }}: {{ Str::limit($act->comment, 28) }}
                                        @elseif($act->action === 'RESUELTA')
                                            Solución técnica documentada
                                        @elseif($act->action === 'RECHAZO_SOLUCION')
                                            Solución rechazada por APOC
                                        @elseif($act->action === 'CERRADA')
                                            Cierre definitivo con visto bueno
                                        @else
                                            {{ $act->comment ?? $act->action }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-brand-text-muted">
                                Sin eventos recientes registrados en la bitácora.
                            </div>
                        @endforelse
                    </div>

                </div>

            </div>

        </div>

    </div>
</x-app-layout>
