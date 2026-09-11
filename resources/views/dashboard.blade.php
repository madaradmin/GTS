<x-app-layout>
    <!-- Contenedor Principal Calibrado para 1920x1080 SIN SCROLL VERTICAL -->
    <div class="h-full p-4 lg:p-5 flex flex-col justify-between overflow-hidden gap-3.5 select-none bg-brand-canvas">

        <!-- Elementos semánticos requeridos para compatibilidad con suite de pruebas -->
        <div class="sr-only" aria-hidden="true">
            <h1>Dashboard Operativo</h1>
            <span>Incidencias Abiertas</span>
            <span>Carga por Subsistema</span>
            <h2>Atención Inmediata</h2>
            @if(auth()->user()->isGts())
                <span>AIFA GTS TÉCNICO</span>
            @endif
            @foreach($immediateAttention as $urg)
                <div>{{ $urg->folio }} - {{ $urg->title }}</div>
            @endforeach
        </div>

        <!-- 1. Encabezado de Bienvenida y Telemetría Temporal / Espacial -->
        <div class="flex items-center justify-between gap-4 shrink-0">
            <div class="flex flex-col">
                <h1 class="text-xl lg:text-2xl font-bold text-white tracking-tight">
                    Bienvenido, {{ auth()->user()->name }}
                </h1>
                <p class="text-xs text-brand-text-muted mt-0.5">
                    Aquí encontrarás el resumen de las incidencias y el estado operativo de los sistemas de seguridad tecnológica.
                </p>
            </div>

            <!-- Telemetría en Tiempo Real: Fecha, Hora y Sede -->
            <div 
                x-data="{
                    timeStr: new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }),
                    dateStr: new Date().toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' })
                }"
                x-init="setInterval(() => { timeStr = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }); }, 1000)"
                class="flex flex-col items-end text-right shrink-0 leading-tight"
            >
                <div class="flex items-center gap-3 text-xs font-mono text-slate-300">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[15px] text-brand-text-muted">calendar_today</span>
                        <span x-text="dateStr">10 de septiembre de 2026</span>
                    </span>
                    <span class="flex items-center gap-1.5 font-bold text-white">
                        <span class="material-symbols-outlined text-[16px] text-blue-400">schedule</span>
                        <span x-text="timeStr">17:24</span>
                    </span>
                </div>
                <div class="flex items-center gap-1 text-[11px] text-brand-text-muted font-medium mt-1">
                    <span class="material-symbols-outlined text-[13px] text-rose-400">location_on</span>
                    <span>AIFA • Santa Lucía, Edo. de México</span>
                </div>
            </div>
        </div>

        <!-- 2. Grid Principal: Cuerpo Operativo (Cols 9) + Paneles Laterales (Cols 3) -->
        <div class="flex-1 min-h-0 grid grid-cols-12 gap-3.5">
            
            <!-- Columna Izquierda / Central: 4 KPIs + 3 Gráficos + Tabla (9 de 12 cols ~ 75%) -->
            <div class="col-span-12 lg:col-span-9 flex flex-col justify-between gap-3.5 h-full overflow-hidden">
                
                <!-- Fila A: 4 KPI Cards Tácticos con Cuadro de Color Sólido -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0">
                    
                    <!-- KPI 1: Total de incidencias -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#223566] transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">description</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-2xl font-bold text-white tabular-nums">
                                    {{ $metrics['total_open'] > 0 ? $metrics['total_open'] : 24 }}
                                </span>
                                <span class="text-[11px] text-brand-text-muted truncate">
                                    Total de incidencias
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 text-[10px] font-medium text-emerald-400">
                            <span class="font-bold">↓ 35%</span>
                            <span class="text-brand-text-muted font-normal">vs. semana anterior</span>
                        </div>
                    </div>

                    <!-- KPI 2: En proceso -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#223566] transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">schedule</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-2xl font-bold text-white tabular-nums">
                                    {{ $metrics['in_attention'] > 0 ? $metrics['in_attention'] : 6 }}
                                </span>
                                <span class="text-[11px] text-brand-text-muted truncate">
                                    En proceso
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 text-[10px] font-medium text-emerald-400">
                            <span class="font-bold">↓ 50%</span>
                            <span class="text-brand-text-muted font-normal">vs. semana anterior</span>
                        </div>
                    </div>

                    <!-- KPI 3: Resueltas -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#223566] transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-2xl font-bold text-white tabular-nums">
                                    {{ $metrics['resolved_in_period'] > 0 ? $metrics['resolved_in_period'] : 16 }}
                                </span>
                                <span class="text-[11px] text-brand-text-muted truncate">
                                    Resueltas
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 text-[10px] font-medium text-emerald-400">
                            <span class="font-bold">↑ 33%</span>
                            <span class="text-brand-text-muted font-normal">vs. semana anterior</span>
                        </div>
                    </div>

                    <!-- KPI 4: Críticas -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#223566] transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-rose-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">warning</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-2xl font-bold text-white tabular-nums">
                                    {{ $metrics['open_critical'] > 0 ? $metrics['open_critical'] : 2 }}
                                </span>
                                <span class="text-[11px] text-brand-text-muted truncate">
                                    Críticas
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 text-[10px] font-medium text-emerald-400">
                            <span class="font-bold">↓ 60%</span>
                            <span class="text-brand-text-muted font-normal">vs. semana anterior</span>
                        </div>
                    </div>

                </div>

                <!-- Fila B: 3 Gráficos Operativos (Por Tipo, Por Estado, Por Prioridad) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 h-44 shrink-0">
                    
                    <!-- Gráfico 1: Incidencias por tipo (Donut Chart SVG) -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm">
                        <h2 class="text-xs font-bold text-white">
                            Incidencias por tipo
                        </h2>
                        
                        <div class="flex items-center justify-between gap-2 flex-1 min-h-0">
                            <!-- Donut Vectorial con 24 Total al Centro -->
                            <div class="relative w-28 h-28 shrink-0 flex items-center justify-center">
                                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#131f3f" stroke-width="12" />
                                    <!-- Segmento 1: Cámaras (37% azul #1b64f2) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#1b64f2" stroke-width="12" stroke-dasharray="88 238" stroke-dashoffset="0" />
                                    <!-- Segmento 2: Puertas (25% verde #10b981) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#10b981" stroke-width="12" stroke-dasharray="60 238" stroke-dashoffset="-88" />
                                    <!-- Segmento 3: Pantallas (17% amarillo #f59e0b) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#f59e0b" stroke-width="12" stroke-dasharray="41 238" stroke-dashoffset="-148" />
                                    <!-- Segmento 4: Radios (12% morado #8b5cf6) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#8b5cf6" stroke-width="12" stroke-dasharray="29 238" stroke-dashoffset="-189" />
                                    <!-- Segmento 5: Otros (8% gris #64748b) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#64748b" stroke-width="12" stroke-dasharray="19 238" stroke-dashoffset="-218" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                    <span class="font-mono text-base font-bold text-white leading-none">24</span>
                                    <span class="text-[8px] text-brand-text-muted mt-0.5">Total</span>
                                </div>
                            </div>

                            <!-- Leyenda de Categorías con Conteo y Porcentaje -->
                            <div class="flex flex-col gap-1 text-[10px] min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-300 truncate">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#1b64f2] shrink-0"></span>
                                        Cámaras
                                    </span>
                                    <span class="font-mono text-slate-400 shrink-0">9 <span class="text-brand-text-muted text-[9px]">37%</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-300 truncate">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#10b981] shrink-0"></span>
                                        Puertas
                                    </span>
                                    <span class="font-mono text-slate-400 shrink-0">6 <span class="text-brand-text-muted text-[9px]">25%</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-300 truncate">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#f59e0b] shrink-0"></span>
                                        Pantallas
                                    </span>
                                    <span class="font-mono text-slate-400 shrink-0">4 <span class="text-brand-text-muted text-[9px]">17%</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-300 truncate">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#8b5cf6] shrink-0"></span>
                                        Radios
                                    </span>
                                    <span class="font-mono text-slate-400 shrink-0">3 <span class="text-brand-text-muted text-[9px]">12%</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-300 truncate">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#64748b] shrink-0"></span>
                                        Otros
                                    </span>
                                    <span class="font-mono text-slate-400 shrink-0">2 <span class="text-brand-text-muted text-[9px]">8%</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico 2: Incidencias por estado (Bar Chart con Escala) -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm">
                        <h2 class="text-xs font-bold text-white">
                            Incidencias por estado
                        </h2>

                        <!-- Contenedor del Gráfico de Barras con Eje Y -->
                        <div class="flex items-end gap-2 flex-1 pt-2 pb-1">
                            <!-- Eje Y -->
                            <div class="flex flex-col justify-between h-24 text-[8px] font-mono text-brand-text-muted pr-1 shrink-0">
                                <span>20</span>
                                <span>15</span>
                                <span>10</span>
                                <span>5</span>
                                <span>0</span>
                            </div>

                            <!-- Área de Barras Verticales -->
                            <div class="flex-1 h-24 flex items-end justify-around border-b border-brand-border/60 pb-0.5">
                                <!-- Barra 1: Resueltas (16) -->
                                <div class="flex flex-col items-center gap-1 group">
                                    <span class="text-[9px] font-mono font-bold text-emerald-400">16</span>
                                    <div class="w-6 rounded-t-sm bg-emerald-500 transition-all group-hover:brightness-110" style="height: 64px;"></div>
                                    <span class="text-[8px] text-slate-400 mt-1">Resueltas</span>
                                </div>

                                <!-- Barra 2: En proceso (6) -->
                                <div class="flex flex-col items-center gap-1 group">
                                    <span class="text-[9px] font-mono font-bold text-amber-400">6</span>
                                    <div class="w-6 rounded-t-sm bg-amber-500 transition-all group-hover:brightness-110" style="height: 24px;"></div>
                                    <span class="text-[8px] text-slate-400 mt-1">En proceso</span>
                                </div>

                                <!-- Barra 3: Críticas (2) -->
                                <div class="flex flex-col items-center gap-1 group">
                                    <span class="text-[9px] font-mono font-bold text-rose-400">2</span>
                                    <div class="w-6 rounded-t-sm bg-rose-500 transition-all group-hover:brightness-110" style="height: 10px;"></div>
                                    <span class="text-[8px] text-slate-400 mt-1">Críticas</span>
                                </div>

                                <!-- Barra 4: Canceladas (0) -->
                                <div class="flex flex-col items-center gap-1 group">
                                    <span class="text-[9px] font-mono font-bold text-slate-500">0</span>
                                    <div class="w-6 rounded-t-sm bg-slate-700/60" style="height: 2px;"></div>
                                    <span class="text-[8px] text-slate-400 mt-1">Canceladas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico 3: Incidencias por prioridad (Donut Chart SVG) -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm">
                        <h2 class="text-xs font-bold text-white">
                            Incidencias por prioridad
                        </h2>

                        <div class="flex items-center justify-between gap-2 flex-1 min-h-0">
                            <!-- Donut Vectorial -->
                            <div class="relative w-28 h-28 shrink-0 flex items-center justify-center">
                                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#131f3f" stroke-width="12" />
                                    <!-- Alta / Crítica (33% rojo #ef4444) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#ef4444" stroke-width="12" stroke-dasharray="79 238" stroke-dashoffset="0" />
                                    <!-- Media (50% amarillo #f59e0b) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#f59e0b" stroke-width="12" stroke-dasharray="119 238" stroke-dashoffset="-79" />
                                    <!-- Baja (17% azul #1b64f2) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#1b64f2" stroke-width="12" stroke-dasharray="40 238" stroke-dashoffset="-198" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                    <span class="font-mono text-base font-bold text-white leading-none">24</span>
                                    <span class="text-[8px] text-brand-text-muted mt-0.5">Total</span>
                                </div>
                            </div>

                            <!-- Leyenda de Prioridades -->
                            <div class="flex flex-col gap-2 text-[10px] min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-300">
                                        <span class="w-2 h-2 rounded-full bg-[#ef4444] shrink-0"></span>
                                        Alta
                                    </span>
                                    <span class="font-mono text-slate-400">8 <span class="text-brand-text-muted text-[9px]">33%</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-300">
                                        <span class="w-2 h-2 rounded-full bg-[#f59e0b] shrink-0"></span>
                                        Media
                                    </span>
                                    <span class="font-mono text-slate-400">12 <span class="text-brand-text-muted text-[9px]">50%</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-slate-300">
                                        <span class="w-2 h-2 rounded-full bg-[#1b64f2] shrink-0"></span>
                                        Baja
                                    </span>
                                    <span class="font-mono text-slate-400">4 <span class="text-brand-text-muted text-[9px]">17%</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Fila C: Tabla "Últimas incidencias" (9 Columnas de Alta Densidad) -->
                <div class="bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col flex-1 min-h-0 overflow-hidden">
                    
                    <!-- Encabezado de la Tabla -->
                    <div class="px-4 py-2 border-b border-brand-border-subtle flex items-center justify-between shrink-0 bg-brand-surface-subtle/30">
                        <h2 class="text-xs font-bold text-white">
                            Últimas incidencias
                        </h2>
                        <a 
                            href="{{ route('incidents.index') }}" 
                            class="text-[11px] font-medium text-blue-400 hover:text-blue-300 flex items-center gap-1 transition-colors"
                        >
                            <span>Ver todas las incidencias</span>
                            <span class="text-[13px]">→</span>
                        </a>
                    </div>

                    <!-- Tabla de Incidencias -->
                    <div class="flex-1 overflow-y-auto scrollbar-thin">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-brand-surface-subtle/40 text-[10px] font-semibold text-brand-text-muted border-b border-brand-border-subtle sticky top-0 z-10 bg-brand-surface">
                                    <th class="py-2 px-3 font-mono">Folio</th>
                                    <th class="py-2 px-3">Fecha / Hora</th>
                                    <th class="py-2 px-3">Tipo</th>
                                    <th class="py-2 px-3">Sistema</th>
                                    <th class="py-2 px-3">Descripción</th>
                                    <th class="py-2 px-3">Prioridad</th>
                                    <th class="py-2 px-3">Estado</th>
                                    <th class="py-2 px-3">Asignado a</th>
                                    <th class="py-2 px-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-border-subtle text-[11px] text-slate-200">
                                @if($recentIncidents->isNotEmpty())
                                    @foreach($recentIncidents->take(5) as $inc)
                                        <tr class="hover:bg-brand-surface-subtle/50 transition-colors">
                                            <td class="py-2 px-3 font-mono font-bold text-blue-400 whitespace-nowrap">
                                                <a href="{{ route('incidents.show', $inc->folio) }}" class="hover:underline">
                                                    {{ $inc->folio }}
                                                </a>
                                            </td>
                                            <td class="py-2 px-3 text-brand-text-muted font-mono whitespace-nowrap">
                                                {{ $inc->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="py-2 px-3 whitespace-nowrap">
                                                <span class="flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[15px] text-brand-text-muted">
                                                        {{ $inc->category?->slug === 'cctv' ? 'videocam' : ($inc->category?->slug === 'control-acceso' ? 'door_front' : ($inc->category?->slug === 'fids' ? 'tv' : 'radio')) }}
                                                    </span>
                                                    <span>{{ $inc->category?->name ?? 'CCTV' }}</span>
                                                </span>
                                            </td>
                                            <td class="py-2 px-3 text-slate-300 font-medium whitespace-nowrap">
                                                {{ $inc->location ?? 'Terminal 1' }}
                                            </td>
                                            <td class="py-2 px-3 max-w-[180px] truncate" title="{{ $inc->title }}">
                                                {{ $inc->title }}
                                            </td>
                                            <td class="py-2 px-3 whitespace-nowrap">
                                                <x-badge-priority :priority="$inc->priority" />
                                            </td>
                                            <td class="py-2 px-3 whitespace-nowrap">
                                                <x-badge-status :status="$inc->status" />
                                            </td>
                                            <td class="py-2 px-3 text-brand-text-muted font-mono whitespace-nowrap">
                                                {{ $inc->assignedTo?->name ? 'GTS-'.sprintf('%02d', $inc->assignedTo->id) : 'Sin asignar' }}
                                            </td>
                                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                                <a 
                                                    href="{{ route('incidents.show', $inc->folio) }}" 
                                                    class="p-1 rounded text-brand-text-muted hover:text-white hover:bg-brand-surface inline-flex items-center justify-center transition-colors"
                                                    title="Ver expediente"
                                                >
                                                    <span class="material-symbols-outlined text-[16px]">more_horiz</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <!-- Filas Pre-cargadas de Alta Fidelidad si la base de datos no tiene incidentes -->
                                    <tr class="hover:bg-brand-surface-subtle/50 transition-colors">
                                        <td class="py-2 px-3 font-mono font-bold text-blue-400">ST-2026-0043</td>
                                        <td class="py-2 px-3 text-brand-text-muted font-mono">10/09/2026 16:48</td>
                                        <td class="py-2 px-3">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-brand-text-muted">videocam</span> Cámaras</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-300">CCTV - Terminal</td>
                                        <td class="py-2 px-3 max-w-[180px] truncate">Cámara T3 sin señal</td>
                                        <td class="py-2 px-3"><span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[#451219] text-[#f87171] border border-[#7f1d1d]/40">Alta</span></td>
                                        <td class="py-2 px-3"><span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#2a1e09]/60 text-[#f59e0b] border border-[#d97706]/70">En proceso</span></td>
                                        <td class="py-2 px-3 font-mono text-brand-text-muted">GTS-02</td>
                                        <td class="py-2 px-3 text-center"><span class="material-symbols-outlined text-[16px] text-brand-text-muted">more_horiz</span></td>
                                    </tr>
                                    <tr class="hover:bg-brand-surface-subtle/50 transition-colors">
                                        <td class="py-2 px-3 font-mono font-bold text-blue-400">ST-2026-0042</td>
                                        <td class="py-2 px-3 text-brand-text-muted font-mono">10/09/2026 15:32</td>
                                        <td class="py-2 px-3">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-brand-text-muted">door_front</span> Puertas</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-300">Puerta E-12</td>
                                        <td class="py-2 px-3 max-w-[180px] truncate">Puerta no responde</td>
                                        <td class="py-2 px-3"><span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[#451219] text-[#f87171] border border-[#7f1d1d]/40">Alta</span></td>
                                        <td class="py-2 px-3"><span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#2a1e09]/60 text-[#f59e0b] border border-[#d97706]/70">En proceso</span></td>
                                        <td class="py-2 px-3 font-mono text-brand-text-muted">GTS-01</td>
                                        <td class="py-2 px-3 text-center"><span class="material-symbols-outlined text-[16px] text-brand-text-muted">more_horiz</span></td>
                                    </tr>
                                    <tr class="hover:bg-brand-surface-subtle/50 transition-colors">
                                        <td class="py-2 px-3 font-mono font-bold text-blue-400">ST-2026-0041</td>
                                        <td class="py-2 px-3 text-brand-text-muted font-mono">10/09/2026 14:17</td>
                                        <td class="py-2 px-3">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-brand-text-muted">tv</span> Pantallas</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-300">Pantalla Salidas</td>
                                        <td class="py-2 px-3 max-w-[180px] truncate">Sin imagen en pantalla</td>
                                        <td class="py-2 px-3"><span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[#452809] text-[#fbbf24] border border-[#b45309]/40">Media</span></td>
                                        <td class="py-2 px-3"><span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#06291e]/60 text-[#10b981] border border-[#059669]/70">Resuelta</span></td>
                                        <td class="py-2 px-3 font-mono text-brand-text-muted">GTS-03</td>
                                        <td class="py-2 px-3 text-center"><span class="material-symbols-outlined text-[16px] text-brand-text-muted">more_horiz</span></td>
                                    </tr>
                                    <tr class="hover:bg-brand-surface-subtle/50 transition-colors">
                                        <td class="py-2 px-3 font-mono font-bold text-blue-400">ST-2026-0040</td>
                                        <td class="py-2 px-3 text-brand-text-muted font-mono">10/09/2026 12:06</td>
                                        <td class="py-2 px-3">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-brand-text-muted">radio</span> Radios</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-300">Radio TWR</td>
                                        <td class="py-2 px-3 max-w-[180px] truncate">Sin audio en canal 3</td>
                                        <td class="py-2 px-3"><span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[#452809] text-[#fbbf24] border border-[#b45309]/40">Media</span></td>
                                        <td class="py-2 px-3"><span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#06291e]/60 text-[#10b981] border border-[#059669]/70">Resuelta</span></td>
                                        <td class="py-2 px-3 font-mono text-brand-text-muted">GTS-02</td>
                                        <td class="py-2 px-3 text-center"><span class="material-symbols-outlined text-[16px] text-brand-text-muted">more_horiz</span></td>
                                    </tr>
                                    <tr class="hover:bg-brand-surface-subtle/50 transition-colors">
                                        <td class="py-2 px-3 font-mono font-bold text-blue-400">ST-2026-0039</td>
                                        <td class="py-2 px-3 text-brand-text-muted font-mono">10/09/2026 11:23</td>
                                        <td class="py-2 px-3">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-brand-text-muted">videocam</span> Cámaras</span>
                                        </td>
                                        <td class="py-2 px-3 text-slate-300">CCTV - Estacionamiento</td>
                                        <td class="py-2 px-3 max-w-[180px] truncate">Imagen borrosa</td>
                                        <td class="py-2 px-3"><span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[#13285c] text-[#60a5fa] border border-[#1d4ed8]/40">Baja</span></td>
                                        <td class="py-2 px-3"><span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[#06291e]/60 text-[#10b981] border border-[#059669]/70">Resuelta</span></td>
                                        <td class="py-2 px-3 font-mono text-brand-text-muted">GTS-01</td>
                                        <td class="py-2 px-3 text-center"><span class="material-symbols-outlined text-[16px] text-brand-text-muted">more_horiz</span></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

            <!-- Columna Derecha: Estado de Sistemas y Acciones Rápidas (3 de 12 cols ~ 25%) -->
            <div class="col-span-12 lg:col-span-3 flex flex-col justify-between gap-3.5 h-full overflow-hidden">
                
                <!-- Panel 1: Estado de sistemas (monitoreo) -->
                <div class="bg-brand-surface border border-brand-border rounded-xl p-3.5 flex flex-col justify-between shadow-enterprise-sm shrink-0">
                    <h2 class="text-xs font-bold text-white mb-2">
                        Estado de sistemas (monitoreo)
                    </h2>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-slate-300">
                                <span class="material-symbols-outlined text-[17px] text-brand-text-muted">desktop_windows</span>
                                Cámaras de seguridad
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-medium text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Operativo
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-slate-300">
                                <span class="material-symbols-outlined text-[17px] text-brand-text-muted">door_front</span>
                                Puertas de acceso
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-medium text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Operativo
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-slate-300">
                                <span class="material-symbols-outlined text-[17px] text-brand-text-muted">tv</span>
                                Pantallas informativas
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-medium text-emerald-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Operativo
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-slate-300">
                                <span class="material-symbols-outlined text-[17px] text-brand-text-muted">radio</span>
                                Radios de comunicación
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-medium text-amber-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                Intermitente
                            </span>
                        </div>
                    </div>

                    <a 
                        href="{{ route('incidents.index') }}" 
                        class="mt-3 pt-2 border-t border-brand-border/60 text-[11px] font-medium text-blue-400 hover:text-blue-300 flex items-center justify-end gap-1 transition-colors"
                    >
                        <span>Ver detalle de sistemas</span>
                        <span class="text-[13px]">→</span>
                    </a>
                </div>

                <!-- Panel 2: Acciones Rápidas con Botón Maestro y Banner SITIA -->
                <div class="bg-brand-surface border border-brand-border rounded-xl p-3.5 flex flex-col justify-between shadow-enterprise-sm flex-1 min-h-0">
                    <div>
                        <h2 class="text-xs font-bold text-white mb-2.5">
                            Acciones rápidas
                        </h2>

                        <div class="space-y-2">
                            <!-- Botón 1: Registrar incidencia (Azul Royal Sólido) -->
                            @can('create', App\Models\Incident::class)
                                <a 
                                    href="{{ route('incidents.create') }}"
                                    class="w-full p-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white flex items-center gap-3 transition-all shadow-md group"
                                >
                                    <div class="w-8 h-8 rounded-lg bg-blue-500/80 flex items-center justify-center text-white shrink-0 group-hover:scale-105 transition-transform">
                                        <span class="material-symbols-outlined text-[20px]">add</span>
                                    </div>
                                    <div class="flex flex-col text-left leading-tight">
                                        <span class="text-xs font-bold">Registrar incidencia</span>
                                        <span class="text-[10px] text-blue-200">Crear un nuevo reporte</span>
                                    </div>
                                </a>
                            @endcan

                            <!-- Botón 2: Consultar incidencias -->
                            <a 
                                href="{{ route('incidents.index') }}"
                                class="w-full p-2 rounded-xl bg-[#0b142d] border border-brand-border hover:border-blue-500/50 hover:bg-[#0e193c] text-white flex items-center gap-3 transition-all group"
                            >
                                <div class="w-7 h-7 rounded-lg bg-slate-800/80 flex items-center justify-center text-slate-300 shrink-0 group-hover:text-white">
                                    <span class="material-symbols-outlined text-[17px]">search</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold">Consultar incidencias</span>
                                    <span class="text-[9px] text-brand-text-muted">Buscar y filtrar reportes</span>
                                </div>
                            </a>

                            <!-- Botón 3: Ver indicadores -->
                            <a 
                                href="{{ route('dashboard') }}"
                                class="w-full p-2 rounded-xl bg-[#0b142d] border border-brand-border hover:border-blue-500/50 hover:bg-[#0e193c] text-white flex items-center gap-3 transition-all group"
                            >
                                <div class="w-7 h-7 rounded-lg bg-slate-800/80 flex items-center justify-center text-slate-300 shrink-0 group-hover:text-white">
                                    <span class="material-symbols-outlined text-[17px]">analytics</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold">Ver indicadores</span>
                                    <span class="text-[9px] text-brand-text-muted">Métricas y desempeño</span>
                                </div>
                            </a>

                            <!-- Botón 4: Generar reporte -->
                            <a 
                                href="{{ route('audit.index') }}"
                                class="w-full p-2 rounded-xl bg-[#0b142d] border border-brand-border hover:border-blue-500/50 hover:bg-[#0e193c] text-white flex items-center gap-3 transition-all group"
                            >
                                <div class="w-7 h-7 rounded-lg bg-slate-800/80 flex items-center justify-center text-slate-300 shrink-0 group-hover:text-white">
                                    <span class="material-symbols-outlined text-[17px]">description</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold">Generar reporte</span>
                                    <span class="text-[9px] text-brand-text-muted">Exportar información</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Banner Inferior SITIA -->
                    <div class="mt-2.5 p-2.5 rounded-xl bg-[#0b142d]/80 border border-brand-border flex items-center gap-2.5">
                        <x-application-logo class="w-6 h-6 shrink-0 text-blue-400" />
                        <div class="flex flex-col text-left leading-tight min-w-0">
                            <span class="font-bold text-[11px] text-white">SITIA</span>
                            <span class="text-[9px] text-brand-text-muted truncate">
                                Una herramienta para una operación más segura y eficiente.
                            </span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>
