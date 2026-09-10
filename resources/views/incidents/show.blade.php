<x-app-layout>
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Notificación Flash -->
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-status-resolved/10 border border-status-resolved/30 text-status-resolved flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2 font-medium text-sm">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Cabecera de Incidencia -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-6 border-b border-border-subtle">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-3">
                    <span class="font-mono text-xl sm:text-2xl font-bold text-primary tracking-wide">
                        {{ $incident->folio }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider
                        @if($incident->status->code === 'NUEVA') bg-status-new/15 text-status-new border border-status-new/30
                        @elseif($incident->status->code === 'ASIGNADA') bg-status-assigned/15 text-status-assigned border border-status-assigned/30
                        @elseif($incident->status->code === 'EN_ATENCION') bg-status-in_progress/15 text-status-in_progress border border-status-in_progress/30
                        @elseif($incident->status->code === 'RESUELTA') bg-status-resolved/15 text-status-resolved border border-status-resolved/30
                        @else bg-surface-container-high text-on-surface-variant border border-border-subtle @endif">
                        {{ $incident->status->name }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider
                        @if($incident->priority->slug === 'critica') bg-status-critical/15 text-status-critical border border-status-critical/30
                        @elseif($incident->priority->slug === 'alta') bg-amber-500/15 text-amber-400 border border-amber-500/30
                        @elseif($incident->priority->slug === 'media') bg-blue-500/15 text-blue-400 border border-blue-500/30
                        @else bg-slate-500/15 text-slate-400 border border-slate-500/30 @endif">
                        {{ $incident->priority->name }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-on-surface mt-1">{{ $incident->title }}</h1>
            </div>
            <div class="flex items-center gap-2">
                @can('create', App\Models\Incident::class)
                    <a href="{{ route('incidents.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary text-xs font-semibold rounded-lg transition-colors shadow-sm">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Nueva Incidencia
                    </a>
                @endcan
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 flex flex-col gap-6">
                <!-- Tarjeta Detalle -->
                <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">description</span>
                        Descripción del Problema
                    </h2>
                    <p class="text-sm text-on-surface leading-relaxed whitespace-pre-line bg-surface-container-low p-4 rounded-lg border border-border-subtle">
                        {{ $incident->description }}
                    </p>
                </div>

                <!-- Adjuntos -->
                @if($incident->attachments->count() > 0)
                    <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">attach_file</span>
                            Archivos Adjuntos ({{ $incident->attachments->count() }})
                        </h2>
                        <ul class="divide-y divide-border-subtle border border-border-subtle rounded-lg overflow-hidden bg-surface-container-low">
                            @foreach($incident->attachments as $att)
                                <li class="p-3 flex items-center justify-between text-xs text-on-surface hover:bg-surface-container-high transition-colors">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-outline text-[18px]">draft</span>
                                        <span class="font-mono text-on-surface font-medium">{{ $att->original_filename }}</span>
                                    </div>
                                    <span class="text-on-surface-variant font-mono">{{ number_format($att->file_size_bytes / 1024, 1) }} KB</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Columna Lateral: Metadatos -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-on-surface-variant flex items-center gap-2 pb-2 border-b border-border-subtle">
                        <span class="material-symbols-outlined text-primary text-[20px]">info</span>
                        Ficha Técnica
                    </h2>
                    <dl class="flex flex-col gap-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-border-subtle/50">
                            <dt class="text-on-surface-variant">Subsistema / Categoría:</dt>
                            <dd class="text-on-surface font-semibold">{{ $incident->category->name }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-border-subtle/50">
                            <dt class="text-on-surface-variant">Ubicación:</dt>
                            <dd class="text-on-surface font-semibold">{{ $incident->location }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-border-subtle/50">
                            <dt class="text-on-surface-variant">Reportado Por:</dt>
                            <dd class="text-on-surface font-semibold">{{ $incident->reportedBy->name }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-border-subtle/50">
                            <dt class="text-on-surface-variant">Fecha y Hora:</dt>
                            <dd class="text-on-surface font-mono">{{ $incident->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        <div class="flex justify-between py-1">
                            <dt class="text-on-surface-variant">Asignado a:</dt>
                            <dd class="text-on-surface font-semibold">
                                {{ $incident->assignedTo ? $incident->assignedTo->name : 'Sin asignar (Pendiente GTS)' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
