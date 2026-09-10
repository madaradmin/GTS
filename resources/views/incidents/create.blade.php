<x-app-layout>
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Encabezado de Página -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs uppercase tracking-widest text-primary font-semibold">Módulo de Despacho Operativo</span>
                    <span class="text-outline text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-on-surface-variant font-medium">GTS AIFA</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-on-surface tracking-tight">Nueva Incidencia</h1>
                <p class="text-sm text-on-surface-variant mt-1 max-w-2xl">
                    Registro de reporte técnico para asignación y seguimiento inmediato del Grupo de Seguridad Tecnológica (GTS).
                </p>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto bg-surface-container-low px-4 py-2 rounded-xl border border-border-subtle shadow-sm">
                <span class="w-2.5 h-2.5 rounded-full bg-status-resolved animate-pulse"></span>
                <span class="text-xs text-on-surface uppercase tracking-wider font-semibold">Canal APOC Activo</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-error-container/20 border border-error/30 text-error">
                <div class="flex items-center gap-2 font-semibold text-sm mb-1">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span>Se detectaron errores en el formulario:</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('incidents.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6" id="incidentForm">
            @csrf

            <!-- Grid Principal de Formulario -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Columna Izquierda / Central: Bloques de Captura -->
                <div class="lg:col-span-8 flex flex-col gap-6">
                    <!-- Bloque 1: Información de la Incidencia -->
                    <section class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-5">
                        <div class="flex items-center gap-2 pb-1">
                            <span class="material-symbols-outlined text-primary text-[22px]">info</span>
                            <h2 class="text-base font-semibold text-on-surface">Información de la Incidencia</h2>
                        </div>

                        <!-- Asunto / Título -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-on-surface flex items-center justify-between" for="title">
                                <span>Título o Asunto del Problema <span class="text-status-critical">*</span></span>
                                <span class="text-outline text-xs">Sintético y descriptivo</span>
                            </label>
                            <input
                                class="w-full px-4 py-2.5 bg-surface-container-low border border-border-subtle rounded-lg text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-border-focus transition-colors shadow-inner"
                                id="title"
                                name="title"
                                value="{{ old('title') }}"
                                placeholder="ej. Falla en lectura de tarjeta de acceso Puerta 14B"
                                required
                                type="text"
                            />
                        </div>

                        <!-- Fila Categoría y Ubicación -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Categoría / Subsistema -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-on-surface" for="category_id">
                                    Categoría / Subsistema <span class="text-status-critical">*</span>
                                </label>
                                <div class="relative">
                                    <select
                                        class="w-full appearance-none px-4 py-2.5 bg-surface-container-low border border-border-subtle rounded-lg text-sm text-on-surface focus:outline-none focus:border-border-focus transition-colors pr-10 cursor-pointer"
                                        id="category_id"
                                        name="category_id"
                                        required
                                    >
                                        <option disabled {{ old('category_id') ? '' : 'selected' }} value="">Seleccionar subsistema...</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-outline text-[20px]">
                                        expand_more
                                    </span>
                                </div>
                            </div>

                            <!-- Ubicación o Área afectada -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-on-surface" for="location">
                                    Ubicación / Área Afectada <span class="text-status-critical">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        class="w-full px-4 py-2.5 bg-surface-container-low border border-border-subtle rounded-lg text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-border-focus transition-colors pr-9"
                                        id="location"
                                        name="location"
                                        value="{{ old('location') }}"
                                        placeholder="ej. Terminal 1 - Nivel 2, Puerta 14B"
                                        required
                                        type="text"
                                    />
                                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">
                                        location_on
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Prioridad Sugerida (Botones Radio Segmentados) -->
                        <div class="flex flex-col gap-2 pt-1">
                            <label class="text-xs font-medium text-on-surface flex items-center justify-between">
                                <span>Prioridad Sugerida <span class="text-status-critical">*</span></span>
                                <span class="text-outline text-xs">Determina el SLA y tiempo de respuesta</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2.5">
                                @foreach ($priorities as $priority)
                                    @php
                                        $slug = strtolower($priority->slug);
                                        $isChecked = old('priority_id') == $priority->id || (!old('priority_id') && $slug === 'media');
                                        $colorClasses = match($slug) {
                                            'baja' => 'text-status-neutral',
                                            'media' => 'text-secondary',
                                            'alta' => 'text-status-warning',
                                            'critica' => 'text-status-critical',
                                            default => 'text-primary',
                                        };
                                        $dotBg = match($slug) {
                                            'baja' => 'bg-status-neutral',
                                            'media' => 'bg-secondary',
                                            'alta' => 'bg-status-warning',
                                            'critica' => 'bg-status-critical',
                                            default => 'bg-primary',
                                        };
                                    @endphp
                                    <label class="group relative flex flex-col p-3 rounded-lg bg-surface-container-low border border-border-subtle hover:border-border-active transition-all cursor-pointer has-[:checked]:bg-surface-overlay has-[:checked]:border-primary/50 shadow-sm">
                                        <input class="sr-only peer" name="priority_id" type="radio" value="{{ $priority->id }}" {{ $isChecked ? 'checked' : '' }} />
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-xs font-semibold {{ $colorClasses }} flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full {{ $dotBg }} inline-block {{ $slug === 'critica' ? 'animate-ping' : '' }}"></span>
                                                {{ $priority->name }}
                                            </span>
                                            <span class="material-symbols-outlined text-[16px] text-primary opacity-0 peer-checked:opacity-100 transition-opacity">check_circle</span>
                                        </div>
                                        <span class="text-xs text-on-surface-variant leading-snug">
                                            SLA objetivo: {{ $priority->sla_minutes }} minutos
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <!-- Bloque 2: Descripción del Evento -->
                    <section class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[22px]">subject</span>
                                <h2 class="text-base font-semibold text-on-surface">Descripción del Evento</h2>
                            </div>
                            <span class="text-xs text-outline font-mono" id="charCounter">0 / 3000</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <textarea
                                class="w-full p-4 bg-surface-container-low border border-border-subtle rounded-lg text-sm text-on-surface placeholder:text-outline focus:outline-none focus:border-border-focus transition-colors shadow-inner resize-y leading-relaxed"
                                id="incident_description"
                                name="description"
                                placeholder="Describa detalladamente el síntoma observado, mensajes de error o comportamiento anómalo en el equipo..."
                                required
                                rows="5"
                            >{{ old('description') }}</textarea>
                            <span class="text-xs text-outline px-1">Incluya números de serie de dispositivos o códigos en pantalla si están disponibles.</span>
                        </div>
                    </section>

                    <!-- Bloque 3: Evidencia o Adjuntos -->
                    <section class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[22px]">attach_file</span>
                                <h2 class="text-base font-semibold text-on-surface">Evidencia o Adjuntos</h2>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant border border-border-subtle">Opcional</span>
                        </div>
                        <!-- Dropzone sin bordes punteados clásicos, profundidad tonal -->
                        <div class="relative bg-surface-container-low hover:bg-surface-container border border-border-subtle hover:border-border-active rounded-xl p-8 transition-colors flex flex-col items-center justify-center text-center group cursor-pointer shadow-inner">
                            <input accept=".png,.jpg,.jpeg,.pdf,.txt" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" multiple name="attachments[]" type="file"/>
                            <div class="w-12 h-12 rounded-xl bg-surface-container-high border border-border-active flex items-center justify-center text-primary mb-3 group-hover:scale-110 transition-transform shadow-sm">
                                <span class="material-symbols-outlined text-[26px]">cloud_upload</span>
                            </div>
                            <p class="text-sm font-medium text-on-surface">
                                Arrastra y suelta capturas de error, bitácoras o documentos
                            </p>
                            <p class="text-xs text-outline mt-1">
                                Formatos admitidos: PNG, JPG, PDF o TXT (Máximo 10 MB por archivo)
                            </p>
                            <span class="mt-3 px-3 py-1 bg-surface-card border border-border-subtle rounded text-primary text-xs group-hover:bg-primary group-hover:text-on-primary transition-colors">
                                Explorar archivos locales
                            </span>
                        </div>
                    </section>
                </div>

                <!-- Columna Derecha: Bloque 4 (Datos de Control) y Resumen de Operación -->
                <div class="lg:col-span-4 flex flex-col gap-6">
                    <!-- Bloque 4: Datos de Control Automáticos -->
                    <div class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4">
                        <div class="flex items-center gap-2 pb-1">
                            <span class="material-symbols-outlined text-primary text-[20px]">admin_panel_settings</span>
                            <h3 class="text-base font-semibold text-on-surface">Datos de Control</h3>
                        </div>
                        <p class="text-xs text-on-surface-variant -mt-2">
                            Valores inmutables de auditoría generados por el motor de telemetría aeroportuaria.
                        </p>
                        <div class="flex flex-col gap-3 pt-2">
                            <!-- Folio Preliminar -->
                            <div class="flex flex-col p-3 rounded-lg bg-surface-container-low border border-border-subtle shadow-sm">
                                <span class="text-xs uppercase tracking-wider text-outline font-mono">Folio Preliminar Asignado</span>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="font-mono text-base font-bold text-primary tracking-wider">{{ $preliminaryFolio }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-surface-overlay text-on-surface-variant font-mono border border-border-subtle">AUTOGÉN</span>
                                </div>
                            </div>
                            <!-- Fecha y Hora de Captura -->
                            <div class="flex flex-col p-3 rounded-lg bg-surface-container-low border border-border-subtle shadow-sm">
                                <span class="text-xs uppercase tracking-wider text-outline font-mono">Fecha / Hora de Registro</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="material-symbols-outlined text-outline text-[16px]">schedule</span>
                                    <span class="text-xs text-on-surface font-medium">{{ now()->format('Y-m-d • H:i:s') }} hrs</span>
                                </div>
                            </div>
                            <!-- Usuario que Reporta -->
                            <div class="flex flex-col p-3 rounded-lg bg-surface-container-low border border-border-subtle shadow-sm">
                                <span class="text-xs uppercase tracking-wider text-outline font-mono">Operador Responsable</span>
                                <div class="flex items-center gap-2.5 mt-2">
                                    <div class="w-8 h-8 rounded-full bg-surface-container-high border border-border-active flex items-center justify-center text-primary font-semibold flex-shrink-0">
                                        <span class="material-symbols-outlined text-[18px]">terminal</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-semibold text-on-surface truncate">{{ auth()->user()->name }}</span>
                                        <span class="text-xs text-on-surface-variant truncate">{{ auth()->user()->role?->name ?? 'APOC' }}</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Canal de Notificación -->
                            <div class="flex flex-col p-3 rounded-lg bg-surface-container-low border border-border-subtle shadow-sm">
                                <span class="text-xs uppercase tracking-wider text-outline font-mono">Despacho Automático</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                                    <span class="text-xs text-on-surface font-medium">Mesa GTS Guardia 2 (C4 & Pistas)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta de Ayuda / Protocolo AIFA -->
                    <div class="bg-surface-card border border-border-subtle p-5 rounded-xl shadow-sm flex flex-col gap-2.5">
                        <div class="flex items-center gap-2 text-primary">
                            <span class="material-symbols-outlined text-[18px]">verified_user</span>
                            <span class="text-xs font-semibold tracking-wide uppercase font-mono">Protocolo GTS AIFA</span>
                        </div>
                        <p class="text-xs text-on-surface-variant leading-relaxed">
                            Una vez remitida, la incidencia se encola de forma síncrona en el tablero de radiodespacho y se genera la bitácora criptográfica de trazabilidad en base de datos.
                        </p>
                        <div class="flex items-center gap-2 pt-1 text-outline text-xs">
                            <span class="material-symbols-outlined text-[14px]">call</span>
                            <span>Mesa de Soporte: Ext. 4209 / 4210</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción Principales (Inferior Completo) -->
            <div class="sticky bottom-4 z-30 bg-surface-subtle/95 backdrop-blur-md p-4 sm:p-5 rounded-xl border border-border-subtle shadow-xl flex flex-col-reverse sm:flex-row items-center justify-between gap-3 mt-2">
                <div class="flex items-center gap-2 text-outline text-xs">
                    <span class="material-symbols-outlined text-[18px]">lock</span>
                    <span>Transmisión protegida por TLS 1.3 interna AIFA</span>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <button
                        class="flex-1 sm:flex-initial px-5 py-2.5 rounded-lg bg-surface-container-high border border-border-subtle hover:bg-surface-overlay text-on-surface text-sm font-medium transition-colors flex items-center justify-center gap-2"
                        onclick="history.back()"
                        type="button"
                    >
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        <span>Cancelar</span>
                    </button>
                    <button
                        class="flex-1 sm:flex-initial px-6 py-2.5 rounded-lg bg-primary-container hover:bg-primary text-on-primary-container hover:text-on-primary text-sm font-semibold transition-all shadow-md flex items-center justify-center gap-2 cursor-pointer"
                        id="btnSubmit"
                        type="submit"
                    >
                        <span class="material-symbols-outlined text-[18px]">send</span>
                        <span>Crear Incidencia</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const textarea = document.getElementById('incident_description');
            const counter = document.getElementById('charCounter');
            if (textarea && counter) {
                const updateCounter = () => {
                    const len = textarea.value.length;
                    counter.textContent = `${len} / 3000`;
                    if (len > 2800) {
                        counter.classList.add('text-status-warning');
                    } else {
                        counter.classList.remove('text-status-warning');
                    }
                };
                textarea.addEventListener('input', updateCounter);
                updateCounter();
            }

            const incidentForm = document.getElementById('incidentForm');
            const btnSubmit = document.getElementById('btnSubmit');
            if (incidentForm && btnSubmit) {
                incidentForm.addEventListener('submit', () => {
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('opacity-75', 'cursor-not-allowed');
                    btnSubmit.innerHTML = `
                        <span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        <span>Despachando a GTS...</span>
                    `;
                });
            }
        });
    </script>
</x-app-layout>

