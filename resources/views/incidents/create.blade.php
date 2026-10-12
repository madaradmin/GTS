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
                <span class="text-xs text-on-surface uppercase tracking-wider font-semibold">{{ auth()->user()->isGts() ? 'Canal GTS Activo' : (auth()->user()->isApocCco() ? 'Canal APOC-CCO Activo' : (auth()->user()->isApocCsa() ? 'Canal APOC-CSA Activo' : (auth()->user()->isApoc() ? 'Canal APOC Activo' : (auth()->user()->isAdmin() ? 'Canal Administrador Activo' : 'Canal ' . (auth()->user()->role?->name ?? 'Usuario') . ' Activo')))) }}</span>
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

        <form action="{{ route('incidents.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6" id="incidentForm" autocomplete="off">
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
                                autocomplete="off"
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
                                        autocomplete="off"
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
                            <label class="text-xs font-medium text-on-surface flex items-center justify-between flex-wrap gap-2">
                                <span>Prioridad Sugerida <span class="text-status-critical">*</span></span>
                                <span class="text-outline text-xs">Determina el tiempo de respuesta estimado</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2.5">
                                @foreach ($priorities as $priority)
                                    @php
                                        $slug = strtolower($priority->slug);
                                        $isChecked = old('priority_id') == $priority->id || (!old('priority_id') && $slug === 'media');
                                        $colorClasses = match($slug) {
                                            'baja' => 'text-status-neutral',
                                            'media' => 'text-[#10B981]',
                                            'alta' => 'text-status-warning',
                                            'critica' => 'text-status-critical',
                                            default => 'text-primary',
                                        };
                                        $dotBg = match($slug) {
                                            'baja' => 'bg-status-neutral',
                                            'media' => 'bg-[#10B981]',
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
                                            Tiempo de respuesta estimado: {{ $priority->sla_minutes }} minutos
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
                    <section 
                        x-data="attachmentUploader()" 
                        class="bg-surface-subtle border border-border-subtle p-6 rounded-xl shadow-sm flex flex-col gap-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[22px]">attach_file</span>
                                <h2 class="text-base font-semibold text-on-surface">Evidencia o Adjuntos</h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <span x-show="files.length > 0" x-cloak class="text-xs px-2.5 py-0.5 rounded bg-primary/20 text-primary font-mono font-medium border border-primary/30">
                                    <span x-text="files.length"></span> / 5 seleccionados
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant border border-border-subtle">Opcional</span>
                            </div>
                        </div>

                        <!-- Dropzone interactivo con soporte de clic y arrastre (Drag & Drop) -->
                        <div 
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop($event)"
                            @click="$refs.fileInput.click()"
                            :class="isDragging ? 'border-primary bg-primary/10 ring-2 ring-primary/40' : 'border-border-subtle hover:border-border-active bg-surface-container-low hover:bg-surface-container'"
                            class="relative border rounded-xl p-6 sm:p-8 transition-all flex flex-col items-center justify-center text-center cursor-pointer shadow-inner group"
                        >
                            <input 
                                type="file" 
                                x-ref="fileInput"
                                id="attachments"
                                name="attachments[]" 
                                multiple 
                                accept=".png,.jpg,.jpeg,.pdf,.txt" 
                                @change="handleFileSelect($event)"
                                class="sr-only"
                            />

                            <div class="w-12 h-12 rounded-xl bg-surface-container-high border border-border-active flex items-center justify-center text-primary mb-3 group-hover:scale-110 transition-transform shadow-sm pointer-events-none">
                                <span class="material-symbols-outlined text-[26px]">cloud_upload</span>
                            </div>
                            <p class="text-sm font-medium text-on-surface pointer-events-none">
                                Arrastra y suelta capturas de error, bitácoras o documentos
                            </p>
                            <p class="text-xs text-outline mt-1 pointer-events-none">
                                Formatos admitidos: PNG, JPG, PDF o TXT (Máximo 10 MB por archivo, hasta 5 archivos)
                            </p>
                            <button 
                                type="button" 
                                class="mt-3 px-3.5 py-1.5 bg-surface-card border border-border-subtle rounded-lg text-primary text-xs font-semibold group-hover:bg-primary group-hover:text-on-primary transition-colors pointer-events-none"
                            >
                                Explorar archivos locales
                            </button>
                        </div>

                        <!-- Alerta inline para validaciones de tamaño, formato o límite en frontend -->
                        <div x-show="errorMessage" x-cloak class="p-3 rounded-xl bg-error-container/20 border border-error/30 text-error text-xs flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] shrink-0">error</span>
                            <span x-text="errorMessage"></span>
                        </div>

                        <!-- Lista visual de archivos seleccionados en tiempo real -->
                        <template x-if="files.length > 0">
                            <div class="flex flex-col gap-2 pt-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-outline">
                                        Evidencias preparadas (<span x-text="files.length"></span>)
                                    </span>
                                    <button 
                                        type="button" 
                                        @click="clearAll()" 
                                        class="text-xs text-outline hover:text-error transition-colors"
                                    >
                                        Quitar todas
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <template x-for="(file, index) in files" :key="index + '-' + file.name">
                                        <div class="p-3 rounded-xl bg-surface-container-low border border-border-subtle flex items-center justify-between gap-3 shadow-sm hover:border-border-active transition-colors">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 border" :class="getFileBadgeClass(file)">
                                                    <span class="material-symbols-outlined text-[20px]" x-text="getFileIcon(file)"></span>
                                                </div>
                                                <div class="flex flex-col min-w-0">
                                                    <span class="text-xs font-mono font-medium text-on-surface truncate" x-text="file.name" :title="file.name"></span>
                                                    <span class="text-[11px] font-mono text-outline" x-text="formatBytes(file.size)"></span>
                                                </div>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click.stop="removeFile(index)" 
                                                class="p-1.5 rounded-lg text-outline hover:text-error hover:bg-error-container/20 transition-colors shrink-0"
                                                title="Quitar archivo"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
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
                                <span class="text-xs uppercase tracking-wider text-outline font-mono">Despacho Automático: GTS</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                                    <span class="text-xs text-on-surface font-medium">GTS</span>
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
                            <span>Soporte 55-57-98-98-00 Ext. 13202</span>
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
                    setTimeout(() => {
                        btnSubmit.disabled = true;
                    }, 0);
                    btnSubmit.classList.add('opacity-75', 'cursor-not-allowed', 'pointer-events-none');
                    btnSubmit.innerHTML = `
                        <span class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                        <span>Despachando a GTS...</span>
                    `;
                });
            }
        });

        function attachmentUploader() {
            return {
                files: [],
                isDragging: false,
                errorMessage: '',

                handleFileSelect(event) {
                    this.addFiles(event.target.files);
                },

                handleDrop(event) {
                    this.isDragging = false;
                    if (event.dataTransfer && event.dataTransfer.files) {
                        this.addFiles(event.dataTransfer.files);
                    }
                },

                addFiles(newFileList) {
                    this.errorMessage = '';
                    const maxFiles = 5;
                    const maxBytes = 10 * 1024 * 1024; // 10 MB
                    const allowedExtensions = ['png', 'jpg', 'jpeg', 'pdf', 'txt'];

                    const incoming = Array.from(newFileList);

                    for (const file of incoming) {
                        if (this.files.length >= maxFiles) {
                            this.errorMessage = `Límite alcanzado: solo se admiten hasta ${maxFiles} archivos de evidencia simultáneamente.`;
                            break;
                        }

                        const ext = file.name.split('.').pop().toLowerCase();
                        if (!allowedExtensions.includes(ext)) {
                            this.errorMessage = `El archivo "${file.name}" no es válido. Solo se admiten formatos JPG, PNG, PDF o TXT.`;
                            continue;
                        }

                        if (file.size > maxBytes) {
                            this.errorMessage = `El archivo "${file.name}" excede los 10 MB máximos permitidos.`;
                            continue;
                        }

                        const exists = this.files.some(f => f.name === file.name && f.size === file.size);
                        if (!exists) {
                            this.files.push(file);
                        }
                    }

                    this.syncInput();
                },

                removeFile(index) {
                    this.files.splice(index, 1);
                    this.syncInput();
                    this.errorMessage = '';
                },

                clearAll() {
                    this.files = [];
                    this.syncInput();
                    this.errorMessage = '';
                },

                syncInput() {
                    const input = this.$refs.fileInput;
                    if (!input) return;

                    const dt = new DataTransfer();
                    for (const file of this.files) {
                        dt.items.add(file);
                    }
                    input.files = dt.files;
                },

                formatBytes(bytes) {
                    if (bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
                },

                getFileIcon(file) {
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (['png', 'jpg', 'jpeg'].includes(ext)) return 'image';
                    if (ext === 'pdf') return 'picture_as_pdf';
                    if (ext === 'txt') return 'description';
                    return 'attach_file';
                },

                getFileBadgeClass(file) {
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (['png', 'jpg', 'jpeg'].includes(ext)) return 'text-cyan-400 bg-cyan-500/10 border-cyan-500/30';
                    if (ext === 'pdf') return 'text-rose-400 bg-rose-500/10 border-rose-500/30';
                    return 'text-amber-400 bg-amber-500/10 border-amber-500/30';
                }
            };
        }
    </script>
</x-app-layout>
