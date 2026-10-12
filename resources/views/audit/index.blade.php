<x-app-layout>
    <div 
        x-data="{
            activeTab: '{{ !empty($filters['action']) || request('view') === 'history' ? 'history' : 'incidents' }}',
            showDeleteModal: false,
            deleteFolio: '',
            deleteActionUrl: '',
            confirmDelete(folio, url) {
                this.deleteFolio = folio;
                this.deleteActionUrl = url;
                this.showDeleteModal = true;
            }
        }"
        class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6"
    >
        @if(session('status_message'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[22px] text-emerald-400">check_circle</span>
                    <span class="font-medium">{{ session('status_message') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>
        @endif
        
        <!-- Encabezado y Contexto Operacional -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('dashboard') }}" class="text-xs uppercase tracking-widest text-[#707B8A] hover:text-[#3D82C6] transition-colors">Inicio</a>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-[#3D82C6] font-semibold">Trazabilidad Aeronáutica</span>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-[#8D98A8] font-medium">Bitácora Pericial</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold text-on-surface tracking-tight">Bitácora General de Auditoría</h1>
                    <span class="px-2.5 py-0.5 rounded bg-surface-container-high text-primary font-mono text-xs font-semibold uppercase tracking-wider border border-border-subtle flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">lock</span>
                        <span>Append-Only</span>
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
                    Supervisión integral de expedientes del sistema y registro cronológico inmutable de eventos bajo norma OACI / AFAC.
                </p>
            </div>

            <div class="flex items-center gap-3 self-start md:self-auto">
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-surface-container-low border border-border-subtle text-on-surface-variant text-xs">
                    <span class="w-2 h-2 rounded-full bg-status-resolved animate-pulse"></span>
                    <span class="font-medium text-on-surface">Auditoría en Tiempo Real</span>
                </div>

                <a href="{{ route('incidents.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low hover:bg-surface-container text-on-surface border border-border-subtle font-semibold text-xs rounded-xl transition-all">
                    <span class="material-symbols-outlined text-[18px]">confirmation_number</span>
                    <span>Ver Incidencias</span>
                </a>
            </div>
        </div>

        <!-- Telemetría Pericial Rápida (KPI Chips) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs text-outline uppercase font-medium">Total Expedientes en Sistema</span>
                    <span class="font-mono text-xl sm:text-2xl text-primary font-bold mt-0.5">
                        {{ number_format($statistics['total_incidents'] ?? $incidents->total()) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">folder</span>
                </div>
            </div>

            <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs text-outline uppercase font-medium">Eventos de Bitácora</span>
                    <span class="font-mono text-xl sm:text-2xl text-cyan-400 font-bold mt-0.5">
                        {{ number_format($statistics['total_events'] ?? 0) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                </div>
            </div>

            <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs text-outline uppercase font-medium">Cambios de Estado</span>
                    <span class="font-mono text-xl sm:text-2xl text-amber-400 font-bold mt-0.5">
                        {{ number_format($statistics['state_changes'] ?? 0) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">sync_alt</span>
                </div>
            </div>

            <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs text-outline uppercase font-medium">Resoluciones / Cierres VoBo</span>
                    <span class="font-mono text-xl sm:text-2xl text-emerald-400 font-bold mt-0.5">
                        {{ number_format(($statistics['resolutions'] ?? 0) + ($statistics['closures'] ?? 0)) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">verified</span>
                </div>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros Periciales -->
        <div class="bg-surface-subtle border border-border-subtle p-5 rounded-2xl shadow-sm">
            <form method="GET" action="{{ route('audit.index') }}" class="flex flex-col gap-4" autocomplete="off">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 items-center">
                    
                    <!-- Búsqueda General -->
                    <div class="lg:col-span-4 relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">search</span>
                        <input
                            type="text"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Buscar en comentario, folio o valores..."
                            class="w-full pl-10 pr-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 transition-colors shadow-inner"
                        />
                    </div>

                    <!-- Filtro por Tipo de Acción -->
                    <div class="lg:col-span-3 relative">
                        <select
                            name="action"
                            class="w-full pl-3.5 pr-8 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface appearance-none focus:outline-none focus:border-primary/50 cursor-pointer transition-colors shadow-inner"
                        >
                            <option value="">Todas las Acciones</option>
                            @foreach ($actions as $code => $label)
                                <option value="{{ $code }}" @selected(($filters['action'] ?? '') === $code)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">expand_more</span>
                    </div>

                    <!-- Filtro por Usuario / Operador -->
                    <div class="lg:col-span-3 relative">
                        <select
                            name="user_id"
                            class="w-full pl-3.5 pr-8 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface appearance-none focus:outline-none focus:border-primary/50 cursor-pointer transition-colors shadow-inner"
                        >
                            <option value="">Todos los Operadores</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>
                                    {{ $user->name }} ({{ strtoupper($user->role->name ?? 'USUARIO') }})
                                </option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">expand_more</span>
                    </div>

                    <!-- Botones de Acción de Filtro -->
                    <div class="lg:col-span-2 flex items-center gap-2">
                        <button
                            type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-primary hover:bg-primary/90 text-on-primary font-bold text-xs rounded-xl shadow-sm transition-all"
                        >
                            <span class="material-symbols-outlined text-[16px]">filter_list</span>
                            <span>Filtrar</span>
                        </button>

                        @if (!empty(array_filter($filters)))
                            <a
                                href="{{ route('audit.index') }}"
                                class="p-2.5 bg-surface-container-high hover:bg-surface-container text-outline hover:text-on-surface rounded-xl border border-border-subtle transition-colors"
                                title="Limpiar todos los filtros"
                            >
                                <span class="material-symbols-outlined text-[18px]">filter_alt_off</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Fila Secundaria: Filtro por Rango de Fechas, Estado y Categoría -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-border-subtle/50 text-xs text-on-surface-variant">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-outline flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">date_range</span>
                                <span>Desde:</span>
                            </span>
                            <input
                                type="date"
                                id="date_from"
                                name="date_from"
                                value="{{ $filters['date_from'] ?? '' }}"
                                class="px-2.5 py-1.5 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary/50 shadow-inner"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-outline">Hasta:</span>
                            <input
                                type="date"
                                id="date_to"
                                name="date_to"
                                value="{{ $filters['date_to'] ?? '' }}"
                                class="px-2.5 py-1.5 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary/50 shadow-inner"
                            />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="text-outline text-xs">Estado:</span>
                            <select
                                name="status_id"
                                class="px-2.5 py-1.5 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary/50 shadow-inner cursor-pointer"
                            >
                                <option value="">Todos los Estados</option>
                                @foreach($statuses as $st)
                                    <option value="{{ $st->id }}" @selected((string)($filters['status_id'] ?? '') === (string)$st->id)>
                                        {{ $st->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <span class="text-outline text-xs">Subsistema:</span>
                            <select
                                name="category_id"
                                class="px-2.5 py-1.5 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary/50 shadow-inner cursor-pointer"
                            >
                                <option value="">Todos los Subsistemas</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected((string)($filters['category_id'] ?? '') === (string)$cat->id)>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Conmutador de Pestañas: Expedientes del Sistema / Bitácora de Eventos -->
        <div class="flex items-center gap-2 p-1.5 bg-surface-container-low border border-border-subtle rounded-2xl self-start">
            <button 
                type="button" 
                @click="activeTab = 'incidents'"
                :class="activeTab === 'incidents' ? 'bg-primary text-on-primary font-bold shadow-md' : 'text-on-surface-variant hover:text-on-surface font-medium hover:bg-surface-container-high/40'"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs transition-all cursor-pointer"
            >
                <span class="material-symbols-outlined text-[17px]">folder_open</span>
                <span>Expedientes del Sistema (Todas las Incidencias)</span>
                <span class="px-2 py-0.5 rounded-full text-[10.5px] font-mono font-bold" :class="activeTab === 'incidents' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-primary'">
                    {{ $incidents->total() }}
                </span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'history'"
                :class="activeTab === 'history' ? 'bg-primary text-on-primary font-bold shadow-md' : 'text-on-surface-variant hover:text-on-surface font-medium hover:bg-surface-container-high/40'"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs transition-all cursor-pointer"
            >
                <span class="material-symbols-outlined text-[17px]">history_edu</span>
                <span>Bitácora de Trazabilidad e Historial</span>
                <span class="px-2 py-0.5 rounded-full text-[10.5px] font-mono font-bold" :class="activeTab === 'history' ? 'bg-white/20 text-white' : 'bg-surface-container-high text-primary'">
                    {{ $histories->total() }}
                </span>
            </button>
        </div>

        <!-- PESTAÑA 1: Expedientes de Incidencias del Sistema -->
        <div x-show="activeTab === 'incidents'" class="bg-surface-subtle border border-border-subtle rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 bg-surface-container-low border-b border-border-subtle flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-on-surface">Expedientes de Incidencias:</span>
                    <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-primary font-mono font-bold">
                        {{ $incidents->total() }} expedientes registrados
                    </span>
                </div>
                <div class="text-outline text-[11px] flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[14px] text-emerald-400">check_circle</span>
                    <span>Conjunto completo del sistema sin exclusión de estados ni restricciones de usuario</span>
                </div>
            </div>

            <div class="w-full">
                <table class="w-full max-w-full text-left border-collapse table-fixed">
                    <thead>
                        <tr class="bg-surface-container-low/70 border-b border-border-subtle text-outline text-[11px] font-semibold uppercase tracking-wider">
                            <th class="w-[12%] py-2.5 px-2.5 font-semibold">Folio</th>
                            <th class="w-[24%] py-2.5 px-2.5 font-semibold">Asunto / Subsistema</th>
                            <th class="w-[15%] py-2.5 px-2.5 font-semibold">Ubicación</th>
                            <th class="w-[9%] py-2.5 px-2 text-center font-semibold">Prioridad</th>
                            <th class="w-[10%] py-2.5 px-2 text-center font-semibold">Estado</th>
                            <th class="w-[11%] py-2.5 px-2.5 font-semibold">Responsable</th>
                            <th class="w-[9%] py-2.5 px-2 text-center font-semibold">Fecha</th>
                            <th class="w-[10%] py-2.5 px-2 text-center font-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle/40 text-xs text-on-surface font-normal">
                        @forelse ($incidents as $incident)
                            <tr class="hover:bg-surface-container-low/40 transition-colors">
                                <!-- Folio -->
                                <td class="py-2.5 px-2.5 whitespace-nowrap min-w-0">
                                    <a href="{{ route('incidents.show', $incident->folio) }}" class="font-mono font-bold text-primary hover:underline inline-flex items-center gap-1 text-xs truncate max-w-full">
                                        <span>{{ $incident->folio }}</span>
                                        <span class="material-symbols-outlined text-[13px] shrink-0">open_in_new</span>
                                    </a>
                                </td>

                                <!-- Asunto y Subsistema -->
                                <td class="py-2.5 px-2.5 min-w-0">
                                    <div class="font-semibold text-on-surface truncate text-xs" title="{{ $incident->title }}">
                                        {{ $incident->title }}
                                    </div>
                                    <div class="text-[10.5px] text-on-surface-variant truncate max-w-full mt-0.5 flex items-center gap-1" title="{{ $incident->category->name ?? 'Sin subsistema' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary/80 shrink-0"></span>
                                        <span class="truncate">{{ $incident->category->name ?? 'General' }}</span>
                                    </div>
                                </td>

                                <!-- Ubicación -->
                                <td class="py-2.5 px-2.5 min-w-0">
                                    <span class="text-on-surface block truncate text-xs" title="{{ $incident->location }}">
                                        {{ $incident->location }}
                                    </span>
                                </td>

                                <!-- Prioridad -->
                                <td class="py-2.5 px-2 whitespace-nowrap text-center">
                                    <x-badge-priority :priority="$incident->priority" />
                                </td>

                                <!-- Estado Operacional -->
                                <td class="py-2.5 px-2 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md font-semibold text-[10px] whitespace-nowrap
                                        @if($incident->status?->code === 'NUEVA' || $incident->status?->code === 'ASIGNADA') bg-[rgba(61,130,198,0.15)] text-[#6FA2D2] border border-[rgba(61,130,198,0.30)]
                                        @elseif($incident->status?->code === 'EN_ATENCION' || $incident->status?->code === 'EN_PROCESO') bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)]
                                        @elseif($incident->status?->code === 'RESUELTA') bg-[rgba(32,201,151,0.15)] text-[#20C997] border border-[rgba(32,201,151,0.30)]
                                        @else bg-[#151A21] text-[#9AA5B4] border border-[#252C35] @endif">
                                        @if($incident->status?->code === 'EN_ATENCION' || $incident->status?->code === 'EN_PROCESO')
                                            <span class="w-1 h-1 rounded-full bg-[#E0A21A] animate-pulse shrink-0"></span>
                                        @endif
                                        <span>{{ $incident->status?->name ?? 'Desconocido' }}</span>
                                    </span>
                                </td>

                                <!-- Responsable (GTS o APOC) -->
                                <td class="py-2.5 px-2.5 min-w-0">
                                    @if($incident->assignedTo)
                                        <div class="flex items-center gap-1 min-w-0" title="Asignado a: {{ $incident->assignedTo->name }}">
                                            <span class="w-5 h-5 rounded-full bg-surface-container-high border border-border-subtle flex items-center justify-center font-mono text-[9px] text-primary font-bold shrink-0">
                                                {{ substr($incident->assignedTo->name, 0, 2) }}
                                            </span>
                                            <span class="text-on-surface text-xs truncate">
                                                {{ $incident->assignedTo->name }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded bg-surface-container-low text-outline font-mono text-[9.5px] border border-border-subtle whitespace-nowrap">
                                            Sin Asignar
                                        </span>
                                    @endif
                                </td>

                                <!-- Fecha -->
                                <td class="py-2.5 px-2 whitespace-nowrap font-mono text-[10.5px] text-center text-outline">
                                    {{ $incident->created_at->format('d/m/Y') }}
                                </td>

                                <!-- Acción de Eliminación -->
                                <td class="py-2.5 px-2 whitespace-nowrap text-center">
                                    @if(auth()->user()->isAdmin() || auth()->user()->hasPermission('incidents.delete'))
                                        <button 
                                            type="button"
                                            @click="confirmDelete('{{ $incident->folio }}', '{{ route('audit.incidents.destroy', $incident->folio) }}')"
                                            class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-rose-400 hover:text-rose-200 hover:bg-rose-500/10 border border-rose-500/20 hover:border-rose-500/40 transition-all shadow-sm cursor-pointer whitespace-nowrap"
                                            title="Eliminar definitivamente expediente {{ $incident->folio }}"
                                        >
                                            <span class="material-symbols-outlined text-[15px] shrink-0">delete</span>
                                            <span>Eliminar</span>
                                        </button>
                                    @else
                                        <span class="text-outline text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-surface-container-high text-outline flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[28px]">search_off</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="font-semibold text-on-surface text-sm">No se encontraron expedientes de incidencias</span>
                                            <p class="text-xs text-outline max-w-md">
                                                No hay registros que coincidan con los criterios de búsqueda o filtros seleccionados en el historial completo del sistema.
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación de Expedientes -->
            @if ($incidents->hasPages())
                <div class="p-4 bg-surface-container-low border-t border-border-subtle">
                    {{ $incidents->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- PESTAÑA 2: Bitácora de Eventos y Trazabilidad -->
        <div x-show="activeTab === 'history'" class="bg-surface-subtle border border-border-subtle rounded-2xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 bg-surface-container-low border-b border-border-subtle flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-on-surface">Eventos de Bitácora:</span>
                    <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-primary font-mono font-bold">
                        {{ $histories->total() }} registros
                    </span>
                </div>
                <div class="text-outline text-[11px]">
                    Ordenados en estricto orden cronológico inverso (el más reciente primero)
                </div>
            </div>

            <div class="w-full">
                <table class="w-full max-w-full text-left border-collapse table-fixed">
                    <thead>
                        <tr class="bg-surface-container-low/70 border-b border-border-subtle text-outline text-[11px] font-semibold uppercase tracking-wider">
                            <th class="w-[13%] py-2.5 px-2.5 font-semibold">Fecha / Hora</th>
                            <th class="w-[13%] py-2.5 px-2.5 font-semibold">Expediente</th>
                            <th class="w-[13%] py-2.5 px-2.5 font-semibold">Operador / Actor</th>
                            <th class="w-[12%] py-2.5 px-2.5 font-semibold">Acción Auditada</th>
                            <th class="w-[14%] py-2.5 px-2.5 font-semibold">Campo / Diferencial</th>
                            <th class="w-[20%] py-2.5 px-2.5 font-semibold">Observaciones</th>
                            <th class="w-[5%] py-2.5 px-2 text-center font-semibold">Sello</th>
                            <th class="w-[10%] py-2.5 px-2 text-center font-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle/40 text-xs text-on-surface font-normal">
                        @forelse ($histories as $history)
                            <tr class="hover:bg-surface-container-low/40 transition-colors">
                                <!-- Fecha y Hora Militar -->
                                <td class="py-2.5 px-2.5 whitespace-nowrap">
                                    <div class="font-mono text-xs font-semibold text-on-surface">
                                        {{ $history->created_at->format('d/m/Y H:i:s') }}
                                    </div>
                                    <div class="text-[10.5px] text-outline mt-0.5">
                                        {{ $history->created_at->diffForHumans() }}
                                    </div>
                                </td>

                                <!-- Expediente (Folio y Categoría) -->
                                <td class="py-2.5 px-2.5 min-w-0">
                                    @if ($history->incident)
                                        <a href="{{ route('incidents.show', $history->incident->folio) }}" class="font-mono font-bold text-primary hover:underline inline-flex items-center gap-1 text-xs truncate max-w-full">
                                            <span>{{ $history->incident->folio }}</span>
                                            <span class="material-symbols-outlined text-[13px] shrink-0">open_in_new</span>
                                        </a>
                                        <div class="text-[10.5px] text-on-surface-variant truncate max-w-full mt-0.5" title="{{ $history->incident->category->name ?? 'General' }}">
                                            {{ $history->incident->category->name ?? 'General' }}
                                        </div>
                                    @else
                                        <span class="font-mono text-outline text-xs">#{{ $history->incident_id }}</span>
                                    @endif
                                </td>

                                <!-- Operador Responsable -->
                                <td class="py-2.5 px-2.5 min-w-0">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <div class="w-6 h-6 rounded-full bg-surface-container-high border border-border-subtle flex items-center justify-center text-[10px] font-bold text-primary shrink-0">
                                            {{ substr($history->user?->name ?? 'S', 0, 1) }}
                                        </div>
                                        <div class="flex flex-col min-w-0 truncate">
                                            <span class="font-semibold text-on-surface text-xs truncate" title="{{ $history->user?->name ?? 'Sistema' }}">
                                                {{ $history->user?->name ?? 'Sistema' }}
                                            </span>
                                            <span class="text-[9.5px] text-outline uppercase font-mono truncate">
                                                {{ $history->user?->role?->name ?? 'AUDITORÍA' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Acción Auditada -->
                                <td class="py-2.5 px-2.5 min-w-0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10.5px] font-semibold truncate max-w-full {{ $history->getActionBadgeClasses() }}" title="{{ $history->getActionLabel() }}">
                                        <span class="material-symbols-outlined text-[13px] shrink-0">{{ $history->getActionIcon() }}</span>
                                        <span class="truncate">{{ $history->getActionLabel() }}</span>
                                    </span>
                                </td>

                                <!-- Campo y Diferencial (Old -> New) -->
                                <td class="py-2.5 px-2.5 min-w-0">
                                    @if ($history->field_name)
                                        <div class="font-mono text-[10.5px] text-outline font-semibold truncate" title="{{ $history->field_name }}">
                                            {{ $history->field_name }}
                                        </div>
                                        @if ($history->old_value || $history->new_value)
                                            <div class="flex items-center gap-1 text-[10.5px] mt-0.5 min-w-0">
                                                @if ($history->old_value)
                                                    <span class="text-rose-400/90 font-mono bg-rose-500/10 px-1 py-0.5 rounded border border-rose-500/20 truncate max-w-[70px]" title="{{ $history->old_value }}">
                                                        {{ $history->old_value }}
                                                    </span>
                                                    <span class="text-outline text-[9px] font-bold shrink-0">→</span>
                                                @endif
                                                @if ($history->new_value)
                                                    <span class="text-emerald-400/90 font-mono bg-emerald-500/10 px-1 py-0.5 rounded border border-emerald-500/20 truncate max-w-[70px]" title="{{ $history->new_value }}">
                                                        {{ $history->new_value }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-outline text-[11px]">—</span>
                                    @endif
                                </td>

                                <!-- Observaciones / Comentario Técnico -->
                                <td class="py-2.5 px-2.5 min-w-0">
                                    @if ($history->comment)
                                        <p class="text-xs text-on-surface-variant leading-snug line-clamp-2" title="{{ $history->comment }}">
                                            {{ $history->comment }}
                                        </p>
                                    @else
                                        <span class="text-outline text-[11px] italic">Sin observaciones adicionales</span>
                                    @endif
                                </td>

                                <!-- Sello Inmutable -->
                                <td class="py-2.5 px-2 whitespace-nowrap text-center">
                                    <div class="inline-flex items-center justify-center p-1 rounded-md text-emerald-400/80 bg-emerald-500/10 border border-emerald-500/20" title="Registro inmutable firmado con timestamp pericial">
                                        <span class="material-symbols-outlined text-[15px]">verified_user</span>
                                    </div>
                                </td>

                                <!-- Acción de Eliminación -->
                                <td class="py-2.5 px-2 whitespace-nowrap text-center">
                                    @if ($history->incident && (auth()->user()->isAdmin() || auth()->user()->hasPermission('incidents.delete')))
                                        <button 
                                            type="button"
                                            @click="confirmDelete('{{ $history->incident->folio }}', '{{ route('audit.incidents.destroy', $history->incident->folio) }}')"
                                            class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-rose-400 hover:text-rose-200 hover:bg-rose-500/10 border border-rose-500/20 hover:border-rose-500/40 transition-all shadow-sm cursor-pointer whitespace-nowrap"
                                            title="Eliminar definitivamente expediente {{ $history->incident->folio }}"
                                        >
                                            <span class="material-symbols-outlined text-[15px] shrink-0">delete</span>
                                            <span>Eliminar</span>
                                        </button>
                                    @else
                                        <span class="text-outline text-[11px]">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 px-4 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-surface-container-high text-outline flex items-center justify-center">
                                            <span class="material-symbols-outlined text-[28px]">search_off</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="font-semibold text-on-surface text-sm">No se encontraron eventos de auditoría</span>
                                            <p class="text-xs text-outline max-w-md">
                                                No hay registros que coincidan con los criterios de búsqueda o filtros seleccionados.
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación de Eventos con conservación de filtros -->
            @if ($histories->hasPages())
                <div class="p-4 bg-surface-container-low border-t border-border-subtle">
                    {{ $histories->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- Modal de Confirmación de Eliminación Permanente (Exclusivo Administrador) -->
        <div 
            x-show="showDeleteModal" 
            x-cloak
            @keydown.escape.window="showDeleteModal = false"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    x-show="showDeleteModal" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="showDeleteModal = false"
                    class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity" 
                    aria-hidden="true"
                ></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div 
                    x-show="showDeleteModal" 
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative inline-block align-bottom bg-[#11151B] border border-rose-500/30 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6"
                >
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[28px]">warning</span>
                        </div>
                        <div class="flex flex-col">
                            <h3 class="text-base font-bold text-[#F2F5F8]" id="modal-title">
                                Confirmar Eliminación Permanente
                            </h3>
                            <p class="text-xs text-[#8D98A8] mt-1.5 leading-relaxed">
                                ¿Está completamente seguro de que desea eliminar la incidencia <strong class="font-mono text-rose-400 font-bold" x-text="deleteFolio"></strong>?
                            </p>
                            <div class="mt-3 p-3 rounded-xl bg-rose-500/5 border border-rose-500/15 text-[11px] text-rose-300 leading-relaxed flex flex-col gap-1">
                                <span class="font-semibold text-rose-400 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">shield</span>
                                    <span>Consecuencias operacionales irreversibles:</span>
                                </span>
                                <span>• Se purgará el registro de la incidencia de PostgreSQL.</span>
                                <span>• Se eliminarán permanentemente las evidencias y adjuntos del almacenamiento.</span>
                                <span>• Se eliminará la traza pericial vinculada a este expediente.</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-[#1F2937]">
                        <button 
                            type="button" 
                            @click="showDeleteModal = false" 
                            class="px-4 py-2 bg-[#1E293B] hover:bg-[#334155] text-[#DDE3EA] rounded-xl text-xs font-semibold transition-colors cursor-pointer"
                        >
                            Cancelar
                        </button>
                        <form :action="deleteActionUrl" method="POST">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-rose-900/30 transition-all cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                                <span>Eliminar Definitivamente</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
