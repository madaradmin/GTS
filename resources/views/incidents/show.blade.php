<x-app-layout>
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6" x-data="{ showAssignModal: false, showResolveModal: false, showRejectModal: false, showCloseModal: false }">
        
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
                        @if($incident->status->code === 'NUEVA') bg-blue-500/15 text-blue-400 border border-blue-500/30
                        @elseif($incident->status->code === 'ASIGNADA') bg-purple-500/15 text-purple-400 border border-purple-500/30
                        @elseif($incident->status->code === 'EN_ATENCION') bg-amber-500/15 text-amber-400 border border-amber-500/30
                        @elseif($incident->status->code === 'RESUELTA') bg-emerald-500/15 text-emerald-400 border border-emerald-500/30
                        @else bg-slate-500/15 text-slate-400 border border-slate-500/30 @endif">
                        @if($incident->status->code === 'EN_ATENCION')
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        @endif
                        {{ $incident->status->name }}
                    </span>

                    <!-- Badge de Prioridad -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-semibold text-xs uppercase tracking-wider
                        @if($incident->priority->slug === 'critica') bg-status-critical/15 text-status-critical border border-status-critical/30
                        @elseif($incident->priority->slug === 'alta') bg-amber-500/15 text-amber-400 border border-amber-500/30
                        @elseif($incident->priority->slug === 'media') bg-blue-500/15 text-blue-400 border border-blue-500/30
                        @else bg-slate-500/15 text-slate-400 border border-slate-500/30 @endif">
                        @if($incident->priority->slug === 'critica')
                            <span class="w-2 h-2 rounded-full bg-status-critical animate-pulse"></span>
                        @endif
                        {{ $incident->priority->name }}
                    </span>

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
                @can('assign', $incident)
                    @if(is_null($incident->assigned_to_user_id) && auth()->user()->isGts())
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

                    <!-- Botón para Abrir Modal de Asignación / Reasignación -->
                    <button
                        type="button"
                        @click="showAssignModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-surface-container-high hover:bg-surface-container text-primary border border-border-subtle font-semibold text-xs rounded-xl shadow-sm transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                        <span>{{ $incident->assignedTo ? 'Reasignar Especialista' : 'Asignar Especialista' }}</span>
                    </button>
                @endcan

                <!-- Botón: Iniciar Atención Técnica (Paso 22) -->
                @can('startAttention', $incident)
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

                <!-- Botón: Marcar como Resuelta (Paso 23) -->
                @can('resolve', $incident)
                    <button
                        type="button"
                        @click="showResolveModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">task_alt</span>
                        <span>Marcar como Resuelta</span>
                    </button>
                @endcan

                <!-- Botón: Rechazar Solución (Paso 24 - APOC) -->
                @can('rejectResolution', $incident)
                    <button
                        type="button"
                        @click="showRejectModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/40 font-bold text-xs rounded-xl shadow-sm transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">replay</span>
                        <span>Rechazar Solución</span>
                    </button>
                @endcan

                <!-- Botón: Dar Visto Bueno y Cerrar (Paso 25 - APOC) -->
                @can('close', $incident)
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
            <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-700/60 text-slate-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md">
                <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold">
                    <span class="material-symbols-outlined text-slate-400 text-[22px]">lock</span>
                    <span>Expediente Cerrado y Archivado — Modo de Solo Lectura Inmutable</span>
                    <span class="text-outline font-normal hidden md:inline">• Visto Bueno otorgado por APOC</span>
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
                    <span class="text-outline font-normal hidden md:inline">• Responsable: {{ $incident->assignedTo?->name }}</span>
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
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $sOrder > 1 ? 'bg-status-resolved/20 text-status-resolved' : ($sOrder === 1 ? 'bg-blue-500/20 text-blue-400 ring-2 ring-blue-500/30' : 'bg-surface-container-high text-outline') }}">
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
                            <div class="p-3 bg-blue-500/10 rounded-lg border border-blue-500/20 text-xs text-blue-300 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px] text-blue-400">pending_actions</span>
                                <span>En espera de validación de servicio y visto bueno por el Centro de Operaciones (APOC) para proceder con el cierre formal del ticket.</span>
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

                <!-- Evidencias y Archivos Adjuntos -->
                @if($incident->attachments->count() > 0)
                    <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-on-surface-variant flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[18px]">attach_file</span>
                                <span>Evidencias Adjuntas</span>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-surface-container-low text-primary font-mono text-xs">
                                {{ $incident->attachments->count() }} archivo(s)
                            </span>
                        </h2>
                        <ul class="divide-y divide-border-subtle border border-border-subtle rounded-xl overflow-hidden bg-surface-container-low">
                            @foreach($incident->attachments as $att)
                                <li class="p-3.5 flex items-center justify-between text-xs text-on-surface hover:bg-surface-container-high transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-surface-container-high border border-border-subtle flex items-center justify-center text-primary">
                                            <span class="material-symbols-outlined text-[18px]">
                                                @if(str_contains($att->mime_type, 'image')) image
                                                @elseif(str_contains($att->mime_type, 'pdf')) picture_as_pdf
                                                @else draft @endif
                                            </span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-mono text-on-surface font-medium">{{ $att->original_filename }}</span>
                                            <span class="text-[11px] text-outline">
                                                Subido por {{ $att->uploadedBy?->name ?? 'Usuario' }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-outline font-mono text-xs">{{ number_format($att->file_size_bytes / 1024, 1) }} KB</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
                                     @elseif($h->action === 'CAMBIO_ESTADO') border-blue-400
                                     @else border-primary @endif">
                                </div>

                                <div class="flex items-center gap-2 flex-wrap text-xs">
                                    <span class="font-mono font-bold text-[11px] px-2 py-0.5 rounded uppercase
                                        @if($h->action === 'NOTA_TECNICA') bg-amber-500/15 text-amber-400 border border-amber-500/30
                                        @elseif($h->action === 'RESUELTA') bg-emerald-500/15 text-emerald-400 border border-emerald-500/30
                                        @elseif($h->action === 'RECHAZO_SOLUCION') bg-rose-500/15 text-rose-400 border border-rose-500/30
                                        @elseif($h->action === 'CERRADA') bg-primary/15 text-primary border border-primary/30
                                        @elseif($h->action === 'CAMBIO_ESTADO') bg-blue-500/15 text-blue-400 border border-blue-500/30
                                        @elseif($h->action === 'ASIGNADA' || $h->action === 'REASIGNADA') bg-purple-500/15 text-purple-400 border border-purple-500/30
                                        @else bg-surface-container-high text-primary border border-border-subtle @endif">
                                        {{ $h->action }}
                                    </span>
                                    <span class="text-outline">•</span>
                                    <span class="font-medium text-on-surface">{{ $h->user?->name ?? 'Sistema' }}</span>
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
                                            <span>Conformidad Operacional y Cierre Formal (APOC):</span>
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
                        Responsable Técnico GTS
                    </h2>

                    @if($incident->assignedTo)
                        <div class="flex items-center gap-3 bg-surface-container-low p-3.5 rounded-xl border border-border-subtle">
                            <div class="w-10 h-10 rounded-full bg-surface-container-high border border-primary/30 flex items-center justify-center font-mono font-bold text-primary text-sm">
                                {{ substr($incident->assignedTo->name, 0, 2) }}
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-semibold text-xs text-on-surface truncate">{{ $incident->assignedTo->name }}</span>
                                <span class="text-[11px] text-outline font-mono">{{ $incident->assignedTo->email }}</span>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-status-resolved"></span>
                                    <span class="text-[10px] text-status-resolved font-medium">Especialista de Guardia</span>
                                </div>
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
                            <dd class="text-on-surface font-semibold">{{ $incident->reportedBy->name }} (APOC)</dd>
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
                        <div class="flex justify-between py-1.5">
                            <dt class="text-outline">SLA Estimado:</dt>
                            <dd class="text-primary font-mono font-semibold">{{ $incident->priority->sla_minutes }} minutos</dd>
                        </div>
                    </dl>
                </div>

            </div>

        </div>

        <!-- 4. Modal de Asignación / Reasignación de Especialista -->
        <div
            x-show="showAssignModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-surface-base/80 backdrop-blur-sm"
            @keydown.escape.window="showAssignModal = false"
        >
            <div
                class="bg-surface-subtle border border-border-subtle w-full max-w-md rounded-2xl shadow-2xl p-6 flex flex-col gap-5"
                @click.outside="showAssignModal = false"
            >
                <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">sync_alt</span>
                        <h3 class="text-base font-bold text-on-surface">
                            {{ $incident->assignedTo ? 'Reasignar Especialista GTS' : 'Asignar Especialista GTS' }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="showAssignModal = false"
                        class="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('incidents.assign', $incident) }}" class="flex flex-col gap-4">
                    @csrf

                    <!-- Selector de Técnico -->
                    <div class="flex flex-col gap-1.5">
                        <label for="modal_assigned_to_user_id" class="text-xs font-semibold text-on-surface">
                            Seleccionar Especialista Técnico GTS <span class="text-status-critical">*</span>
                        </label>
                        <div class="relative">
                            <select
                                id="modal_assigned_to_user_id"
                                name="assigned_to_user_id"
                                required
                                class="w-full appearance-none px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface focus:outline-none focus:border-border-focus transition-colors pr-9 cursor-pointer shadow-inner"
                            >
                                <option value="" disabled {{ $incident->assigned_to_user_id ? '' : 'selected' }}>
                                    Seleccionar técnico disponible...
                                </option>
                                @foreach($gtsUsers as $tech)
                                    <option value="{{ $tech->id }}" {{ $incident->assigned_to_user_id == $tech->id ? 'selected' : '' }}>
                                        {{ $tech->name }} ({{ $tech->badge_number ?? 'GTS' }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">
                                expand_more
                            </span>
                        </div>
                    </div>

                    <!-- Comentario u Observación -->
                    <div class="flex flex-col gap-1.5">
                        <label for="modal_comment" class="text-xs font-semibold text-on-surface">
                            Observaciones de Asignación <span class="text-outline font-normal">(Opcional)</span>
                        </label>
                        <textarea
                            id="modal_comment"
                            name="comment"
                            rows="3"
                            placeholder="Motivo de la asignación, instrucciones de despacho a pista o consola..."
                            class="w-full px-3.5 py-2.5 bg-surface-container-low border border-border-subtle rounded-xl text-xs text-on-surface placeholder:text-outline focus:outline-none focus:border-border-focus transition-colors shadow-inner resize-none"
                        ></textarea>
                    </div>

                    <!-- Botones de Acción Modal -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showAssignModal = false"
                            class="px-4 py-2 bg-surface-container-high hover:bg-surface-container text-on-surface font-semibold text-xs rounded-xl border border-border-subtle transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary font-bold text-xs rounded-xl shadow-sm transition-all"
                        >
                            Confirmar Asignación
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 5. Modal de Resolución Técnica de Incidencia (Paso 23) -->
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

        <!-- 6. Modal de Rechazo de Solución por APOC (Paso 24) -->
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

        <!-- 7. Modal de Visto Bueno y Cierre Definitivo por APOC (Paso 25) -->
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
                            <span class="text-outline">Especialista GTS:</span>
                            <span class="font-semibold text-on-surface">{{ $incident->assignedTo?->name ?? 'GTS de Guardia' }}</span>
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
                            placeholder="Detalles de la validación: servicio verificado en sala/pista, pruebas de pasajeros exitosas, visto bueno otorgado por APOC..."
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

    </div>
</x-app-layout>