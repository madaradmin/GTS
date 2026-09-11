<x-app-layout>
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6">
        
        <!-- Encabezado y Acciones Principales -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs uppercase tracking-widest text-primary font-semibold">Operaciones AIFA</span>
                    <span class="text-outline text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-on-surface-variant font-medium">Control APOC & GTS</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold text-on-surface tracking-tight">Todas las Incidencias</h1>
                    <span class="px-2.5 py-0.5 rounded bg-surface-container-high text-primary font-mono text-xs font-semibold uppercase tracking-wider border border-border-subtle">
                        AIFA-GTS
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-on-surface-variant mt-1">
                    <span class="font-semibold text-on-surface">{{ $incidents->total() }}</span> incidencias registradas en la plataforma técnica
                </p>
            </div>

            <div class="flex items-center gap-3 self-start md:self-auto">
                <div class="hidden lg:flex items-center gap-2 px-3 py-2 rounded-xl bg-surface-container-low border border-border-subtle text-on-surface-variant text-xs">
                    <span class="w-2 h-2 rounded-full bg-status-resolved animate-pulse"></span>
                    <span class="font-medium text-on-surface">Canal Operativo Activo</span>
                </div>

                @can('create', App\Models\Incident::class)
                    <a href="{{ route('incidents.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-container hover:bg-primary text-on-primary-container font-semibold text-xs rounded-xl shadow-sm transition-all">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Nueva Incidencia</span>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Telemetría Rápida de Guardia (KPI Chips) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs text-outline uppercase font-medium">Nuevas sin atender</span>
                    <span class="font-mono text-xl sm:text-2xl text-[#3D82C6] font-bold mt-0.5">
                        {{ sprintf('%02d', $metrics['nuevas'] ?? 0) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[#123A70]/30 text-[#3D82C6] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">inbox</span>
                </div>
            </div>

            <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs text-outline uppercase font-medium">En Atención GTS</span>
                    <span class="font-mono text-xl sm:text-2xl text-[#E0A21A] font-bold mt-0.5">
                        {{ sprintf('%02d', $metrics['en_atencion'] ?? 0) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[#E0A21A]/15 text-[#E0A21A] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">pending_actions</span>
                </div>
            </div>

            <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs text-outline uppercase font-medium">Críticas Activas</span>
                    <span class="font-mono text-xl sm:text-2xl text-status-critical font-bold mt-0.5">
                        {{ sprintf('%02d', $metrics['criticas'] ?? 0) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-status-critical/10 text-status-critical flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">warning</span>
                </div>
            </div>

            <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex flex-col">
                    <span class="text-xs text-outline uppercase font-medium">Resueltas</span>
                    <span class="font-mono text-xl sm:text-2xl text-[#20C997] font-bold mt-0.5">
                        {{ sprintf('%02d', $metrics['resueltas'] ?? 0) }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[#20C997]/15 text-[#20C997] flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">task_alt</span>
                </div>
            </div>
        </div>

        <!-- Barra de Búsqueda y Filtros Multidimensionales -->
        <form method="GET" action="{{ route('incidents.index') }}" class="bg-surface-subtle border border-border-subtle p-4 rounded-xl shadow-sm flex flex-col gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <!-- Búsqueda por Texto -->
                <div class="relative lg:col-span-4">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                        search
                    </span>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Buscar por folio, asunto o ubicación..."
                        class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-border-focus transition-colors shadow-inner"
                    />
                </div>

                <!-- Filtro de Estado -->
                <div class="relative lg:col-span-2">
                    <select
                        name="status_id"
                        class="w-full appearance-none px-3 py-2 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-border-focus transition-colors pr-8 cursor-pointer shadow-inner"
                    >
                        <option value="">Todos los estados</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->id }}" {{ request('status_id') == $st->id ? 'selected' : '' }}>
                                {{ $st->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                        expand_more
                    </span>
                </div>

                <!-- Filtro de Prioridad -->
                <div class="relative lg:col-span-2">
                    <select
                        name="priority_id"
                        class="w-full appearance-none px-3 py-2 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-border-focus transition-colors pr-8 cursor-pointer shadow-inner"
                    >
                        <option value="">Todas las prioridades</option>
                        @foreach ($priorities as $pr)
                            <option value="{{ $pr->id }}" {{ request('priority_id') == $pr->id ? 'selected' : '' }}>
                                {{ $pr->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                        expand_more
                    </span>
                </div>

                <!-- Filtro de Categoría / Subsistema -->
                <div class="relative lg:col-span-2">
                    <select
                        name="category_id"
                        class="w-full appearance-none px-3 py-2 bg-surface-container-low border border-border-subtle rounded-lg text-xs text-on-surface focus:outline-none focus:border-border-focus transition-colors pr-8 cursor-pointer shadow-inner"
                    >
                        <option value="">Todas las categorías</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                        expand_more
                    </span>
                </div>

                <!-- Botones de Acción de Filtro -->
                <div class="flex items-center gap-2 lg:col-span-2">
                    <button
                        type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-surface-container-high hover:bg-surface-container text-primary text-xs font-semibold rounded-lg border border-border-subtle transition-colors shadow-sm"
                    >
                        <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                        <span>Filtrar</span>
                    </button>
                    @if(request()->hasAny(['search', 'status_id', 'priority_id', 'category_id']))
                        <a
                            href="{{ route('incidents.index') }}"
                            class="text-xs text-outline hover:text-on-surface px-2.5 py-2 transition-colors whitespace-nowrap"
                        >
                            Limpiar
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <!-- Tabla Principal de Incidencias -->
        <div class="bg-surface-subtle border border-border-subtle rounded-xl overflow-hidden shadow-lg flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-border-subtle text-outline uppercase font-mono text-[11px] tracking-wider">
                            <th class="py-3 px-4 font-semibold">Folio</th>
                            <th class="py-3 px-4 font-semibold">Título / Asunto</th>
                            <th class="py-3 px-4 font-semibold">Área / Ubicación</th>
                            <th class="py-3 px-4 font-semibold">Prioridad</th>
                            <th class="py-3 px-4 font-semibold">Estado</th>
                            <th class="py-3 px-4 font-semibold">Responsable</th>
                            <th class="py-3 px-4 font-semibold">Fecha / Hora</th>
                            <th class="py-3 px-4 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle text-xs" id="incidentsTableBody">
                        @forelse ($incidents as $incident)
                            <tr class="hover:bg-surface-container-low/60 transition-colors group">
                                <!-- Folio -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-mono text-primary font-semibold tracking-wide text-xs">
                                        {{ $incident->folio }}
                                    </span>
                                </td>

                                <!-- Título y Categoría -->
                                <td class="py-3.5 px-4 min-w-[240px]">
                                    <div class="font-semibold text-on-surface group-hover:text-primary transition-colors line-clamp-1">
                                        {{ $incident->title }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-primary/80"></span>
                                        <span class="text-[11px] text-on-surface-variant font-medium">
                                            {{ $incident->category->name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Ubicación -->
                                <td class="py-3.5 px-4 min-w-[180px]">
                                    <span class="text-on-surface font-medium block truncate">
                                        {{ $incident->location }}
                                    </span>
                                    <span class="text-[11px] text-outline flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-[13px]">location_on</span>
                                        AIFA Operaciones
                                    </span>
                                </td>

                                <!-- Prioridad -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <x-badge-priority :priority="$incident->priority" />
                                </td>

                                <!-- Estado -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold text-[11px] uppercase tracking-wider
                                        @if($incident->status->code === 'NUEVA' || $incident->status->code === 'ASIGNADA') bg-[rgba(61,130,198,0.15)] text-[#6FA2D2] border border-[rgba(61,130,198,0.30)]
                                        @elseif($incident->status->code === 'EN_ATENCION' || $incident->status->code === 'EN_PROCESO') bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)]
                                        @elseif($incident->status->code === 'RESUELTA') bg-[rgba(32,201,151,0.15)] text-[#20C997] border border-[rgba(32,201,151,0.30)]
                                        @else bg-[#151A21] text-[#9AA5B4] border border-[#252C35] @endif">
                                        @if($incident->status->code === 'EN_ATENCION' || $incident->status->code === 'EN_PROCESO')
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#E0A21A] animate-pulse"></span>
                                        @endif
                                        {{ $incident->status->name }}
                                    </span>
                                </td>

                                <!-- Responsable GTS -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($incident->assignedTo)
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-surface-container-high border border-border-subtle flex items-center justify-center font-mono text-[10px] text-primary font-bold">
                                                {{ substr($incident->assignedTo->name, 0, 2) }}
                                            </span>
                                            <span class="text-on-surface font-medium text-xs truncate max-w-[140px]">
                                                {{ $incident->assignedTo->name }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-surface-container-low text-outline font-mono text-[10px] border border-border-subtle">
                                            Sin Asignar
                                        </span>
                                    @endif
                                </td>

                                <!-- Fecha y Hora -->
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[11px] text-on-surface-variant">
                                    <span class="block text-on-surface">{{ $incident->created_at->format('d M Y') }}</span>
                                    <span class="text-outline">{{ $incident->created_at->format('H:i') }} hrs</span>
                                </td>

                                <!-- Acciones -->
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <a
                                        href="{{ route('incidents.show', $incident->folio) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-surface-container-high hover:bg-surface-container text-primary text-xs font-semibold border border-border-subtle transition-colors shadow-sm"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        <span>Ver detalle</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-surface-container-low border border-border-subtle flex items-center justify-center text-outline">
                                            <span class="material-symbols-outlined text-[28px]">search_off</span>
                                        </div>
                                        <div class="flex flex-col gap-1 max-w-sm">
                                            <h3 class="text-sm font-semibold text-on-surface">No se encontraron incidencias</h3>
                                            <p class="text-xs text-outline">
                                                No hay registros que coincidan con los filtros aplicados o aún no se han registrado incidencias en la plataforma.
                                            </p>
                                        </div>
                                        @if(request()->hasAny(['search', 'status_id', 'priority_id', 'category_id']))
                                            <a
                                                href="{{ route('incidents.index') }}"
                                                class="mt-2 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-surface-container-high text-primary text-xs font-semibold hover:bg-surface-container transition-colors"
                                            >
                                                Restablecer filtros
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if ($incidents->hasPages())
                <div class="p-4 border-t border-border-subtle bg-surface-container-low flex items-center justify-between">
                    {{ $incidents->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>