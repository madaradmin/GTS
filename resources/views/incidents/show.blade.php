<x-app-layout>
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6" x-data="{ showAssignModal: false, showResolveModal: false, showRejectModal: false, showCloseModal: false, showUploadModal: false }">
        
        <!-- Notificaciones Flash -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-status-resolved/10 border border-status-resolved/30 text-status-resolved flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2 font-medium text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-xl bg-status-critical/10 border border-status-critical/30 text-status-critical flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2 font-medium text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-[20px]">error</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <!-- 1. Encabezado de la Incidencia -->
        <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-md flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <span class="font-mono text-xl sm:text-2xl font-bold text-primary tracking-wide">
                        {{ $incident->folio }}
                    </span>

                    <!-- Badge de Estado -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-semibold text-xs uppercase tracking-wider
                        @if($incident->status->code === 'NUEVA' || $incident->status->code === 'ASIGNADA') bg-[rgba(61,130,198,0.15)] text-[#6FA2D2] border border-[rgba(61,130,198,0.30)]
                        @elseif($incident->status->code === 'EN_ATENCION' || $incident->status->code === 'EN_PROCESO') bg-[rgba(224,162,26,0.15)] text-[#E2B24C] border border-[rgba(224,162,26,0.30)]
                        @elseif($incident->status->code === 'RESUELTA') bg-[rgba(32,201,151,0.15)] text-[#20C997] border border-[rgba(32,201,151,0.30)]
                        @else bg-[#151A21] text-[#9AA5B4] border border-[#252C35] @endif">
                        @if($incident->status->code === 'EN_ATENCION' || $incident->status->code === 'EN_PROCESO')
                            <span class="w-2 h-2 rounded-full bg-[#E0A21A] animate-pulse"></span>
                        @endif
                        {{ $incident->status->name }}
                    </span>

                    <!-- Badge de Prioridad -->
                    <x-badge-priority :priority="$incident->priority" />

                    <!-- Procedencia CCO / CSA -->
                    @if($incident->isFromCco())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold text-xs font-mono uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/30" title="Procedencia de la incidencia">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            APOC-CCO
                        </span>
                    @elseif($incident->isFromCsa())
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold text-xs font-mono uppercase tracking-wider bg-orange-500/10 text-orange-400 border border-orange-500/30" title="Procedencia de la incidencia">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                            APOC-CSA
                        </span>
                    @elseif($badge = $incident->origin_badge)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold text-xs font-mono uppercase tracking-wider {{ $badge['bg'] }} {{ $badge['text'] }} border {{ $badge['border'] }}" title="{{ $badge['title'] }}">
                            {{ $badge['label'] }}
                        </span>
                    @endif

                    <span class="text-xs text-outline font-mono hidden sm:inline">
                        | {{ $incident->category->name }}
                    </span>
                </div>

                <h1 class="text-xl sm:text-2xl font-bold text-on-surface tracking-tight">
                    {{ $incident->title }}
                </h1>
            </div>

            <!-- Botones de Acción Táctica -->
            <div class="flex flex-wrap items-center gap-2.5 self-start lg:self-center">
                @can('take', $incident)
                    @if(is_null($incident->assigned_to_user_id))
                        <!-- Acción Rápida: Tomar Incidencia (Auto-asignación) -->
                        <form method="POST" action="{{ route('incidents.assign', $incident) }}" class="inline">
                            @csrf
                            <input type="hidden" name="assigned_to_user_id" value="{{ auth()->id() }}">
                            <input type="hidden" name="comment" value="Toma técnica directa por el especialista de guardia.">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary/90 text-on-primary font-semibold text-xs rounded-xl shadow-sm transition-all"
                            >
                                <span class="material-symbols-outlined text-[18px]">engineering</span>
                                <span>Tomar Incidencia</span>
                            </button>
                        </form>
                    @endif
                @endcan

                @can('assign', $incident)
                    <!-- Botón para Abrir Modal de Asignación / Reasignación -->
                    <button
                        type="button"
                        @click="showAssignModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-high hover:bg-surface-container text-primary border border-border-subtle font-semibold text-xs rounded-xl shadow-sm transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                        <span>{{ ($incident->assignedTo || $incident->isAssignedToTics()) ? 'Reasignar Especialista' : 'Asignar Especialista' }}</span>
                    </button>
                @endcan

                @can('startAttention', $incident)
                    <!-- Botón: Iniciar Atención Técnica (Paso 22) -->
                    <form method="POST" action="{{ route('incidents.start-attention', $incident) }}" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-sm transition-all"
                        >
                            <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                            <span>Iniciar Atención</span>
                        </button>
                    </form>
                @endcan

                @can('resolve', $incident)
                    <!-- Botón: Marcar como Resuelta (Paso 23) -->
                    <button
                        type="button"
                        @click="showResolveModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">task_alt</span>
                        <span>Marcar como Resuelta</span>
                    </button>
                @endcan

                @can('rejectResolution', $incident)
                    <!-- Botón: Rechazar Solución (Paso 24 - APOC) -->
                    <button
                        type="button"
                        @click="showRejectModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/40 font-bold text-xs rounded-xl shadow-sm transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">replay</span>
                        <span>Rechazar Solución</span>
                    </button>
                @endcan

                @can('close', $incident)
                    <!-- Botón: Dar Visto Bueno y Cerrar (Paso 25 - APOC) -->
                    <button
                        type="button"
                        @click="showCloseModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary/90 text-on-primary font-bold text-xs rounded-xl shadow-sm transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        <span>Dar VoBo y Cerrar</span>
                    </button>
                @endcan

                @can('create', App\Models\Incident::class)
                    <a
                        href="{{ route('incidents.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-low hover:bg-surface-container text-on-surface border border-border-subtle font-semibold text-xs rounded-xl transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        <span>Nueva</span>
                    </a>
                @endcan
            </div>
        </div>

        <!-- Banner de Incidencia Cerrada Definitiva (Inmutable - Paso 25) -->
        @if ($incident->status->code === 'CERRADA')
            @php
                $closeHistory = $incident->histories->first(fn($h) => $h->action === 'CERRADA');
                $closerUser = $closeHistory?->user;
            @endphp
            <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-700/60 text-slate-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md">
                <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold">
                    <span class="material-symbols-outlined text-slate-400 text-[22px]">lock</span>
                    <span>Expediente Cerrado y Archivado — Modo de Solo Lectura Inmutable</span>
                    <span class="text-outline font-normal hidden md:inline">• Visto Bueno otorgado por {{ $closerUser ? $closerUser->getTaggedName() : 'Operador' }}</span>
                </div>
                <div class="font-mono text-xs text-outline flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">task_alt</span>
                    <span>Cierre: {{ $incident->closed_at?->format('d/m/Y H:i') ?? 'N/D' }} hrs</span>
                </div>
            </div>
        @endif

        <!-- Banner de Observación de Rechazo Previo (Paso 24) -->
        @if ($incident->rejection_reason && $incident->status->code === 'EN_ATENCION')
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 flex flex-col sm:flex-row sm:items-start justify-between gap-3 shadow-sm">
                <div class="flex items-start gap-2.5 text-xs sm:text-sm">
                    <span class="material-symbols-outlined text-rose-400 text-[22px] shrink-0 mt-0.5">warning</span>
                    <div class="flex flex-col gap-1">
                        <span class="font-bold text-rose-400">Solución Técnica Previa Rechazada por APOC</span>
                        <p class="text-xs text-rose-200/90 leading-relaxed font-sans">
                            {{ $incident->rejection_reason }}
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded bg-rose-500/20 text-rose-300 text-[11px] font-mono shrink-0 self-start">
                    Reingreso a Diagnóstico
                </span>
            </div>
        @endif

        <!-- Banner de Atención Técnica Activa -->
        @if ($incident->status->code === 'EN_ATENCION')
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                    <span>Incidencia en Atención Técnica y Diagnóstico de Campo</span>
                    <span class="text-outline font-normal hidden md:inline">• Responsable: {{ $incident->isAssignedToTics() ? 'TICS' : ($incident->assignedTo?->name ?? 'Sin asignar') }}</span>
                </div>
                <div class="font-mono text-xs text-amber-400/80 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">schedule</span>
                    <span>Inicio: {{ $incident->attended_at?->format('H:i') ?? 'N/D' }} hrs</span>
                </div>
            </div>
        @endif

        <!-- 2. Stepper Visual del Ciclo de Vida (FSM) -->
        <div class="bg-surface-subtle border border-border-subtle p-4 rounded-xl shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 sm:gap-4">
                <!-- Paso 1: Nueva -->
                @php $sOrder = $incident->status->step_order; @endphp
                <div class="p-3 rounded-lg flex items-center gap-3 {{ $sOrder >= 1 ? 'bg-surface-container-low border border-border-subtle' : 'opacity-40' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $sOrder > 1 ? 'bg-status-resolved/20 text-status-resolved' : ($sOrder === 1 ? 'bg-[#123A70]/30 text-[#3D82C6] ring-2 ring-[#164A8A]/40' : 'bg-surface-container-high text-outline') }}">
                        @if($sOrder > 1) <span class="material-symbols-outlined text-[18px]">check</span> @else 1 @endif
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-semibold text-xs text-on-surface">1. Nueva</span>
                        <span class="text-[10px] text-outline truncate font-mono">{{ $incident->reported_at?->format('d M H:i') ?? 'Pendiente' }}</span>
                    </div>
                </div>

                <!-- Paso 2: Asignada -->
                <div class="p-3 rounded-lg flex items-center gap-3 {{ $sOrder >= 2 ? 'bg-surface-container-low border border-border-subtle' : 'opacity-40' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $sOrder > 2 ? 'bg-status-resolved/20 text-status-resolved' : ($sOrder === 2 ? 'bg-purple-500/20 text-purple-400 ring-2 ring-purple-500/30' : 'bg-surface-container-high text-outline') }}">
                        @if($sOrder > 2) <span class="material-symbols-outlined text-[18px]">check</span> @else 2 @endif
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-semibold text-xs text-on-surface">2. Asignada</span>
                        <span class="text-[10px] text-outline truncate font-mono">
                            {{ $incident->assigned_at ? $incident->assigned_at->format('d M H:i') : ($sOrder >= 2 ? 'En espera' : 'Pendiente') }}
                        </span>
                    </div>
                </div>

                <!-- Paso 3: En Atención -->
                <div class="p-3 rounded-lg flex items-center gap-3 {{ $sOrder >= 3 ? 'bg-surface-container-low border border-border-subtle' : 'opacity-40' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $sOrder > 3 ? 'bg-status-resolved/20 text-status-resolved' : ($sOrder === 3 ? 'bg-amber-500/20 text-amber-400 ring-2 ring-amber-500/30' : 'bg-surface-container-high text-outline') }}">
                        @if($sOrder > 3) <span class="material-symbols-outlined text-[18px]">check</span> @else 3 @endif
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-semibold text-xs text-on-surface">3. En Atención</span>
                        <span class="text-[10px] text-outline truncate font-mono">
                            {{ $incident->attended_at ? $incident->attended_at->format('d M H:i') : 'Pendiente' }}
                        </span>
                    </div>
                </div>

                <!-- Paso 4: Resuelta -->
                <div class="p-3 rounded-lg flex items-center gap-3 {{ $sOrder >= 4 ? 'bg-surface-container-low border border-border-subtle' : 'opacity-40' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $sOrder > 4 ? 'bg-status-resolved/20 text-status-resolved' : ($sOrder === 4 ? 'bg-emerald-500/20 text-emerald-400 ring-2 ring-emerald-500/30' : 'bg-surface-container-high text-outline') }}">
                        @if($sOrder > 4) <span class="material-symbols-outlined text-[18px]">check</span> @else 4 @endif
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-semibold text-xs text-on-surface">4. Resuelta</span>
                        <span class="text-[10px] text-outline truncate font-mono">
                            {{ $incident->resolved_at ? $incident->resolved_at->format('d M H:i') : 'Pendiente' }}
                        </span>
                    </div>
                </div>

                <!-- Paso 5: Cerrada -->
                <div class="p-3 rounded-lg flex items-center gap-3 {{ $sOrder === 5 ? 'bg-surface-container-low border border-primary/30' : 'opacity-40' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $sOrder === 5 ? 'bg-primary/20 text-primary ring-2 ring-primary/40' : 'bg-surface-container-high text-outline' }}">
                        @if($sOrder === 5) <span class="material-symbols-outlined text-[18px]">verified</span> @else 5 @endif
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-semibold text-xs text-on-surface">5. Cerrada</span>
                        <span class="text-[10px] text-outline truncate font-mono">
                            {{ $incident->closed_at ? $incident->closed_at->format('d M H:i') : 'Pendiente' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Contenido Principal: Columnas de Detalle y Bitácora -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Columna Izquierda (8 cols): Descripción, Adjuntos, Bitácora -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                
                <!-- Solución Técnica Aplicada (Paso 23) -->
                @if($incident->solution_description)
                    <div class="bg-surface-subtle border border-emerald-500/40 p-6 rounded-xl shadow-sm flex flex-col gap-4">
                        <div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-border-subtle">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-emerald-400 flex items-center gap-2">
                                <span class="material-symbols-outlined text-emerald-400 text-[20px]">task_alt</span>
                                <span>Solución Técnica Certificada (GTS)</span>
                            </h2>
                            @if($incident->resolved_at)
                                <span class="px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-400 font-mono text-[11px] font-semibold border border-emerald-500/30">
                                    Resuelta: {{ $incident->resolved_at->format('d/m/Y H:i') }} hrs
                                </span>
                            @endif
                        </div>

                        <div class="text-xs sm:text-sm text-on-surface leading-relaxed whitespace-pre-line bg-surface-container-low p-4 rounded-xl border border-border-subtle font-sans">
                            {{ $incident->solution_description }}
                        </div>

                        @if($incident->status->code === 'RESUELTA')
                            <div class="p-3 bg-[#123A70]/25 rounded-lg border border-[#164A8A]/40 text-xs text-[#A8C4E6] flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px] text-[#3D82C6]">pending_actions</span>
                                <span>En espera de validación de servicio y visto bueno para proceder con el cierre formal del ticket.</span>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Descripción del Problema -->
                <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px]">description</span>
                        Descripción del Problema
                    </h2>
                    <div class="text-xs sm:text-sm text-on-surface leading-relaxed whitespace-pre-line bg-surface-container-low p-4 rounded-xl border border-border-subtle font-sans">
                        {{ $incident->description }}
                    </div>
                </div>

                <!-- Evidencias y Archivos Adjuntos (Fase 6 - Paso 27) -->
                <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between flex-wrap gap-2 pb-1 border-b border-border-subtle">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">attach_file</span>
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-on-surface">Evidencias y Archivos Adjuntos</h2>
                            <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-primary font-mono text-xs font-bold border border-border-subtle">
                                {{ $incident->attachments->count() }}
                            </span>
                        </div>

                        @can('uploadAttachment', $incident)
                            <button
                                type="button"
                                @click="showUploadModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-high hover:bg-surface-container text-primary hover:text-primary-fixed text-xs font-semibold rounded-xl border border-border-subtle transition-colors cursor-pointer"
                            >
                                <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
                                <span>Adjuntar Evidencia</span>
                            </button>
                        @endcan
                    </div>

                    @if($incident->attachments->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($incident->attachments as $att)
                                <div class="p-3.5 rounded-xl bg-surface-container-low border border-border-subtle hover:border-border-active transition-all flex flex-col justify-between gap-3 shadow-sm">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border {{ $att->file_badge_color }}">
                                            <span class="material-symbols-outlined text-[20px]">{{ $att->file_icon }}</span>
                                        </div>
                                        <div class="flex flex-col min-w-0 flex-1">
                                            <span class="font-mono text-xs font-semibold text-on-surface truncate" title="{{ $att->original_filename }}">
                                                {{ $att->original_filename }}
                                            </span>
                                            <div class="flex items-center gap-2 text-[11px] text-outline mt-0.5">
                                                <span class="font-mono">{{ $att->formatted_size }}</span>
                                                <span>•</span>
                                                <span>{{ $att->created_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                            <span class="text-[10px] text-on-surface-variant truncate mt-0.5">
                                                Por: {{ $att->uploadedBy?->name ?? 'Usuario' }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($att->is_image)
                                        <div class="rounded-lg overflow-hidden border border-border-subtle bg-surface-base max-h-36 flex items-center justify-center">
                                            <img
                                                src="{{ route('incidents.attachments.show', [$incident, $att]) }}"
                                                alt="{{ $att->original_filename }}"
                                                class="w-full h-auto object-cover max-h-36 hover:scale-105 transition-transform duration-200 cursor-pointer"
                                                onclick="window.open(this.src, '_blank')"
                                            />
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-border-subtle/50">
                                        <a
                                            href="{{ route('incidents.attachments.show', [$incident, $att]) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-on-surface-variant hover:text-primary bg-surface-container-high hover:bg-surface-container rounded-lg border border-border-subtle transition-colors"
                                            title="Visualizar en pestaña nueva"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                            <span>Ver</span>
                                        </a>
                                        <a
                                            href="{{ route('incidents.attachments.download', [$incident, $att]) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-on-primary bg-primary hover:bg-primary/90 rounded-lg shadow-sm transition-all"
                                            title="Descargar archivo físico"
                                        >
                                            <span class="material-symbols-outlined text-[14px]">download</span>
                                            <span>Descargar</span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 rounded-xl bg-surface-container-low border border-border-subtle/70 flex flex-col items-center justify-center text-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-surface-container-high text-outline flex items-center justify-center">
                                <span class="material-symbols-outlined text-[22px]">folder_open</span>
                            </div>
                            <span class="text-xs font-semibold text-on-surface">No hay evidencias adjuntas</span>
                            <p class="text-[11px] text-outline max-w-sm">
                                Este expediente no cuenta con capturas de error, bitácoras o archivos de respaldo.
                            </p>
                        </div>
                    @endif

                    @if($incident->status->code === 'CERRADA')
                        <div class="p-2.5 rounded-lg bg-slate-800/60 border border-slate-700 text-slate-400 text-[11px] flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px] text-slate-400">lock</span>
                            <span>Expediente archivado en modo inmutable. No se permite la carga de nuevas evidencias.</span>
                        </div>
                    @endif
                </div>

                <!-- Caja de Registro de Notas Técnicas de Campo (Paso 22 - Solo cuando está EN_ATENCION) -->
                @can('addNote', $incident)
                    <div class="bg-surface-subtle border border-amber-500/30 p-6 rounded-xl shadow-sm flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-amber-400 flex items-center gap-2">
                                <span class="material-symbols-outlined text-amber-400 text-[18px]">engineering</span>
                                Registrar Avance Técnico de Campo
                            </h2>
                            <span class="text-[11px] text-outline font-mono">GTS Operaciones</span>
                        </div>

                        <form method="POST" action="{{ route('incidents.add-note', $incident) }}" class="flex flex-col gap-3">
                            @csrf
                            <textarea
                                name="note"
                                rows="3"
                                required
                                minlength="5"
                                maxlength="1000"
                                placeholder="Describir pruebas en sitio, diagnóstico con multímetro/OTDR, sustitución de hardware (switch, PoE, cámara, cableado)..."
                                class="w-full px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-amber-400/50 transition-colors shadow-inner resize-none"
                            ></textarea>

                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <span class="text-[11px] text-outline">Mínimo 5 caracteres • Registro inmutable en bitácora</span>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-sm transition-all"
                                >
                                    <span class="material-symbols-outlined text-[16px]">post_add</span>
                                    <span>Registrar Avance</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan

                <!-- Bitácora de Auditoría Inmutable (Línea de Tiempo) -->
                <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[18px]">history</span>
                            <span>Bitácora de Trazabilidad y Auditoría (Append-Only)</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-surface-container-low text-outline font-mono text-xs">
                            {{ $incident->histories->count() }} evento(s)
                        </span>
                    </h2>

                    <div class="relative pl-6 border-l-2 border-border-subtle flex flex-col gap-5 my-2">
                        @foreach($incident->histories as $h)
                            <div class="relative flex flex-col gap-1">
                                <!-- Punto de la línea de tiempo -->
                                <div class="absolute -left-[31px] top-0.5 w-3 h-3 rounded-full bg-surface-base border-2
                                     @if($h->action === 'NOTA_TECNICA') border-amber-400 bg-amber-400/30
                                     @elseif($h->action === 'RESUELTA') border-emerald-400 bg-emerald-400/30
                                     @elseif($h->action === 'RECHAZO_SOLUCION') border-rose-500 bg-rose-500/30
                                     @elseif($h->action === 'CERRADA') border-primary bg-primary/30
                                     @elseif($h->action === 'CAMBIO_ESTADO') border-[#3D82C6]
                                     @else border-primary @endif">
                                </div>

                                <div class="flex items-center gap-2 flex-wrap text-xs">
                                    <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded uppercase
                                        @if($h->action === 'NOTA_TECNICA') bg-amber-500/15 text-amber-400 border border-amber-500/30
                                        @elseif($h->action === 'RESUELTA') bg-emerald-500/15 text-emerald-400 border border-emerald-500/30
                                        @elseif($h->action === 'RECHAZO_SOLUCION') bg-rose-500/15 text-rose-400 border border-rose-500/30
                                        @elseif($h->action === 'CERRADA') bg-primary/15 text-primary border border-primary/30
                                        @elseif($h->action === 'CAMBIO_ESTADO') bg-[rgba(61,130,198,0.15)] text-[#6FA2D2] border border-[rgba(61,130,198,0.30)]
                                        @elseif($h->action === 'ASIGNADA' || $h->action === 'REASIGNADA') bg-purple-500/15 text-purple-400 border border-purple-500/30
                                        @else bg-surface-container-high text-primary border border-border-subtle @endif">
                                        {{ $h->action }}
                                    </span>
                                    <span class="text-outline">•</span>
                                    <span class="font-medium text-on-surface">{{ $h->user ? $h->user->getTaggedName() : 'Sistema' }}</span>
                                    <span class="text-outline">•</span>
                                    <span class="font-mono text-[11px] text-outline">{{ $h->created_at->format('d M Y H:i:s') }}</span>
                                </div>

                                @if($h->action === 'NOTA_TECNICA')
                                    <div class="p-3.5 bg-amber-500/5 rounded-xl border border-amber-500/20 text-xs text-on-surface leading-relaxed mt-1">
                                        <div class="flex items-center gap-1.5 text-amber-400 font-semibold text-[11px] mb-1">
                                            <span class="material-symbols-outlined text-[15px]">engineering</span>
                                            <span>Avance / Diagnóstico Técnico:</span>
                                        </div>
                                        <p class="whitespace-pre-line text-on-surface-variant font-sans">{{ $h->comment }}</p>
                                    </div>
                                @elseif($h->action === 'RESUELTA')
                                    <div class="p-3.5 bg-emerald-500/5 rounded-xl border border-emerald-500/20 text-xs text-on-surface leading-relaxed mt-1">
                                        <div class="flex items-center gap-1.5 text-emerald-400 font-semibold text-[11px] mb-1">
                                            <span class="material-symbols-outlined text-[15px]">task_alt</span>
                                            <span>Solución Técnica Certificada por GTS:</span>
                                        </div>
                                        <p class="whitespace-pre-line text-on-surface-variant font-sans">{{ $h->comment }}</p>
                                    </div>
                                @elseif($h->action === 'RECHAZO_SOLUCION')
                                    <div class="p-3.5 bg-rose-500/5 rounded-xl border border-rose-500/20 text-xs text-on-surface leading-relaxed mt-1">
                                        <div class="flex items-center gap-1.5 text-rose-400 font-semibold text-[11px] mb-1">
                                            <span class="material-symbols-outlined text-[15px]">cancel</span>
                                            <span>Motivo de Rechazo por APOC:</span>
                                        </div>
                                        <p class="whitespace-pre-line text-on-surface-variant font-sans">{{ $h->comment }}</p>
                                    </div>
                                @elseif($h->action === 'CERRADA')
                                    <div class="p-3.5 bg-primary/5 rounded-xl border border-primary/20 text-xs text-on-surface leading-relaxed mt-1">
                                        <div class="flex items-center gap-1.5 text-primary font-semibold text-[11px] mb-1">
                                            <span class="material-symbols-outlined text-[15px]">verified</span>
                                            <span>Conformidad Operacional y Cierre Formal ({{ $h->user ? $h->user->getRoleTag() : 'VoBo' }}):</span>
                                        </div>
                                        <p class="whitespace-pre-line text-on-surface-variant font-sans">{{ $h->comment }}</p>
                                    </div>
                                @elseif($h->comment)
                                    <p class="text-xs text-on-surface-variant bg-surface-container-low p-2.5 rounded-lg border border-border-subtle mt-1 font-sans">
                                        {{ $h->comment }}
                                    </p>
                                @endif

                                @if($h->field_name)
                                    <div class="text-[11px] text-outline font-mono flex items-center gap-1.5 mt-0.5">
                                        <span>{{ $h->field_name }}:</span>
                                        <span class="line-through text-error/70">{{ $h->old_value ?? 'ninguno' }}</span>
                                        <span class="material-symbols-outlined text-[12px]">arrow_forward</span>
                                        <span class="text-status-resolved font-medium">{{ $h->new_value ?? 'ninguno' }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Columna Derecha (4 cols): Ficha Técnica y Responsable -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                
                <!-- Tarjeta Especialista Asignado -->
                <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-2 pb-2 border-b border-border-subtle">
                        <span class="material-symbols-outlined text-primary text-[18px]">badge</span>
                        Responsable Técnico
                    </h2>

                    {{-- Áreas Asignadas --}}
                    @if($incident->assignedAreas->isNotEmpty())
                        <div class="flex flex-col gap-1.5 pb-2 border-b border-border-subtle/60">
                            <span class="text-[10px] uppercase font-bold text-outline tracking-wider">Área(s) Responsable(s)</span>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @foreach($incident->assignedAreas as $area)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-mono font-medium border
                                        @if($area->area_code === 'tics') bg-blue-500/10 text-blue-400 border-blue-500/20
                                        @elseif($area->role) {{ $area->role->badge_bg ?? 'bg-surface-container-high' }} {{ $area->role->badge_text ?? 'text-primary' }} {{ $area->role->badge_border ?? 'border-border-subtle' }}
                                        @else bg-surface-container-high text-on-surface border-border-subtle @endif">
                                        <span class="material-symbols-outlined text-[13px]">domain</span>
                                        <span>{{ $area->area_code === 'tics' ? 'TICS' : ($area->role?->name ?? strtoupper($area->area_code)) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Especialista Individual o Estado de Asignación --}}
                    @if($incident->assignedTo)
                        <div class="flex items-center gap-3 bg-surface-container-low p-3.5 rounded-xl border border-border-subtle">
                            <div class="w-10 h-10 rounded-full bg-surface-container-high border border-primary/30 flex items-center justify-center font-mono font-bold text-primary text-sm">
                                {{ substr($incident->assignedTo->name, 0, 2) }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-semibold text-xs text-on-surface truncate">{{ $incident->assignedTo->name }}</span>
                                <span class="text-[11px] text-outline font-mono">{{ $incident->assignedTo->email }}</span>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="text-[10px] text-outline font-mono">
                                        {{ $incident->assignedTo->role?->name ?? 'GTS' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @elseif($incident->isAssignedToTics())
                        <div class="flex items-center gap-3 bg-surface-container-low p-3.5 rounded-xl border border-border-subtle">
                            <div class="w-10 h-10 rounded-full bg-surface-container-high border border-primary/30 flex items-center justify-center font-mono font-bold text-primary text-sm">
                                TI
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-semibold text-xs text-on-surface truncate">TICS</span>
                                <span class="text-[11px] text-outline font-mono">Especialista de Atención</span>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="text-[10px] text-primary font-mono bg-primary/10 px-1.5 py-0.5 rounded border border-primary/20">
                                        Área TICS
                                    </span>
                                </div>
                            </div>
                        </div>
                    @elseif($incident->assignedAreas->isNotEmpty())
                        <div class="flex items-center gap-3 bg-surface-container-low p-3.5 rounded-xl border border-border-subtle">
                            <div class="w-10 h-10 rounded-full bg-surface-container-high border border-border-subtle flex items-center justify-center font-mono font-bold text-outline text-sm">
                                <span class="material-symbols-outlined text-[20px]">groups</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-semibold text-xs text-on-surface">Asignación a Nivel de Área</span>
                                <span class="text-[11px] text-outline">Sin especialista individual asignado</span>
                                <span class="text-[10px] text-primary mt-0.5">Atención abierta para personal autorizado</span>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center gap-2 p-5 bg-surface-container-low rounded-xl border border-border-subtle text-center">
                            <span class="material-symbols-outlined text-outline text-[28px]">person_off</span>
                            <span class="text-xs font-semibold text-outline">Sin Especialista Asignado</span>
                            <p class="text-[11px] text-outline max-w-[200px]">
                                La incidencia se encuentra en espera de toma técnica por GTS.
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Ficha Técnica de la Incidencia -->
                <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-2 pb-2 border-b border-border-subtle">
                        <span class="material-symbols-outlined text-primary text-[18px]">info</span>
                        Ficha Técnica Operativa
                    </h2>

                    <dl class="flex flex-col gap-3 text-xs">
                        <div class="flex justify-between py-1.5 border-b border-border-subtle/50">
                            <dt class="text-outline">Subsistema:</dt>
                            <dd class="text-on-surface font-semibold">{{ $incident->category->name }}</dd>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-border-subtle/50">
                            <dt class="text-outline">Ubicación:</dt>
                            <dd class="text-on-surface font-semibold text-right max-w-[180px]">{{ $incident->location }}</dd>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-border-subtle/50">
                            <dt class="text-outline">Reportado Por:</dt>
                            <dd class="text-on-surface font-semibold flex items-center gap-1.5">
                                <span>{{ $incident->reportedBy?->name ?? 'Sistema' }}</span>
                                @if($incident->isFromCco())
                                    <span class="px-1.5 py-0.2 rounded font-mono text-[9.5px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">CCO</span>
                                @elseif($incident->isFromCsa())
                                    <span class="px-1.5 py-0.2 rounded font-mono text-[9.5px] font-bold bg-orange-500/10 text-orange-400 border border-orange-500/20">CSA</span>
                                @elseif($incident->origin_group)
                                    <span class="px-1.5 py-0.2 rounded font-mono text-[9.5px] font-bold bg-slate-500/10 text-slate-400 border border-slate-500/20">{{ $incident->origin_group }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-border-subtle/50">
                            <dt class="text-outline">Procedencia:</dt>
                            <dd class="text-on-surface font-semibold font-mono text-xs">
                                @if($incident->isFromCco())
                                    <span class="text-amber-400 font-bold">APOC-CCO</span>
                                @elseif($incident->isFromCsa())
                                    <span class="text-orange-400 font-bold">APOC-CSA</span>
                                @else
                                    <span>{{ $incident->origin_group ?? 'GTS' }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-border-subtle/50">
                            <dt class="text-outline">Fecha Reporte:</dt>
                            <dd class="text-on-surface font-mono">{{ $incident->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-border-subtle/50">
                            <dt class="text-outline">Fecha Asignación:</dt>
                            <dd class="text-on-surface font-mono">
                                {{ $incident->assigned_at ? $incident->assigned_at->format('Y-m-d H:i:s') : 'Pendiente' }}
                            </dd>
                        </div>
                        @if($incident->resolved_at)
                            <div class="flex justify-between py-1.5 border-b border-border-subtle/50">
                                <dt class="text-outline">Fecha Resolución:</dt>
                                <dd class="text-emerald-400 font-mono">{{ $incident->resolved_at->format('Y-m-d H:i:s') }}</dd>
                            </div>
                        @endif
                        @if($incident->closed_at)
                            <div class="flex justify-between py-1.5 border-b border-border-subtle/50">
                                <dt class="text-outline">Fecha Cierre:</dt>
                                <dd class="text-primary font-mono font-semibold">{{ $incident->closed_at->format('Y-m-d H:i:s') }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-2 py-1.5">
                            <dt class="text-outline">Tiempo de respuesta estimado:</dt>
                            <dd class="text-primary font-mono font-semibold shrink-0">{{ $incident->priority->sla_minutes }} minutos</dd>
                        </div>
                    </dl>
                </div>

            </div>

        </div>

        <!-- 4. Modal de Asignación / Reasignación de Especialista y Áreas Responsables -->
        @can('assign', $incident)
        @php
            $creatorSlug = strtolower($incident->reportedBy?->role?->slug ?? '');
            $initialAreas = array_values(array_filter(
                array_map('strtolower', $incident->assignedAreaCodes()), 
                fn($c) => $c !== 'tics' && $c !== 'admin' && ($creatorSlug === '' || $c !== $creatorSlug)
            ));
            if (empty($initialAreas)) {
                if ($incident->assignedTo && $incident->assignedTo->role && $incident->assignedTo->role->slug !== 'admin' && $incident->assignedTo->role_id !== $incident->reportedBy?->role_id) {
                    $initialAreas = [$incident->assignedTo->role->slug];
                }
            }
            $initialUserId = ($incident->assigned_to_user_id && ! $incident->assignedTo?->isAdmin() && $incident->assignedTo?->role_id !== $incident->reportedBy?->role_id) 
                ? (string) $incident->assigned_to_user_id 
                : '';
            $availableAreasList = ($availableAreas ?? collect())->values()->all();
            $availableTechniciansList = ($availableTechnicians ?? collect())->map(function($tech) {
                return [
                    'id' => (string) $tech->id,
                    'name' => $tech->name,
                    'badge' => $tech->badge_number ?? $tech->getRoleTag(),
                    'role_slug' => strtolower($tech->role?->slug ?? ''),
                    'role_name' => $tech->role?->name ?? 'GTS',
                ];
            })->values()->all();
        @endphp
        <div
            x-show="showAssignModal"
            x-cloak
            x-data="{
                allAreas: {{ Js::from($availableAreasList) }},
                allTechnicians: {{ Js::from($availableTechniciansList) }},
                selectedAreas: {{ Js::from($initialAreas) }},
                selectedUserId: '{{ $initialUserId }}',
                areaToAdd: '',
                isSubmitting: false,
                errorMessage: '',

                get filteredTechnicians() {
                    if (this.selectedAreas.length === 0) return [];
                    const selectedSlugs = this.selectedAreas.map(a => a.toLowerCase());
                    return this.allTechnicians.filter(t => selectedSlugs.includes(t.role_slug));
                },

                get unselectedAreas() {
                    const selectedSlugs = this.selectedAreas.map(a => a.toLowerCase());
                    return this.allAreas.filter(a => !selectedSlugs.includes(a.code.toLowerCase()));
                },

                addArea(code) {
                    if (!code) return;
                    const cleanCode = code.toLowerCase();
                    if (!this.selectedAreas.map(a => a.toLowerCase()).includes(cleanCode)) {
                        this.selectedAreas.push(cleanCode);
                    }
                    this.areaToAdd = '';
                    this.errorMessage = '';
                },

                removeArea(code) {
                    const cleanCode = code.toLowerCase();
                    this.selectedAreas = this.selectedAreas.filter(a => a.toLowerCase() !== cleanCode);
                    this.validateSelectedUser();
                    this.errorMessage = '';
                },

                validateSelectedUser() {
                    if (this.selectedUserId) {
                        const isValid = this.filteredTechnicians.some(t => String(t.id) === String(this.selectedUserId));
                        if (!isValid) {
                            this.selectedUserId = '';
                        }
                    }
                },

                getAreaMeta(code) {
                    const clean = code.toLowerCase();
                    return this.allAreas.find(a => a.code.toLowerCase() === clean) || {
                        code: code,
                        name: code.toUpperCase(),
                        badge_bg: 'bg-surface-container-high',
                        badge_text: 'text-on-surface',
                        badge_border: 'border-border-subtle'
                    };
                },

                async submitAssignment() {
                    if (this.selectedAreas.length === 0 || this.isSubmitting) return;
                    this.isSubmitting = true;
                    this.errorMessage = '';

                    try {
                        const form = this.$refs.assignForm;
                        const formData = new FormData(form);

                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            const data = await response.json().catch(() => null);
                            if (response.status === 422 && data?.errors) {
                                const errorMessages = Object.values(data.errors).flat();
                                this.errorMessage = errorMessages[0] || 'Error de validación al asignar la incidencia.';
                            } else {
                                this.errorMessage = data?.message || 'Ocurrió un error al procesar la asignación.';
                            }
                            return;
                        }

                        // Éxito: recargar la vista para reflejar el estado táctico actualizado
                        window.location.reload();
                    } catch (error) {
                        this.errorMessage = 'Error de conectividad al enviar la solicitud. Por favor intente nuevamente.';
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-base/80 backdrop-blur-sm"
            @keydown.escape.window="showAssignModal = false"
        >
            <div
                class="bg-surface-subtle border border-border-subtle w-full max-w-xl rounded-2xl shadow-2xl p-6 flex flex-col gap-5"
                @click.outside="showAssignModal = false"
            >
                <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-primary text-[24px]">device_hub</span>
                        <div>
                            <h3 class="text-base font-bold text-on-surface">
                                {{ ($incident->assignedTo || $incident->assignedAreas->isNotEmpty()) ? 'Reasignar Especialista y Áreas' : 'Asignar Especialista y Áreas' }}
                            </h3>
                            <p class="text-[11px] text-outline">Gestione las áreas responsables y asigne opcionalmente un especialista técnico.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showAssignModal = false"
                        class="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form x-ref="assignForm" method="POST" action="{{ route('incidents.assign', $incident) }}" class="flex flex-col gap-4" @submit.prevent="submitAssignment()">
                    @csrf

                    <!-- Alerta de Error en caso de falla de validación o servidor -->
                    <div x-show="errorMessage" x-cloak class="p-3 rounded-xl bg-status-critical/10 border border-status-critical/30 text-status-critical text-xs flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
                        <span x-text="errorMessage"></span>
                    </div>

                    <!-- Inputs Ocultos de Áreas Seleccionadas -->
                    <template x-for="code in selectedAreas" :key="code">
                        <input type="hidden" name="areas[]" :value="code">
                    </template>

                    <!-- SELECTOR 1: Área Responsable (Selección Múltiple) -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-semibold text-on-surface flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-primary">domain</span>
                                <span>1. Área Responsable <span class="text-status-critical">*</span></span>
                            </span>
                            <span class="text-[11px] text-outline font-normal">
                                Seleccionadas: <strong class="text-primary font-mono" x-text="selectedAreas.length"></strong>
                            </span>
                        </label>

                        <!-- Contenedor de Chips / Badges de Áreas Seleccionadas -->
                        <div class="min-h-[46px] p-2.5 bg-surface-container-low border border-border-subtle rounded-xl flex flex-wrap items-center gap-2">
                            <template x-for="code in selectedAreas" :key="code">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border transition-all"
                                    :class="getAreaMeta(code).badge_bg + ' ' + getAreaMeta(code).badge_text + ' ' + getAreaMeta(code).badge_border"
                                >
                                    <span class="font-mono text-[11px]" x-text="getAreaMeta(code).name"></span>
                                    <button
                                        type="button"
                                        @click="removeArea(code)"
                                        title="Retirar área"
                                        class="hover:opacity-75 transition-opacity ml-1 p-0.5 rounded-full hover:bg-black/20 text-current flex items-center justify-center shrink-0"
                                    >
                                        <span class="material-symbols-outlined text-[13px] leading-none">close</span>
                                    </button>
                                </span>
                            </template>

                            <template x-if="selectedAreas.length === 0">
                                <span class="text-outline text-xs italic flex items-center gap-1.5 px-1">
                                    <span class="material-symbols-outlined text-[16px]">info</span>
                                    Ninguna área seleccionada. Elija al menos un área responsable.
                                </span>
                            </template>
                        </div>

                        <!-- Selector para agregar nuevas áreas autorizadas -->
                        <div class="flex items-center gap-2 mt-0.5">
                            <div class="relative flex-1">
                                <select
                                    x-model="areaToAdd"
                                    @change="addArea(areaToAdd)"
                                    class="w-full bg-none appearance-none px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface focus:outline-none focus:border-border-focus transition-colors pr-9 cursor-pointer shadow-inner disabled:opacity-60 disabled:cursor-not-allowed"
                                    :disabled="unselectedAreas.length === 0"
                                >
                                    <option value="" x-text="unselectedAreas.length === 0 ? 'Todas las áreas autorizadas ya han sido agregadas' : '-- Seleccionar área para agregar --'"></option>
                                    <template x-for="area in unselectedAreas" :key="area.code">
                                        <option :value="area.code" x-text="area.name"></option>
                                    </template>
                                </select>
                                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none select-none">
                                    expand_more
                                </span>
                            </div>
                        </div>

                        <!-- Estado vacío si el usuario no tiene áreas permitidas -->
                        <template x-if="allAreas.length === 0">
                            <p class="text-[11px] text-status-critical flex items-center gap-1 mt-1">
                                <span class="material-symbols-outlined text-[14px]">warning</span>
                                No tiene destinos de asignación autorizados según su rol y alcances operativos.
                            </p>
                        </template>
                    </div>

                    <!-- SELECTOR 2: Usuario Responsable (Opcional) -->
                    <div class="flex flex-col gap-1.5 pt-1">
                        <label for="modal_assigned_to_user_id" class="text-xs font-semibold text-on-surface flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-primary">person</span>
                                <span>2. Usuario Responsable <span class="text-outline font-normal">(Opcional)</span></span>
                            </span>
                            <span class="text-[11px] text-outline font-normal">
                                Compatibles: <strong class="text-primary font-mono" x-text="filteredTechnicians.length"></strong>
                            </span>
                        </label>

                        <div class="relative">
                            <select
                                id="modal_assigned_to_user_id"
                                name="assigned_to_user_id"
                                x-model="selectedUserId"
                                :disabled="filteredTechnicians.length === 0 || selectedAreas.length === 0"
                                class="w-full bg-none appearance-none px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface focus:outline-none focus:border-border-focus transition-colors pr-9 cursor-pointer shadow-inner disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <option value="" x-text="
                                    selectedAreas.length === 0 
                                        ? '-- Primero seleccione al menos un área responsable --' 
                                        : (filteredTechnicians.length === 0 
                                            ? '-- Las áreas seleccionadas no cuentan con especialistas individuales --' 
                                            : '-- Sin usuario asignado (asignar solo a nivel de área) --')
                                "></option>

                                <template x-for="tech in filteredTechnicians" :key="tech.id">
                                    <option :value="tech.id" x-text="'[' + tech.role_name + '] ' + tech.name + ' (' + tech.badge + ')'"></option>
                                </template>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none select-none">
                                expand_more
                            </span>
                        </div>

                        <p class="text-[11px] text-outline" x-show="selectedAreas.length > 0 && filteredTechnicians.length === 0">
                            * Las áreas seleccionadas operarán sin asignación individual de técnico.
                        </p>
                    </div>

                    <!-- Comentario u Observación -->
                    <div class="flex flex-col gap-1.5">
                        <label for="modal_comment" class="text-xs font-semibold text-on-surface">
                            Observaciones de Asignación <span class="text-outline font-normal">(Opcional)</span>
                        </label>
                        <textarea
                            id="modal_comment"
                            name="comment"
                            rows="2"
                            placeholder="Motivo de la asignación, instrucciones operativas a pista o consola..."
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-border-focus transition-colors shadow-inner resize-none"
                        ></textarea>
                    </div>

                    <!-- Botones de Acción Modal -->
                    <div class="flex items-center justify-between pt-2 border-t border-border-subtle">
                        <div class="text-[11px] text-outline">
                            <span x-show="selectedAreas.length === 0" class="text-status-critical flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>
                                Seleccione al menos un área.
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="showAssignModal = false"
                                class="px-4 py-2 bg-surface-container-high hover:bg-surface-container text-on-surface font-semibold text-xs rounded-xl border border-border-subtle transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="selectedAreas.length === 0 || isSubmitting"
                                class="px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="material-symbols-outlined text-[16px]">how_to_reg</span>
                                <span x-text="isSubmitting ? 'Asignando...' : 'Confirmar Asignación'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <!-- 5. Modal de Resolución Técnica de Incidencia (Paso 23) -->
        @can('resolve', $incident)
        <div
            x-show="showResolveModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-base/80 backdrop-blur-sm"
            @keydown.escape.window="showResolveModal = false"
        >
            <div
                class="bg-surface-subtle border border-border-subtle w-full max-w-lg rounded-2xl shadow-2xl p-6 flex flex-col gap-5"
                @click.outside="showResolveModal = false"
            >
                <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-400 text-[22px]">task_alt</span>
                        <h3 class="text-base font-bold text-on-surface">
                            Resolución Técnica de Incidencia
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="showResolveModal = false"
                        class="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('incidents.resolve', $incident) }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="p-3 rounded-xl bg-surface-container-low border border-border-subtle flex items-center justify-between text-xs">
                        <span class="text-outline">Folio:</span>
                        <span class="font-mono font-bold text-primary">{{ $incident->folio }}</span>
                    </div>

                    <!-- Descripción de la Solución Técnica Aplicada -->
                    <div class="flex flex-col gap-1.5">
                        <label for="modal_solution_description" class="text-xs font-semibold text-on-surface">
                            Descripción Técnica de la Solución Aplicada <span class="text-status-critical">*</span>
                        </label>
                        <textarea
                            id="modal_solution_description"
                            name="solution_description"
                            rows="5"
                            required
                            minlength="15"
                            maxlength="3000"
                            placeholder="Detallar las acciones técnicas ejecutadas: componentes sustituidos (marca, modelo, serial), reconfiguración de puertos/VLANs, pruebas de calibración o telemetría en consola, y confirmación de estabilidad..."
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-emerald-400/50 transition-colors shadow-inner resize-none"
                        ></textarea>
                        <span class="text-[11px] text-outline">
                            Mínimo 15 caracteres obligatorios. La solución quedará registrada de forma inmutable en el expediente de auditoría.
                        </span>
                    </div>

                    <!-- Botones de Acción Modal -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showResolveModal = false"
                            class="px-4 py-2 bg-surface-container-high hover:bg-surface-container text-on-surface font-semibold text-xs rounded-xl border border-border-subtle transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            <span>Confirmar y Marcar como Resuelta</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <!-- 6. Modal de Rechazo de Solución por APOC (Paso 24) -->
        @can('rejectResolution', $incident)
        <div
            x-show="showRejectModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-base/80 backdrop-blur-sm"
            @keydown.escape.window="showRejectModal = false"
        >
            <div
                class="bg-surface-subtle border border-border-subtle w-full max-w-lg rounded-2xl shadow-2xl p-6 flex flex-col gap-5"
                @click.outside="showRejectModal = false"
            >
                <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-rose-400 text-[22px]">replay</span>
                        <h3 class="text-base font-bold text-on-surface">
                            Rechazo de Solución Técnica (APOC)
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="showRejectModal = false"
                        class="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('incidents.reject-resolution', $incident) }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="p-3 rounded-xl bg-surface-container-low border border-border-subtle flex items-center justify-between text-xs">
                        <span class="text-outline">Folio de Expediente:</span>
                        <span class="font-mono font-bold text-primary">{{ $incident->folio }}</span>
                    </div>

                    <!-- Motivo Obligatorio de Rechazo -->
                    <div class="flex flex-col gap-1.5">
                        <label for="modal_rejection_reason" class="text-xs font-semibold text-on-surface">
                            Motivo / Hallazgos de Rechazo <span class="text-status-critical">*</span>
                        </label>
                        <textarea
                            id="modal_rejection_reason"
                            name="rejection_reason"
                            rows="4"
                            required
                            minlength="10"
                            maxlength="1000"
                            placeholder="Explicar detalladamente por qué no se acepta la solución: pruebas en sitio fallidas, persistencia de intermitencia, cámaras aún sin telemetría, errores en e-Gates..."
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-rose-400/50 transition-colors shadow-inner resize-none"
                        ></textarea>
                        <span class="text-[11px] text-outline">
                            Mínimo 10 caracteres obligatorios. Este reporte devolverá el ticket inmediatamente al estado EN ATENCIÓN para nuevo diagnóstico técnico por GTS.
                        </span>
                    </div>

                    <!-- Botones de Acción Modal -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showRejectModal = false"
                            class="px-4 py-2 bg-surface-container-high hover:bg-surface-container text-on-surface font-semibold text-xs rounded-xl border border-border-subtle transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-[18px]">replay</span>
                            <span>Confirmar Rechazo y Reabrir Atención</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <!-- 7. Modal de Visto Bueno y Cierre Definitivo (Paso 25) -->
        @can('close', $incident)
        <div
            x-show="showCloseModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-base/80 backdrop-blur-sm"
            @keydown.escape.window="showCloseModal = false"
        >
            <div
                class="bg-surface-subtle border border-border-subtle w-full max-w-lg rounded-2xl shadow-2xl p-6 flex flex-col gap-5"
                @click.outside="showCloseModal = false"
            >
                <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">verified</span>
                        <h3 class="text-base font-bold text-on-surface">
                            Conformidad y Cierre de Incidencia
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="showCloseModal = false"
                        class="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('incidents.close', $incident) }}" class="flex flex-col gap-4">
                    @csrf

                    <div class="p-3.5 rounded-xl bg-surface-container-low border border-border-subtle flex flex-col gap-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-outline">Folio de Expediente:</span>
                            <span class="font-mono font-bold text-primary text-sm">{{ $incident->folio }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-outline">Especialista:</span>
                            <span class="font-semibold text-on-surface">{{ $incident->isAssignedToTics() ? 'TICS' : ($incident->assignedTo?->name ?? 'GTS de Guardia') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-outline">Subsistema:</span>
                            <span class="text-on-surface">{{ $incident->category->name }}</span>
                        </div>
                    </div>

                    <div class="p-3 bg-primary/10 rounded-xl border border-primary/20 text-xs text-primary-200 flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-[18px] text-primary shrink-0 mt-0.5">info</span>
                        <p class="leading-relaxed text-[11px] text-on-surface-variant">
                            Al confirmar, el expediente pasará al estado final inmutable <strong class="text-on-surface">CERRADA</strong>. Se archivará la solución técnica documentada y ningún usuario podrá reactivar ni alterar este registro.
                        </p>
                    </div>

                    <!-- Observaciones de Conformidad / Cierre -->
                    <div class="flex flex-col gap-1.5">
                        <label for="modal_close_comment" class="text-xs font-semibold text-on-surface">
                            Observaciones de Conformidad y Servicio <span class="text-outline font-normal">(Opcional)</span>
                        </label>
                        <textarea
                            id="modal_close_comment"
                            name="comment"
                            rows="3"
                            maxlength="1000"
                            placeholder="Detalles de la validación: servicio verificado en sala/pista, pruebas de pasajeros exitosas, visto bueno otorgado..."
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-primary/50 transition-colors shadow-inner resize-none"
                        ></textarea>
                    </div>

                    <!-- Botones de Acción Modal -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showCloseModal = false"
                            class="px-4 py-2 bg-surface-container-high hover:bg-surface-container text-on-surface font-semibold text-xs rounded-xl border border-border-subtle transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2.5 bg-primary hover:bg-primary/90 text-on-primary font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5"
                        >
                            <span class="material-symbols-outlined text-[18px]">verified</span>
                            <span>Confirmar Cierre y Conformidad</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <!-- Modal: Adjuntar Nuevas Evidencias (Paso 27) -->
        @can('uploadAttachment', $incident)
        <div
            x-show="showUploadModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
            @keydown.escape.window="showUploadModal = false"
        >
            <div
                class="bg-surface-subtle border border-border-subtle rounded-2xl w-full max-w-lg p-6 shadow-2xl flex flex-col gap-5 text-on-surface"
                @click.outside="showUploadModal = false"
            >
                <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-[20px]">cloud_upload</span>
                        </div>
                        <div class="flex flex-col">
                            <h3 class="text-sm font-bold text-on-surface">Adjuntar Nuevas Evidencias</h3>
                            <span class="text-[11px] text-outline">Expediente {{ $incident->folio }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showUploadModal = false"
                        class="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('incidents.attachments.store', $incident) }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                    @csrf

                    <div class="p-3 bg-surface-container-low rounded-xl border border-border-subtle text-xs text-on-surface-variant flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-[18px] text-primary shrink-0 mt-0.5">info</span>
                        <p class="leading-relaxed text-[11px]">
                            Los archivos se resguardarán en almacenamiento privado no accesible públicamente. Formatos permitidos: <strong class="text-on-surface">JPG, PNG, PDF, TXT</strong> (máx. 10 MB por archivo, hasta 5 archivos).
                        </p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="modal_attachments" class="text-xs font-semibold text-on-surface">
                            Seleccionar Archivos de Respaldo <span class="text-status-critical">*</span>
                        </label>
                        <input
                            type="file"
                            id="modal_attachments"
                            name="attachments[]"
                            multiple
                            required
                            accept=".jpg,.jpeg,.png,.pdf,.txt"
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-surface-container-high file:text-primary hover:file:bg-surface-container cursor-pointer shadow-inner"
                        />
                    </div>

                    <!-- Botones de Acción Modal -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showUploadModal = false"
                            class="px-4 py-2 bg-surface-container-high hover:bg-surface-container text-on-surface font-semibold text-xs rounded-xl border border-border-subtle transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2.5 bg-primary hover:bg-primary/90 text-on-primary font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
                            <span>Subir y Resguardar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endcan

    </div>
</x-app-layout>