<x-app-layout>
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6">
        
        <!-- Encabezado y Contexto Operacional -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs uppercase tracking-widest text-primary font-semibold">Trazabilidad Aeronáutica</span>
                    <span class="text-outline text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-on-surface-variant font-medium">Control Pericial APOC & GTS</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold text-on-surface tracking-tight">Bitácora General de Auditoría</h1>
                    <span class="px-2.5 py-0.5 rounded bg-surface-container-high text-primary font-mono text-xs font-semibold uppercase tracking-wider border border-border-subtle flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">lock</span>
                        <span>Append-Only</span>
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
                    Registro cronológico e inmutable de eventos, intervenciones de cuadrilla y transiciones operativas bajo norma OACI / AFAC.
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
                    <span class="text-xs text-outline uppercase font-medium">Total Eventos Registrados</span>
                    <span class="font-mono text-xl sm:text-2xl text-primary font-bold mt-0.5">
                        {{ number_format($statistics['total_events'] ?? 0) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
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
                    <span class="text-xs text-outline uppercase font-medium">Notas Técnicas en Campo</span>
                    <span class="font-mono text-xl sm:text-2xl text-cyan-400 font-bold mt-0.5">
                        {{ number_format($statistics['technical_notes'] ?? 0) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">edit_note</span>
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
            <form method="GET" action="{{ route('audit.index') }}" class="flex flex-col gap-4">
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

                <!-- Fila Secundaria: Filtro por Rango de Fechas -->
                <div class="flex flex-wrap items-center gap-3 pt-1 border-t border-border-subtle/50 text-xs text-on-surface-variant">
                    <span class="font-medium text-outline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">date_range</span>
                        <span>Rango de Fecha:</span>
                    </span>
                    <div class="flex items-center gap-2">
                        <label for="date_from" class="text-outline">Desde:</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="{{ $filters['date_from'] ?? '' }}"
                            class="px-2.5 py-1.5 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary/50 shadow-inner"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="date_to" class="text-outline">Hasta:</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="{{ $filters['date_to'] ?? '' }}"
                            class="px-2.5 py-1.5 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary/50 shadow-inner"
                        />
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla Táctica de Trazabilidad e Historial -->
        <div class="bg-surface-subtle border border-border-subtle rounded-2xl shadow-sm overflow-hidden flex flex-col">
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

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low/70 border-b border-border-subtle text-outline text-[11px] font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Fecha / Hora</th>
                            <th class="py-3 px-4">Expediente</th>
                            <th class="py-3 px-4">Operador / Actor</th>
                            <th class="py-3 px-4">Acción Auditada</th>
                            <th class="py-3 px-4">Campo / Diferencial</th>
                            <th class="py-3 px-4">Observaciones / Comentario</th>
                            <th class="py-3 px-4 text-center">Sello</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle/40 text-xs text-on-surface font-normal">
                        @forelse ($histories as $history)
                            <tr class="hover:bg-surface-container-low/40 transition-colors">
                                <!-- Fecha y Hora Militar -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-mono text-xs font-semibold text-on-surface">
                                        {{ $history->created_at->format('d/m/Y H:i:s') }}
                                    </div>
                                    <div class="text-[11px] text-outline mt-0.5">
                                        {{ $history->created_at->diffForHumans() }}
                                    </div>
                                </td>

                                <!-- Expediente (Folio y Categoría) -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if ($history->incident)
                                        <a href="{{ route('incidents.show', $history->incident->folio) }}" class="font-mono font-bold text-primary hover:underline flex items-center gap-1">
                                            <span>{{ $history->incident->folio }}</span>
                                            <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                                        </a>
                                        <div class="text-[11px] text-on-surface-variant truncate max-w-[180px] mt-0.5">
                                            {{ $history->incident->category->name }}
                                        </div>
                                    @else
                                        <span class="font-mono text-outline">#{{ $history->incident_id }}</span>
                                    @endif
                                </td>

                                <!-- Operador Responsable -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-surface-container-high border border-border-subtle flex items-center justify-center text-[10px] font-bold text-primary">
                                            {{ substr($history->user?->name ?? 'S', 0, 1) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-on-surface text-xs">{{ $history->user?->name ?? 'Sistema' }}</span>
                                            <span class="text-[10px] text-outline uppercase font-mono">
                                                {{ $history->user?->role?->name ?? 'AUDITORÍA' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Acción Auditada -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold {{ $history->getActionBadgeClasses() }}">
                                        <span class="material-symbols-outlined text-[14px]">{{ $history->getActionIcon() }}</span>
                                        <span>{{ $history->getActionLabel() }}</span>
                                    </span>
                                </td>

                                <!-- Campo y Diferencial (Old -> New) -->
                                <td class="py-3.5 px-4">
                                    @if ($history->field_name)
                                        <div class="font-mono text-[11px] text-outline font-semibold">
                                            {{ $history->field_name }}
                                        </div>
                                        @if ($history->old_value || $history->new_value)
                                            <div class="flex items-center gap-1 text-[11px] mt-0.5">
                                                @if ($history->old_value)
                                                    <span class="text-rose-400/90 font-mono bg-rose-500/10 px-1 py-0.5 rounded border border-rose-500/20 max-w-[120px] truncate" title="{{ $history->old_value }}">
                                                        {{ $history->old_value }}
                                                    </span>
                                                    <span class="text-outline text-[10px] font-bold">→</span>
                                                @endif
                                                @if ($history->new_value)
                                                    <span class="text-emerald-400/90 font-mono bg-emerald-500/10 px-1 py-0.5 rounded border border-emerald-500/20 max-w-[120px] truncate" title="{{ $history->new_value }}">
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
                                <td class="py-3.5 px-4 max-w-sm">
                                    @if ($history->comment)
                                        <p class="text-xs text-on-surface-variant leading-relaxed line-clamp-2" title="{{ $history->comment }}">
                                            {{ $history->comment }}
                                        </p>
                                    @else
                                        <span class="text-outline text-[11px] italic">Sin observaciones adicionales</span>
                                    @endif
                                </td>

                                <!-- Sello Inmutable -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                    <div class="inline-flex items-center justify-center p-1 rounded-md text-emerald-400/80 bg-emerald-500/10 border border-emerald-500/20" title="Registro inmutable firmado con timestamp pericial">
                                        <span class="material-symbols-outlined text-[16px]">verified_user</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 px-4 text-center">
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

            <!-- Paginación con conservación de filtros -->
            @if ($histories->hasPages())
                <div class="p-4 bg-surface-container-low border-t border-border-subtle">
                    {{ $histories->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

