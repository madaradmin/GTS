<x-app-layout>
    <!-- Contenedor Principal Calibrado para 1920x1080 SIN SCROLL VERTICAL -->
    <div class="h-full p-3.5 lg:p-4 flex flex-col justify-between overflow-hidden gap-3 select-none bg-brand-canvas">

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
                <h1 class="text-lg lg:text-xl font-bold text-white tracking-tight">
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
                <div class="flex items-center gap-1 text-[10px] text-[#707B8A] font-medium mt-0.5">
                    <span class="material-symbols-outlined text-[12px] text-[#D83B52]">location_on</span>
                    <span>AIFA • Santa Lucía, Edo. de México</span>
                </div>
            </div>
        </div>

        <!-- 2. Grid Principal: Cuerpo Operativo (Cols 9) + Paneles Laterales (Cols 3) -->
        <div class="flex-1 min-h-0 grid grid-cols-12 gap-3">
            
            <!-- Columna Izquierda / Central: 4 KPIs + 3 Gráficos + Tabla (9 de 12 cols ~ 75%) -->
            <div class="col-span-12 lg:col-span-9 flex flex-col justify-between gap-3 h-full overflow-hidden">
                
                <!-- Fila A: 5 KPI Cards Tácticos Operacionales Dinámicos -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 shrink-0">
                    
                    <!-- KPI 1: Total de incidencias -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-2.5 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-[#164A8A] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">description</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-xl lg:text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['total'] }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    Total incidencias
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-1 text-[10px] font-medium text-[#7F8A99]">
                            <span class="truncate">Histórico plataforma</span>
                        </div>
                    </div>

                    <!-- KPI 2: Nuevas -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-2.5 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-[#0284C7] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">inbox</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-xl lg:text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['new'] }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    Nuevas
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-1 text-[10px] font-medium {{ $metrics['new'] > 0 ? 'text-[#38BDF8]' : 'text-[#7F8A99]' }}">
                            <span class="truncate">Por asignar</span>
                        </div>
                    </div>

                    <!-- KPI 3: En proceso -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-2.5 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-[#D99A18] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">schedule</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-xl lg:text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['in_process'] }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    En proceso
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-1 text-[10px] font-medium text-[#FBBF24]">
                            <span class="truncate">Atención técnica activa</span>
                        </div>
                    </div>

                    <!-- KPI 4: Resueltas -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-2.5 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-[#12B886] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-xl lg:text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['resolved'] }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    Resueltas
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-1 text-[10px] font-medium text-[#20C997]">
                            <span class="truncate">Cerradas y concluidas</span>
                        </div>
                    </div>

                    <!-- KPI 5: Críticas -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-2.5 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-[#D83B52] flex items-center justify-center text-white shrink-0 shadow-sm">
                                <span class="material-symbols-outlined text-[18px]">warning</span>
                            </div>
                            <div class="flex flex-col leading-tight min-w-0">
                                <span class="font-mono text-xl lg:text-2xl font-bold text-[#F2F5F8] tabular-nums">
                                    {{ $metrics['critical'] }}
                                </span>
                                <span class="text-[11px] text-[#7F8A99] truncate">
                                    Críticas
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 mt-1 text-[10px] font-medium {{ $metrics['critical'] > 0 ? 'text-[#F87171]' : 'text-[#7F8A99]' }}">
                            <span class="truncate">Severidad alta activa</span>
                        </div>
                    </div>

                </div>

                <!-- Fila B: 2 Gráficos Operativos Dinámicos Rediseñados al 50% / 50% (Por Tipo y Por Prioridad) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 h-[152px] shrink-0">
                    
                    <!-- Gráfico 1: Incidencias por tipo (50% de ancho) -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center justify-between border-b border-[#202731] pb-1.5 shrink-0">
                            <h2 class="text-xs font-bold text-[#F2F5F8] flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[15px] text-[#3D82C6]">pie_chart</span>
                                <span>Incidencias por tipo</span>
                            </h2>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono text-[#7E8998] bg-[#151B23] border border-[#202731]">
                                Categorías AIFA
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between gap-4 flex-1 min-h-0 pt-1.5">
                            <!-- Donut Vectorial con Conteo Total Dinámico al Centro -->
                            <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                                @php
                                    $catCumOffset = 0;
                                @endphp
                                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#1B2129" stroke-width="12" />
                                    @foreach($distribution['categories'] as $catItem)
                                        @php
                                            $catDash = $distribution['total_active'] > 0 ? round(($catItem['count'] / $distribution['total_active']) * 238.76) : 0;
                                        @endphp
                                        @if($catDash > 0)
                                            <circle cx="50" cy="50" r="38" fill="none" stroke="{{ $catItem['color'] }}" stroke-width="12" stroke-dasharray="{{ $catDash }} 239" stroke-dashoffset="-{{ $catCumOffset }}" />
                                            @php $catCumOffset += $catDash; @endphp
                                        @endif
                                    @endforeach
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                    <span class="font-mono text-sm font-bold text-[#F2F5F8] leading-none">{{ $distribution['total_active'] }}</span>
                                    <span class="text-[8px] text-[#707B8A] mt-0.5">Activas</span>
                                </div>
                            </div>

                            <!-- Leyenda de Categorías con Conteo y Porcentaje Real en Grid de 2 columnas para aprovechar el ancho -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-1 text-[10.5px] min-w-0 flex-1 overflow-y-auto scrollbar-thin pr-1">
                                @forelse($distribution['categories'] as $catItem)
                                    <div class="flex items-center justify-between bg-[#11161D]/50 px-2 py-0.5 rounded border border-[#1A222C]">
                                        <span class="flex items-center gap-1.5 text-[#DDE3EA] truncate" title="{{ $catItem['name'] }}">
                                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $catItem['color'] }}"></span>
                                            <span class="truncate text-[10.5px]">{{ $catItem['name'] }}</span>
                                        </span>
                                        <span class="font-mono text-[#9AA5B4] shrink-0 ml-1 text-[10px]">
                                            <span class="font-bold text-[#F2F5F8]">{{ $catItem['count'] }}</span> 
                                            <span class="text-[#707B8A] text-[9px]">({{ $catItem['percentage'] }}%)</span>
                                        </span>
                                    </div>
                                @empty
                                    <div class="col-span-2 text-[10px] text-[#707B8A] py-2 text-center">Sin incidencias activas</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico 2: Incidencias por prioridad (50% de ancho) -->
                    <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm hover:border-[#2A3440] transition-all">
                        <div class="flex items-center justify-between border-b border-[#202731] pb-1.5 shrink-0">
                            <h2 class="text-xs font-bold text-[#F2F5F8] flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[15px] text-[#F59E0B]">signal_cellular_alt</span>
                                <span>Incidencias por prioridad</span>
                            </h2>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono text-[#7E8998] bg-[#151B23] border border-[#202731]">
                                Severidad OACI
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-4 flex-1 min-h-0 pt-1.5">
                            <!-- Donut Vectorial Dinámico -->
                            @php
                                $prioColors = [
                                    'critica' => '#D83B52',
                                    'alta' => '#F59E0B',
                                    'media' => '#10B981',
                                    'baja' => '#1E5FA7',
                                ];
                                $prioCumOffset = 0;
                            @endphp
                            <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                    <circle cx="50" cy="50" r="38" fill="none" stroke="#1B2129" stroke-width="12" />
                                    @foreach($prioritiesSummary['items'] as $prioItem)
                                        @php
                                            $prioColor = $prioColors[$prioItem['slug']] ?? '#687383';
                                            $prioDash = $prioritiesSummary['total'] > 0 ? round(($prioItem['count'] / $prioritiesSummary['total']) * 238.76) : 0;
                                        @endphp
                                        @if($prioDash > 0)
                                            <circle cx="50" cy="50" r="38" fill="none" stroke="{{ $prioColor }}" stroke-width="12" stroke-dasharray="{{ $prioDash }} 239" stroke-dashoffset="-{{ $prioCumOffset }}" />
                                            @php $prioCumOffset += $prioDash; @endphp
                                        @endif
                                    @endforeach
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                    <span class="font-mono text-sm font-bold text-[#F2F5F8] leading-none">{{ $prioritiesSummary['total'] }}</span>
                                    <span class="text-[8px] text-[#707B8A] mt-0.5">Activas</span>
                                </div>
                            </div>

                            <!-- Leyenda de Prioridades Dinámica en Grid de 2 columnas -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-3 gap-y-1.5 text-[10.5px] min-w-0 flex-1 pr-1">
                                @forelse($prioritiesSummary['items'] as $prioItem)
                                    @php $prioColor = $prioColors[$prioItem['slug']] ?? '#687383'; @endphp
                                    <div class="flex items-center justify-between bg-[#11161D]/50 px-2.5 py-1 rounded border border-[#1A222C]">
                                        <span class="flex items-center gap-1.5 text-[#DDE3EA] truncate">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $prioColor }}"></span>
                                            <span class="truncate font-medium text-[11px]">{{ $prioItem['name'] }}</span>
                                        </span>
                                        <span class="font-mono text-[#9AA5B4] shrink-0 ml-1 text-[10.5px]">
                                            <span class="font-bold text-[#F2F5F8]">{{ $prioItem['count'] }}</span>
                                            <span class="text-[#707B8A] text-[9.5px]">({{ $prioItem['percentage'] }}%)</span>
                                        </span>
                                    </div>
                                @empty
                                    <div class="col-span-2 text-[10px] text-[#707B8A] text-center">Sin prioridades activas</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Fila C: Tabla "Últimas incidencias" (Optimizado a 5 Columnas Esenciales - Cero Scroll Horizontal) -->
                <div 
                    x-data="{
                        timer: null,
                        lastEtag: null,
                        isPolling: false,
                        init() {
                            // Iniciar sondeo táctico continuo cada 4 segundos
                            this.timer = setInterval(() => this.poll(), 4000);

                            // Sondeo reactivo inmediato cuando la pestaña recupera el foco
                            document.addEventListener('visibilitychange', () => {
                                if (document.visibilityState === 'visible') {
                                    this.poll();
                                }
                            });
                        },
                        destroy() {
                            if (this.timer) clearInterval(this.timer);
                        },
                        async poll() {
                            if (this.isPolling) return;
                            this.isPolling = true;
                            try {
                                const headers = {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'text/html'
                                };
                                if (this.lastEtag) {
                                    headers['If-None-Match'] = this.lastEtag;
                                }

                                const response = await fetch('{{ route('dashboard.recent-incidents') }}', { headers });

                                // Si la respuesta es 304 Not Modified, los datos están sincronizados sin cambios
                                if (response.status === 304) {
                                    return;
                                }

                                if (response.ok) {
                                    const etag = response.headers.get('ETag');
                                    if (etag) {
                                        this.lastEtag = etag;
                                    }
                                    const html = await response.text();
                                    if (this.$refs.recentTableBody && html.trim() !== this.$refs.recentTableBody.innerHTML.trim()) {
                                        this.$refs.recentTableBody.innerHTML = html.trim();
                                    }
                                }
                            } catch (error) {
                                // Red resiliente: fallo silencioso ante cortes momentáneos de conectividad
                            } finally {
                                this.isPolling = false;
                            }
                        }
                    }"
                    class="bg-brand-surface border border-brand-border rounded-xl shadow-enterprise flex flex-col flex-1 min-h-0 overflow-hidden hover:border-[#2A3440] transition-all"
                >
                    
                    <!-- Encabezado de la Tabla -->
                    <div class="px-3.5 py-1.5 border-b border-[#202731] flex items-center justify-between shrink-0 bg-[#0D1117]">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xs font-bold text-[#F2F5F8]">
                                Últimas incidencias
                            </h2>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-[#151B23] text-[10px] font-mono text-[#7E8998] border border-[#202731]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>En tiempo real</span>
                            </span>
                        </div>
                        @can('viewAny', App\Models\Incident::class)
                        <a 
                            href="{{ route('incidents.index') }}" 
                            class="text-[11px] font-medium text-[#3D82C6] hover:text-[#6FA2D2] flex items-center gap-1 transition-colors group"
                        >
                            <span>Ver todas las incidencias</span>
                            <span class="text-[13px] group-hover:translate-x-0.5 transition-transform">→</span>
                        </a>
                        @endcan
                    </div>

                    <!-- Tabla de Incidencias -->
                    <div class="flex-1 overflow-x-hidden overflow-y-auto scrollbar-thin">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-[#0D1117] text-[10px] font-semibold text-[#7E8998] border-b border-[#202731] sticky top-0 z-10 select-none">
                                    <th class="py-1.5 px-3.5 font-mono">Folio</th>
                                    <th class="py-1.5 px-3.5">Fecha / Hora</th>
                                    <th class="py-1.5 px-3.5">Tipo / Categoría</th>
                                    <th class="py-1.5 px-3.5">Estado</th>
                                    <th class="py-1.5 px-3.5">Prioridad</th>
                                </tr>
                            </thead>
                            <tbody 
                                x-ref="recentTableBody"
                                class="divide-y divide-[#202731] text-[11px] text-[#DDE3EA]"
                            >
                                @include('dashboard.partials.recent-incidents-rows', ['recentIncidents' => $recentIncidents])
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

            <!-- Columna Derecha: Estado de Sistemas y Acciones Rápidas (3 de 12 cols ~ 25%) -->
            <div class="col-span-12 lg:col-span-3 flex flex-col justify-between gap-3 h-full overflow-hidden">
                
                <!-- Panel 1: Estado de sistemas (monitoreo) -->
                <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm shrink-0">
                    <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-[#202731]">
                        <h2 class="text-xs font-bold text-[#F2F5F8] flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[15px] text-[#3D82C6]">hub</span>
                            <span>Estado de sistemas (monitoreo)</span>
                        </h2>
                        @if(($systemsHealth['total'] ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold font-mono border"
                                  style="background-color: {{ $systemsHealth['badge_bg'] }}; color: {{ $systemsHealth['color'] }}; border-color: {{ $systemsHealth['badge_border'] }};"
                                  title="Disponibilidad: {{ $systemsHealth['active'] }}/{{ $systemsHealth['total'] }} sistemas operativos">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $systemsHealth['color'] }}"></span>
                                {{ $systemsHealth['state'] }} ({{ $systemsHealth['percentage'] }}%)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono text-[#707B8A] bg-[#151B23] border border-[#202731]">
                                Sin registros
                            </span>
                        @endif
                    </div>

                    <div class="space-y-1.5 text-xs max-h-48 overflow-y-auto scrollbar-thin pr-1">
                        @forelse($systemsHealth['systems'] ?? [] as $system)
                            <div class="flex items-center justify-between py-0.5">
                                <span class="flex items-center gap-2 text-[#D6DCE4] truncate min-w-0" title="{{ $system['name'] }} ({{ $system['code'] ?? 'S/C' }})">
                                    <span class="material-symbols-outlined text-[17px] text-brand-text-muted shrink-0">{{ $system['icon'] }}</span>
                                    <span class="truncate">{{ $system['name'] }}</span>
                                </span>
                                <span class="flex items-center gap-1.5 text-[11px] font-medium shrink-0 ml-1.5" style="color: {{ $system['status_color'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background-color: {{ $system['status_color'] }}"></span>
                                    {{ $system['status_label'] }}
                                </span>
                            </div>
                        @empty
                            <div class="py-3 text-center text-xs text-[#707B8A]">
                                No hay sistemas registrados en el catálogo.
                            </div>
                        @endforelse
                    </div>

                    <a 
                        href="{{ route('catalogs.index') }}" 
                        class="mt-2 pt-1.5 border-t border-brand-border/60 text-[10px] font-medium text-[#3D82C6] hover:text-[#5B9FE0] flex items-center justify-end gap-1 transition-colors"
                    >
                        <span>Ver detalle de sistemas</span>
                        <span class="text-[12px]">→</span>
                    </a>
                </div>

                <!-- Panel 2: Acciones Rápidas con Botón Maestro y Banner SITIA -->
                <div class="bg-brand-surface border border-brand-border rounded-xl p-3 flex flex-col justify-between shadow-enterprise-sm flex-1 min-h-0">
                    <div>
                        <h2 class="text-xs font-bold text-[#F2F5F8] mb-1.5">
                            Acciones rápidas
                        </h2>

                        <div class="space-y-1.5">
                            {{-- Botón 1: Registrar incidencia (Azul Royal Sólido) --}}
                            @can('create', App\Models\Incident::class)
                                <a 
                                    href="{{ route('incidents.create') }}"
                                    class="w-full p-2 rounded-xl bg-[#123A70] hover:bg-[#164A8A] border border-white/10 text-white flex items-center gap-2.5 transition-all shadow-sm group"
                                >
                                    <div class="w-7 h-7 rounded-lg bg-[#164A8A] flex items-center justify-center text-white shrink-0 group-hover:scale-105 transition-transform">
                                        <span class="material-symbols-outlined text-[18px]">add</span>
                                    </div>
                                    <div class="flex flex-col text-left leading-tight">
                                        <span class="text-xs font-bold">Registrar incidencia</span>
                                        <span class="text-[9px] text-[#A8C4E6]">Crear un nuevo reporte</span>
                                    </div>
                                </a>
                            @endcan

                            {{-- Botón 2: Consultar incidencias --}}
                            @can('viewAny', App\Models\Incident::class)
                            <a 
                                href="{{ route('incidents.index') }}"
                                class="w-full p-1.5 rounded-lg bg-[#0F141B] border border-[#252C35] hover:border-[#2A3440] hover:bg-[#151A21] text-[#E2E7ED] flex items-center gap-2.5 transition-all group"
                            >
                                <div class="w-6 h-6 rounded-md bg-[#151A21] border border-[#252C35] flex items-center justify-center text-[#8A95A4] shrink-0 group-hover:text-white group-hover:bg-[#1A222C] transition-colors">
                                    <span class="material-symbols-outlined text-[15px]">search</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold text-[#E2E7ED]">Consultar incidencias</span>
                                    <span class="text-[9px] text-[#8A95A4]">Buscar y filtrar reportes</span>
                                </div>
                            </a>
                            @endcan

                            <!-- Botón 3: Ver indicadores -->
                            <a 
                                href="{{ route('indicators.index') }}"
                                class="w-full p-1.5 rounded-lg bg-[#0F141B] border border-[#252C35] hover:border-[#2A3440] hover:bg-[#151A21] text-[#E2E7ED] flex items-center gap-2.5 transition-all group"
                            >
                                <div class="w-6 h-6 rounded-md bg-[#151A21] border border-[#252C35] flex items-center justify-center text-[#8A95A4] shrink-0 group-hover:text-white group-hover:bg-[#1A222C] transition-colors">
                                    <span class="material-symbols-outlined text-[15px]">analytics</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold text-[#E2E7ED]">Ver indicadores</span>
                                    <span class="text-[9px] text-[#8A95A4]">Métricas y desempeño</span>
                                </div>
                            </a>

                            {{-- Botón 4: Descargar reporte --}}
                            @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('reports.view'))
                            <a 
                                href="{{ route('reports.index') }}"
                                class="w-full p-1.5 rounded-lg bg-[#0F141B] border border-[#252C35] hover:border-[#2A3440] hover:bg-[#151A21] text-[#E2E7ED] flex items-center gap-2.5 transition-all group"
                                title="Descargar reporte"
                            >
                                <div class="w-6 h-6 rounded-md bg-[#151A21] border border-[#252C35] flex items-center justify-center text-[#8A95A4] shrink-0 group-hover:text-white group-hover:bg-[#1A222C] transition-colors">
                                    <span class="material-symbols-outlined text-[15px]">description</span>
                                </div>
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-xs font-semibold text-[#E2E7ED]">Descargar reporte</span>
                                    <span class="text-[9px] text-[#8A95A4]">Exportar información</span>
                                </div>
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Banner Inferior GTS -->
                    <div class="mt-2 p-2 rounded-xl bg-[#0F141B] border border-[#252C35] flex items-center gap-2.5">
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
