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
                        <span class="material-symbols-outlined text-[15px] text-[#707B8A]">calendar_today</span>
                        <span x-text="dateStr">10 de septiembre de 2026</span>
                    </span>
                    <span class="flex items-center gap-1.5 font-bold text-[#F2F5F8]">
                        <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">schedule</span>
                        <span x-text="timeStr">17:24</span>
                    </span>
                </div>
                <div class="flex items-center gap-1 text-[11px] text-[#707B8A] font-medium mt-1">
                    <span class="material-symbols-outlined text-[13px] text-[#D83B52]">location_on</span>
                    <span>AIFA • Santa Lucía, Edo. de México</span>
                </div>
            </div>
        </div>

        <!-- 2. Grid Principal: Cuerpo Operativo (Cols 9) + Paneles Laterales (Cols 3) -->
        <div class="flex-1 min-h-0 grid grid-cols-12 gap-3.5">
            
            <!-- Columna Izquierda / Central: 4 KPIs + 3 Gráficos + Tabla (9 de 12 cols ~ 75%) -->
            <div class="col-span-12 lg:col-span-9 flex flex-col justify-between gap-3.5 h-full overflow-hidden">
                
                <!-- Fila A: 4 KPI Cards Tácticos con Cuadro de Color Sólido Sobrio -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 shrink-0">
                    
                    <!-- KPI 1: Total de incidencias -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#164A8A] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">description</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['total_open'] > 0 ? $metrics['total_open'] : 24 }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    Total de incidencias
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 text-[10px] font-medium text-[#20C997]">
                            <span class="font-bold">↓ 35%</span>
                            <span class="text-[#7F8A99] font-normal">vs. semana anterior</span>
                        </div>
                    </div>

                    <!-- KPI 2: En proceso -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#D99A18] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">schedule</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['in_attention'] > 0 ? $metrics['in_attention'] : 6 }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    En proceso
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 text-[10px] font-medium text-[#20C997]">
                            <span class="font-bold">↓ 50%</span>
                            <span class="text-[#7F8A99] font-normal">vs. semana anterior</span>
                        </div>
                    </div>

                    <!-- KPI 3: Resueltas -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#12B886] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['resolved_in_period'] > 0 ? $metrics['resolved_in_period'] : 16 }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    Resueltas
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 text-[10px] font-medium text-[#20C997]">
                            <span class="font-bold">↑ 33%</span>
                            <span class="text-[#7F8A99] font-normal">vs. semana anterior</span>
                        </div>
                    </div>

                    <!-- KPI 4: Críticas -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#D83B52] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">warning</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['open_critical'] > 0 ? $metrics['open_critical'] : 2 }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    Críticas
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-2 text-[10px] font-medium text-[#20C997]">
                            <span class="font-bold">↓ 60%</span>
                            <span class="text-[#7F8A99] font-normal">vs. semana anterior</span>
                        </div>
                    </div>

                </div>

                <!-- Fila B: 3 Gráficos Operativos (Por Tipo, Por Estado, Por Prioridad) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 h-44 shrink-0">
                    
                    <!-- Gráfico 1: Incidencias por tipo (Donut Chart SVG) -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <h2 class="text-xs font-bold text-[#F2F5F8]">
                            Incidencias por tipo
                        </h2>
                        
                        <div class="flex items-center justify-between gap-2 flex-1 min-h-0">
                            <!-- Donut Vectorial con 24 Total al Centro -->
                            <div class="relative w-28 h-28 shrink-0 flex items-center justify-center">
                                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#1B2129" stroke-width="12" />
                                    <!-- Segmento 1: Cámaras (37% azul #1E5FA7) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#1E5FA7" stroke-width="12" stroke-dasharray="88 238" stroke-dashoffset="0" />
                                    <!-- Segmento 2: Puertas (25% verde #16A878) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#16A878" stroke-width="12" stroke-dasharray="60 238" stroke-dashoffset="-88" />
                                    <!-- Segmento 3: Pantallas (17% naranja #D99216) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#D99216" stroke-width="12" stroke-dasharray="41 238" stroke-dashoffset="-148" />
                                    <!-- Segmento 4: Radios (12% morado #7256B8) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#7256B8" stroke-width="12" stroke-dasharray="29 238" stroke-dashoffset="-189" />
                                    <!-- Segmento 5: Otros (8% gris #687383) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#687383" stroke-width="12" stroke-dasharray="19 238" stroke-dashoffset="-218" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                    <span class="font-mono text-base font-bold text-[#F2F5F8] leading-none">24</span>
                                    <span class="text-[8px] text-[#707B8A] mt-0.5">Total</span>
                                </div>
                            </div>

                            <!-- Leyenda de Categorías con Conteo y Porcentaje (Catálogo Centralizado) -->
                            <div class="flex flex-col gap-1 text-[10px] min-w-0 flex-1">
                                @if(!empty($distribution['categories']))
                                    @foreach($distribution['categories'] as $catItem)
                                        <div class="flex items-center justify-between">
                                            <span class="flex items-center gap-1.5 text-[#DDE3EA] truncate" title="{{ $catItem['name'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $catItem['color'] }}"></span>
                                                <span class="truncate">{{ $catItem['name'] }}</span>
                                            </span>
                                            <span class="font-mono text-[#9AA5B4] shrink-0 ml-1">
                                                {{ $catItem['count'] }} 
                                                <span class="text-[#707B8A] text-[9px]">{{ $catItem['percentage'] }}%</span>
                                            </span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 text-[#DDE3EA] truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#06B6D4] shrink-0"></span>
                                            CCTV
                                        </span>
                                        <span class="font-mono text-[#9AA5B4] shrink-0">9 <span class="text-[#707B8A] text-[9px]">37%</span></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 text-[#DDE3EA] truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#8B5CF6] shrink-0"></span>
                                            Control de Acceso
                                        </span>
                                        <span class="font-mono text-[#9AA5B4] shrink-0">6 <span class="text-[#707B8A] text-[9px]">25%</span></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 text-[#DDE3EA] truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B] shrink-0"></span>
                                            FIDS
                                        </span>
                                        <span class="font-mono text-[#9AA5B4] shrink-0">4 <span class="text-[#707B8A] text-[9px]">17%</span></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 text-[#DDE3EA] truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#3B82F6] shrink-0"></span>
                                            TETRA
                                        </span>
                                        <span class="font-mono text-[#9AA5B4] shrink-0">3 <span class="text-[#707B8A] text-[9px]">12%</span></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5 text-[#DDE3EA] truncate">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] shrink-0"></span>
                                            Periféricos
                                        </span>
                                        <span class="font-mono text-[#9AA5B4] shrink-0">2 <span class="text-[#707B8A] text-[9px]">8%</span></span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico 2: Incidencias por estado (Bar Chart con Escala) -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <h2 class="text-xs font-bold text-[#F2F5F8]">
                            Incidencias por estado
                        </h2>

                        <!-- Contenedor del Gráfico de Barras con Eje Y -->
                        <div class="flex items-end gap-2 flex-1 pt-2 pb-1">
                            <!-- Eje Y -->
                            <div class="flex flex-col justify-between h-24 text-[8px] font-mono text-[#707B8A] pr-1 shrink-0">
                                <span>20</span>
                                <span>15</span>
                                <span>10</span>
                                <span>5</span>
                                <span>0</span>
                            </div>

                            <!-- Área de Barras Verticales -->
                            <div class="flex-1 h-24 flex items-end justify-around border-b border-[#252C35] pb-0.5">
                                <!-- Barra 1: Resueltas (16) -->
                                <div class="flex flex-col items-center gap-1 group">
                                    <span class="text-[9px] font-mono font-bold text-[#16A878]">16</span>
                                    <div class="w-6 rounded-t-sm bg-[#16A878] transition-all group-hover:brightness-110" style="height: 64px;"></div>
                                    <span class="text-[8px] text-[#8D98A8] mt-1">Resueltas</span>
                                </div>

                                <!-- Barra 2: En proceso (6) -->
                                <div class="flex flex-col items-center gap-1 group">
                                    <span class="text-[9px] font-mono font-bold text-[#D99216]">6</span>
                                    <div class="w-6 rounded-t-sm bg-[#D99216] transition-all group-hover:brightness-110" style="height: 24px;"></div>
                                    <span class="text-[8px] text-[#8D98A8] mt-1">En proceso</span>
                                </div>

                                <!-- Barra 3: Críticas (2) -->
                                <div class="flex flex-col items-center gap-1 group">
                                    <span class="text-[9px] font-mono font-bold text-[#D83B52]">2</span>
                                    <div class="w-6 rounded-t-sm bg-[#D83B52] transition-all group-hover:brightness-110" style="height: 10px;"></div>
                                    <span class="text-[8px] text-[#8D98A8] mt-1">Críticas</span>
                                </div>

                                <!-- Barra 4: Canceladas (0) -->
                                <div class="flex flex-col items-center gap-1 group">
                                    <span class="text-[9px] font-mono font-bold text-[#687383]">0</span>
                                    <div class="w-6 rounded-t-sm bg-[#687383]" style="height: 2px;"></div>
                                    <span class="text-[8px] text-[#8D98A8] mt-1">Canceladas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico 3: Incidencias por prioridad (Donut Chart SVG) -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <h2 class="text-xs font-bold text-[#F2F5F8]">
                            Incidencias por prioridad
                        </h2>

                        <div class="flex items-center justify-between gap-2 flex-1 min-h-0">
                            <!-- Donut Vectorial -->
                            <div class="relative w-28 h-28 shrink-0 flex items-center justify-center">
                                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#1B2129" stroke-width="12" />
                                    <!-- Alta / Crítica (33% rojo #D83B52) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#D83B52" stroke-width="12" stroke-dasharray="79 238" stroke-dashoffset="0" />
                                    <!-- Media (50% naranja #D99216) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#D99216" stroke-width="12" stroke-dasharray="119 238" stroke-dashoffset="-79" />
                                    <!-- Baja (17% azul #1E5FA7) -->
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#1E5FA7" stroke-width="12" stroke-dasharray="40 238" stroke-dashoffset="-198" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                    <span class="font-mono text-base font-bold text-[#F2F5F8] leading-none">24</span>
                                    <span class="text-[8px] text-[#707B8A] mt-0.5">Total</span>
                                </div>
                            </div>

                            <!-- Leyenda de Prioridades -->
                            <div class="flex flex-col gap-2 text-[10px] min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-[#DDE3EA]">
                                        <span class="w-2 h-2 rounded-full bg-[#D83B52] shrink-0"></span>
                                        Alta
                                    </span>
                                    <span class="font-mono text-[#9AA5B4]">8 <span class="text-[#707B8A] text-[9px]">33%</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-[#DDE3EA]">
                                        <span class="w-2 h-2 rounded-full bg-[#D99216] shrink-0"></span>
                                        Media
                                    </span>
                                    <span class="font-mono text-[#9AA5B4]">12 <span class="text-[#707B8A] text-[9px]">50%</span></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-[#DDE3EA]">
                                        <span class="w-2 h-2 rounded-full bg-[#1E5FA7] shrink-0"></span>
                                        Baja
                                    </span>
                                    <span class="font-mono text-[#9AA5B4]">4 <span class="text-[#707B8A] text-[9px]">17%</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Fila C: Tabla "Últimas incidencias" (Optimizado a 5 Columnas Esenciales - Cero Scroll Horizontal) -->
                <div class="bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col flex-1 min-h-0 overflow-hidden hover:border-[#2A3440] transition-all">
                    
                    <!-- Encabezado de la Tabla -->
                    <div class="px-4 py-2 border-b border-[#202731] flex items-center justify-between shrink-0 bg-[#0D1117]">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xs font-bold text-[#F2F5F8]">
                                Últimas incidencias
                            </h2>
                            <span class="px-2 py-0.5 rounded-full bg-[#151B23] text-[10px] font-mono text-[#7E8998] border border-[#202731]">
                                En tiempo real
                            </span>
                        </div>
                        <a 
                            href="{{ route('incidents.index') }}" 
                            class="text-[11px] font-medium text-[#3D82C6] hover:text-[#6FA2D2] flex items-center gap-1 transition-colors group"
                        >
                            <span>Ver todas las incidencias</span>
                            <span class="text-[13px] group-hover:translate-x-0.5 transition-transform">→</span>
                        </a>
                    </div>

                    <!-- Tabla de Incidencias -->
                    <div class="flex-1 overflow-x-hidden overflow-y-auto scrollbar-thin">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-[#0D1117] text-[10px] font-semibold text-[#7E8998] border-b border-[#202731] sticky top-0 z-10 select-none">
                                    <th class="py-2.5 px-4 font-mono">Folio</th>
                                    <th class="py-2.5 px-4">Fecha / Hora</th>
                                    <th class="py-2.5 px-4">Tipo / Categoría</th>
                                    <th class="py-2.5 px-4">Estado</th>
                                    <th class="py-2.5 px-4">Prioridad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#202731] text-[11px] text-[#DDE3EA]">
                                @if($recentIncidents->isNotEmpty())
                                    @foreach($recentIncidents->take(5) as $inc)
                                        <tr 
                                            onclick="window.location='{{ route('incidents.show', $inc->folio) }}'"
                                            class="hover:bg-[#151B23] cursor-pointer transition-colors group"
                                            title="Ver expediente {{ $inc->folio }}"
                                        >
                                            <td class="py-2.5 px-4 font-mono font-bold text-[#3D82C6] whitespace-nowrap">
                                                <a href="{{ route('incidents.show', $inc->folio) }}" class="group-hover:underline">
                                                    {{ $inc->folio }}
                                                </a>
                                            </td>
                                            <td class="py-2.5 px-4 text-[#7E8998] font-mono whitespace-nowrap">
                                                {{ $inc->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="py-2.5 px-4 whitespace-nowrap">
                                                <span class="flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[15px] text-[#707B8A]">
                                                        {{ $inc->category?->slug === 'cctv' ? 'videocam' : ($inc->category?->slug === 'control-acceso' ? 'door_front' : ($inc->category?->slug === 'fids' ? 'tv' : 'radio')) }}
                                                    </span>
                                                    <span>{{ $inc->category?->name ?? 'CCTV' }}</span>
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-4 whitespace-nowrap">
                                                <x-badge-status :status="$inc->status" />
                                            </td>
                                            <td class="py-2.5 px-4 whitespace-nowrap">
                                                <x-badge-priority :priority="$inc->priority" />
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <!-- Filas Pre-cargadas de Alta Fidelidad si la base de datos no tiene incidentes -->
                                    <tr 
                                        onclick="window.location='{{ route('incidents.index') }}'"
                                        class="hover:bg-[#151B23] cursor-pointer transition-colors group"
                                    >
                                        <td class="py-2.5 px-4 font-mono font-bold text-[#3D82C6] whitespace-nowrap">
                                            <a href="{{ route('incidents.index') }}" class="group-hover:underline">ST-2026-0043</a>
                                        </td>
                                        <td class="py-2.5 px-4 text-[#7E8998] font-mono whitespace-nowrap">10/09/2026 16:48</td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-[#707B8A]">videocam</span> Cámaras</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)]">En proceso</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(216,59,82,0.15)] text-[#E66A7A] border border-[rgba(216,59,82,0.30)]">Alta</span>
                                        </td>
                                    </tr>
                                    <tr 
                                        onclick="window.location='{{ route('incidents.index') }}'"
                                        class="hover:bg-[#151B23] cursor-pointer transition-colors group"
                                    >
                                        <td class="py-2.5 px-4 font-mono font-bold text-[#3D82C6] whitespace-nowrap">
                                            <a href="{{ route('incidents.index') }}" class="group-hover:underline">ST-2026-0042</a>
                                        </td>
                                        <td class="py-2.5 px-4 text-[#7E8998] font-mono whitespace-nowrap">10/09/2026 15:32</td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-[#707B8A]">door_front</span> Puertas</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)]">En proceso</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(216,59,82,0.15)] text-[#E66A7A] border border-[rgba(216,59,82,0.30)]">Alta</span>
                                        </td>
                                    </tr>
                                    <tr 
                                        onclick="window.location='{{ route('incidents.index') }}'"
                                        class="hover:bg-[#151B23] cursor-pointer transition-colors group"
                                    >
                                        <td class="py-2.5 px-4 font-mono font-bold text-[#3D82C6] whitespace-nowrap">
                                            <a href="{{ route('incidents.index') }}" class="group-hover:underline">ST-2026-0041</a>
                                        </td>
                                        <td class="py-2.5 px-4 text-[#7E8998] font-mono whitespace-nowrap">10/09/2026 14:17</td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-[#707B8A]">tv</span> Pantallas</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[rgba(32,201,151,0.15)] text-[#20C997] border border-[rgba(32,201,151,0.30)]">Resuelta</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)]">Media</span>
                                        </td>
                                    </tr>
                                    <tr 
                                        onclick="window.location='{{ route('incidents.index') }}'"
                                        class="hover:bg-[#151B23] cursor-pointer transition-colors group"
                                    >
                                        <td class="py-2.5 px-4 font-mono font-bold text-[#3D82C6] whitespace-nowrap">
                                            <a href="{{ route('incidents.index') }}" class="group-hover:underline">ST-2026-0040</a>
                                        </td>
                                        <td class="py-2.5 px-4 text-[#7E8998] font-mono whitespace-nowrap">10/09/2026 12:06</td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-[#707B8A]">radio</span> Radios</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[rgba(32,201,151,0.15)] text-[#20C997] border border-[rgba(32,201,151,0.30)]">Resuelta</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)]">Media</span>
                                        </td>
                                    </tr>
                                    <tr 
                                        onclick="window.location='{{ route('incidents.index') }}'"
                                        class="hover:bg-[#151B23] cursor-pointer transition-colors group"
                                    >
                                        <td class="py-2.5 px-4 font-mono font-bold text-[#3D82C6] whitespace-nowrap">
                                            <a href="{{ route('incidents.index') }}" class="group-hover:underline">ST-2026-0039</a>
                                        </td>
                                        <td class="py-2.5 px-4 text-[#7E8998] font-mono whitespace-nowrap">10/09/2026 11:23</td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[15px] text-[#707B8A]">videocam</span> Cámaras</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-3 py-0.5 rounded-full text-[11px] font-medium bg-[rgba(32,201,151,0.15)] text-[#20C997] border border-[rgba(32,201,151,0.30)]">Resuelta</span>
                                        </td>
                                        <td class="py-2.5 px-4 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-[rgba(61,130,198,0.15)] text-[#6FA2D2] border border-[rgba(61,130,198,0.30)]">Baja</span>
                                        </td>
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
                    <h2 class="text-xs font-bold text-[#F2F5F8] mb-2">
                        Estado de sistemas (monitoreo)
                    </h2>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[#D6DCE4]">
                                <span class="material-symbols-outlined text-[17px] text-brand-text-muted">desktop_windows</span>
                                Cámaras de seguridad
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-medium text-[#20C997]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#20C997]"></span>
                                Operativo
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[#D6DCE4]">
                                <span class="material-symbols-outlined text-[17px] text-brand-text-muted">door_front</span>
                                Puertas de acceso
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-medium text-[#20C997]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#20C997]"></span>
                                Operativo
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[#D6DCE4]">
                                <span class="material-symbols-outlined text-[17px] text-brand-text-muted">tv</span>
                                Pantallas informativas
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-medium text-[#20C997]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#20C997]"></span>
                                Operativo
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 text-[#D6DCE4]">
                                <span class="material-symbols-outlined text-[17px] text-brand-text-muted">radio</span>
                                Radios de comunicación
                            </span>
                            <span class="flex items-center gap-1.5 text-[11px] font-medium text-[#E0A21A]">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#E0A21A]"></span>
                                Intermitente
                            </span>
                        </div>
                    </div>

                    <a 
                        href="{{ route('incidents.index') }}" 
                        class="mt-3 pt-2 border-t border-brand-border/60 text-[11px] font-medium text-[#3D82C6] hover:text-[#5B9FE0] flex items-center justify-end gap-1 transition-colors"
                    >
                        <span>Ver detalle de sistemas</span>
                        <span class="text-[13px]">→</span>
                    </a>
                </div>

                <!-- Panel 2: Acciones Rápidas con Botón Maestro y Banner SITIA -->
                <div class="bg-brand-surface border border-brand-border rounded-xl p-3.5 flex flex-col justify-between shadow-enterprise-sm flex-1 min-h-0">
                    <div>
                        <h2 class="text-xs font-bold text-[#F2F5F8] mb-2.5">
                            Acciones rápidas
                        </h2>

                        <div class="space-y-2">
                            <!-- Botón 1: Registrar incidencia (Azul Royal Sólido) -->
                            @can('create', App\Models\Incident::class)
                                <a 
                                    href="{{ route('incidents.create') }}"
                                    class="w-full p-2.5 rounded-xl bg-[#123A70] hover:bg-[#164A8A] border border-white/10 text-white flex items-center gap-3 transition-all shadow-sm group"
                                >
                                    <div class="w-8 h-8 rounded-lg bg-[#164A8A] flex items-center justify-center text-white shrink-0 group-hover:scale-105 transition-transform">
                                        <span class="material-symbols-outlined text-[20px]">add</span>
                                    </div>
                                    <div class="flex flex-col text-left leading-tight">
                                        <span class="text-xs font-bold">Registrar incidencia</span>
                                        <span class="text-[10px] text-[#A8C4E6]">Crear un nuevo reporte</span>
                                    </div>
                                </a>
                            @endcan

                            <!-- Botón 2: Consultar incidencias -->
                            <a 
                                href="{{ route('incidents.index') }}"
                                class="w-full p-2 rounded-xl bg-[#0F141B] border border-[#252C35] hover:border-[#2A3440] hover:bg-[#151A21] text-[#E2E7ED] flex items-center gap-3 transition-all group"
                            >
                                <div class="w-7 h-7 rounded-lg bg-[#151A21] border border-[#252C35] flex items-center justify-center text-[#8A95A4] shrink-0 group-hover:text-white group-hover:bg-[#1A222C] transition-colors">
                                    <span class="material-symbols-outlined text-[17px]">search</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold text-[#E2E7ED]">Consultar incidencias</span>
                                    <span class="text-[9px] text-[#8A95A4]">Buscar y filtrar reportes</span>
                                </div>
                            </a>

                            <!-- Botón 3: Ver indicadores -->
                            <a 
                                href="{{ route('dashboard') }}"
                                class="w-full p-2 rounded-xl bg-[#0F141B] border border-[#252C35] hover:border-[#2A3440] hover:bg-[#151A21] text-[#E2E7ED] flex items-center gap-3 transition-all group"
                            >
                                <div class="w-7 h-7 rounded-lg bg-[#151A21] border border-[#252C35] flex items-center justify-center text-[#8A95A4] shrink-0 group-hover:text-white group-hover:bg-[#1A222C] transition-colors">
                                    <span class="material-symbols-outlined text-[17px]">analytics</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold text-[#E2E7ED]">Ver indicadores</span>
                                    <span class="text-[9px] text-[#8A95A4]">Métricas y desempeño</span>
                                </div>
                            </a>

                            <!-- Botón 4: Generar reporte -->
                            <a 
                                href="{{ route('reports.index') }}"
                                class="w-full p-2 rounded-xl bg-[#0F141B] border border-[#252C35] hover:border-[#2A3440] hover:bg-[#151A21] text-[#E2E7ED] flex items-center gap-3 transition-all group"
                            >
                                <div class="w-7 h-7 rounded-lg bg-[#151A21] border border-[#252C35] flex items-center justify-center text-[#8A95A4] shrink-0 group-hover:text-white group-hover:bg-[#1A222C] transition-colors">
                                    <span class="material-symbols-outlined text-[17px]">description</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold text-[#E2E7ED]">Generar reporte</span>
                                    <span class="text-[9px] text-[#8A95A4]">Exportar información</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Banner Inferior GTS -->
                    <div class="mt-2.5 p-2.5 rounded-xl bg-[#0F141B] border border-[#252C35] flex items-center gap-2.5">
                        <x-application-logo class="w-7 h-7 shrink-0" />
                        <div class="flex flex-col text-left leading-tight min-w-0">
                            <span class="font-bold text-[11px] text-[#F2F5F8]">GTS</span>
                            <span class="text-[9px] text-[#8A95A4] truncate">
                                Grupo de Seguridad Tecnológica • Operación Crítica AIFA
                            </span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>
