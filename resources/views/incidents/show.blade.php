<x-app-layout>
    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6" x-data="{ showAssignModal: false }">
        
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
                <div class="p-3 rounded-lg flex items-center gap-3 {{ $sOrder === 5 ? 'bg-surface-container-low border border-border-subtle' : 'opacity-40' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $sOrder === 5 ? 'bg-slate-500/20 text-slate-300' : 'bg-surface-container-high text-outline' }}">
                        @if($sOrder === 5) <span class="material-symbols-outlined text-[18px]">task_alt</span> @else 5 @endif
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
                                <div class="absolute -left-[31px] top-0.5 w-3 h-3 rounded-full bg-surface-base border-2 border-primary"></div>

                                <div class="flex items-center gap-2 flex-wrap text-xs">
                                    <span class="font-mono font-bold text-primary">{{ $h->action }}</span>
                                    <span class="text-outline">•</span>
                                    <span class="font-medium text-on-surface">{{ $h->user?->name ?? 'Sistema' }}</span>
                                    <span class="text-outline">•</span>
                                    <span class="font-mono text-[11px] text-outline">{{ $h->created_at->format('d M Y H:i:s') }}</span>
                                </div>

                                @if($h->comment)
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

    </div>
</x-app-layout>