<x-app-layout>
    <div 
        x-data="{ 
            showEmailModal: false, 
            showWhatsAppModal: false,
            emailRecipient: '{{ auth()->user()->email }}',
            emailNotes: '',
            whatsAppPhone: '',
            copiedWhatsApp: false,
            copyPreview() {
                const text = $refs.whatsAppText.innerText;
                navigator.clipboard.writeText(text);
                this.copiedWhatsApp = true;
                setTimeout(() => this.copiedWhatsApp = false, 2500);
            }
        }" 
        class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col gap-6"
    >

        <!-- Alertas de Éxito / Feedback de Operación -->
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

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs sm:text-sm flex flex-col gap-1">
                <div class="flex items-center gap-2 font-bold text-rose-400">
                    <span class="material-symbols-outlined text-[18px]">error</span>
                    <span>Por favor corrija los siguientes errores:</span>
                </div>
                <ul class="list-disc list-inside pl-2 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Encabezado y Contexto Operacional -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs uppercase tracking-widest text-[#3D82C6] font-semibold">Gestión Documental Operativa</span>
                    <span class="text-[#707B8A] text-sm">/</span>
                    <span class="text-xs uppercase tracking-widest text-[#8D98A8] font-medium">AIFA & GTS</span>
                </div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#F2F5F8] tracking-tight">Sistema Profesional de Reportes</h1>
                    <span class="px-2.5 py-0.5 rounded bg-[#101923] text-[#3D82C6] font-mono text-xs font-semibold uppercase tracking-wider border border-[#263241] flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[14px]">summarize</span>
                        <span>Centro Ejecutivo</span>
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-[#8D98A8] mt-1">
                    Generación de informes técnicos de turno, exportación institucional en PDF y despacho multicanal para APOC y GTS.
                </p>
            </div>

            <!-- Acciones Principales de Distribución (Sección 4) -->
            <div class="flex flex-wrap items-center gap-2.5 self-start md:self-auto">
                <!-- 1. Botón Generar PDF Institucional -->
                <a 
                    href="{{ route('reports.pdf', request()->query()) }}" 
                    target="_blank"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-[#3D82C6] hover:bg-[#316cb0] text-white font-semibold text-xs rounded-xl shadow-md transition-all group"
                >
                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                    <span>Generar PDF Institucional</span>
                </a>

                <!-- 2. Botón Enviar por Correo -->
                <button 
                    type="button" 
                    @click="showEmailModal = true"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-[#11151B] hover:bg-[#151B23] text-[#DDE3EA] border border-[#263241] font-semibold text-xs rounded-xl transition-all"
                >
                    <span class="material-symbols-outlined text-[18px] text-[#3D82C6]">mail</span>
                    <span>Enviar por Correo</span>
                </button>

                <!-- 3. Botón Compartir por WhatsApp -->
                <button 
                    type="button" 
                    @click="showWhatsAppModal = true"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-[#11151B] hover:bg-[#151B23] text-[#DDE3EA] border border-[#263241] font-semibold text-xs rounded-xl transition-all"
                >
                    <span class="material-symbols-outlined text-[18px] text-[#20C997]">chat</span>
                    <span>WhatsApp</span>
                </button>
            </div>
        </div>

        <!-- SECCIÓN 1: TIPO DE REPORTE (PRESETS RÁPIDOS) -->
        <div class="bg-[#11151B] border border-[#263241] p-4 rounded-2xl shadow-sm flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs uppercase tracking-wider text-[#8D98A8] font-bold flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">tune</span>
                    <span>Sección 1 — Presets y Tipos de Reporte</span>
                </span>
                @if(request()->hasAny(['period_preset', 'date_from', 'date_to', 'status_id', 'priority_id', 'category_id', 'assigned_to', 'reported_by', 'search', 'report_type']))
                    <a href="{{ route('reports.index') }}" class="text-[11px] text-[#707B8A] hover:text-[#3D82C6] underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">refresh</span>
                        <span>Restablecer filtros</span>
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- General -->
                <a 
                    href="{{ route('reports.index') }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all {{ (!request()->has('period_preset') && !request()->has('report_type') && !request()->has('date_from')) ? 'bg-[#3D82C6]/20 border-[#3D82C6] text-[#F2F5F8] font-semibold' : 'bg-[#0B0F14] border-[#263241] text-[#A8B0BC] hover:border-[#3D82C6]' }}"
                >
                    🌐 General Completo
                </a>

                <!-- Hoy (Turno) -->
                <a 
                    href="{{ route('reports.index', array_merge(request()->except(['date_from', 'date_to']), ['period_preset' => 'today'])) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all {{ request()->get('period_preset') === 'today' ? 'bg-[#3D82C6]/20 border-[#3D82C6] text-[#F2F5F8] font-semibold' : 'bg-[#0B0F14] border-[#263241] text-[#A8B0BC] hover:border-[#3D82C6]' }}"
                >
                    ⏱️ Turno de Hoy
                </a>

                <!-- Últimos 7 Días -->
                <a 
                    href="{{ route('reports.index', array_merge(request()->except(['date_from', 'date_to']), ['period_preset' => 'week'])) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all {{ request()->get('period_preset') === 'week' ? 'bg-[#3D82C6]/20 border-[#3D82C6] text-[#F2F5F8] font-semibold' : 'bg-[#0B0F14] border-[#263241] text-[#A8B0BC] hover:border-[#3D82C6]' }}"
                >
                    📅 Últimos 7 Días
                </a>

                <!-- Mes en Curso -->
                <a 
                    href="{{ route('reports.index', array_merge(request()->except(['date_from', 'date_to']), ['period_preset' => 'month'])) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all {{ request()->get('period_preset') === 'month' ? 'bg-[#3D82C6]/20 border-[#3D82C6] text-[#F2F5F8] font-semibold' : 'bg-[#0B0F14] border-[#263241] text-[#A8B0BC] hover:border-[#3D82C6]' }}"
                >
                    📊 Mes en Curso
                </a>

                <!-- Por Prioridad Crítica -->
                <a 
                    href="{{ route('reports.index', array_merge(request()->except('priority_id'), ['report_type' => 'critical'])) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all {{ request()->get('report_type') === 'critical' ? 'bg-rose-500/20 border-rose-500 text-rose-300 font-semibold' : 'bg-[#0B0F14] border-[#263241] text-[#A8B0BC] hover:border-rose-500/50' }}"
                >
                    🚨 Incidencias Críticas
                </a>

                <!-- Por Estado: En Atención / Pendientes -->
                <a 
                    href="{{ route('reports.index', array_merge(request()->except('status_id'), ['report_type' => 'open'])) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all {{ request()->get('report_type') === 'open' ? 'bg-amber-500/20 border-amber-500 text-amber-300 font-semibold' : 'bg-[#0B0F14] border-[#263241] text-[#A8B0BC] hover:border-amber-500/50' }}"
                >
                    ⏳ Pendientes en Atención
                </a>

                <!-- Por Estado: Resueltas / VoBo -->
                <a 
                    href="{{ route('reports.index', array_merge(request()->except('status_id'), ['report_type' => 'resolved'])) }}"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-all {{ request()->get('report_type') === 'resolved' ? 'bg-emerald-500/20 border-emerald-500 text-emerald-300 font-semibold' : 'bg-[#0B0F14] border-[#263241] text-[#A8B0BC] hover:border-emerald-500/50' }}"
                >
                    ✅ Resueltas / VoBo
                </a>
            </div>
        </div>

        <!-- SECCIÓN 2: FORMULARIO DE FILTROS OPERATIVOS REALES -->
        <div class="bg-[#11151B] border border-[#263241] p-5 rounded-2xl shadow-sm">
            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col gap-4">
                @if(request()->has('report_type'))
                    <input type="hidden" name="report_type" value="{{ request()->get('report_type') }}">
                @endif
                @if(request()->has('period_preset') && !request()->has('date_from'))
                    <input type="hidden" name="period_preset" value="{{ request()->get('period_preset') }}">
                @endif

                <div class="flex items-center justify-between border-b border-[#1F2937] pb-2.5">
                    <span class="text-xs uppercase tracking-wider text-[#8D98A8] font-bold flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">filter_list</span>
                        <span>Sección 2 — Filtros Operativos Avanzados</span>
                    </span>
                    <span class="text-[11px] text-[#707B8A]">Los filtros se reflejan de forma idéntica en PDF, Correo y WhatsApp</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    
                    <!-- Fecha Desde -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium text-[#8D98A8]">Fecha Inicial:</label>
                        <input 
                            type="date" 
                            name="date_from" 
                            value="{{ $filters['date_from'] ?? '' }}"
                            class="w-full text-xs px-3 py-2 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6] focus:ring-1 focus:ring-[#3D82C6]"
                        >
                    </div>

                    <!-- Fecha Hasta -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium text-[#8D98A8]">Fecha Final:</label>
                        <input 
                            type="date" 
                            name="date_to" 
                            value="{{ $filters['date_to'] ?? '' }}"
                            class="w-full text-xs px-3 py-2 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6] focus:ring-1 focus:ring-[#3D82C6]"
                        >
                    </div>

                    <!-- Estado -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium text-[#8D98A8]">Estado Operativo:</label>
                        <select 
                            name="status_id" 
                            class="w-full text-xs px-3 py-2 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6] focus:ring-1 focus:ring-[#3D82C6]"
                        >
                            <option value="">Todos los estados</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}" {{ ($filters['status_id'] ?? '') == $status->id ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Prioridad -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium text-[#8D98A8]">Prioridad:</label>
                        <select 
                            name="priority_id" 
                            class="w-full text-xs px-3 py-2 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6] focus:ring-1 focus:ring-[#3D82C6]"
                        >
                            <option value="">Todas las prioridades</option>
                            @foreach($priorities as $priority)
                                <option value="{{ $priority->id }}" {{ ($filters['priority_id'] ?? '') == $priority->id ? 'selected' : '' }}>
                                    {{ $priority->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Subsistema / Área -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium text-[#8D98A8]">Subsistema / Área:</label>
                        <select 
                            name="category_id" 
                            class="w-full text-xs px-3 py-2 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6] focus:ring-1 focus:ring-[#3D82C6]"
                        >
                            <option value="">Todas las áreas</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Especialista GTS Asignado -->
                    <div class="flex flex-col gap-1">
                        <label class="text-[11px] font-medium text-[#8D98A8]">Técnico GTS:</label>
                        <select 
                            name="assigned_to" 
                            class="w-full text-xs px-3 py-2 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#DDE3EA] focus:outline-none focus:border-[#3D82C6] focus:ring-1 focus:ring-[#3D82C6]"
                        >
                            <option value="">Todos los técnicos</option>
                            @foreach($gtsUsers as $user)
                                <option value="{{ $user->id }}" {{ ($filters['assigned_to'] ?? '') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
                    <!-- Búsqueda textual libre -->
                    <div class="w-full sm:max-w-md relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#707B8A] text-[18px]">search</span>
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Buscar en folio, título, ubicación o descripción..."
                            class="w-full text-xs pl-9 pr-3 py-2 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#DDE3EA] placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6]"
                        >
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <a 
                            href="{{ route('reports.index') }}" 
                            class="px-4 py-2 rounded-xl bg-[#0B0F14] hover:bg-[#151A21] border border-[#263241] text-[#A8B0BC] text-xs font-semibold transition-all"
                        >
                            Limpiar
                        </a>
                        <button 
                            type="submit" 
                            class="px-5 py-2 rounded-xl bg-[#1E293B] hover:bg-[#283548] text-[#3D82C6] hover:text-white border border-[#3D82C6]/40 font-semibold text-xs transition-all flex items-center gap-2 shadow-sm"
                        >
                            <span class="material-symbols-outlined text-[17px]">filter_alt</span>
                            <span>Aplicar Filtros</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- SECCIÓN 3: RESUMEN EJECUTIVO PREVIO (TELEMETRÍA DE INCIDENCIAS) -->
        <div>
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs uppercase tracking-wider text-[#8D98A8] font-bold flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">analytics</span>
                    <span>Sección 3 — Resumen Ejecutivo Previo (Incidencias Filtradas)</span>
                </span>
                <span class="text-xs text-[#707B8A] font-mono">
                    {{ $summary['total'] }} registros consolidados
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                
                <!-- Total Filtrado -->
                <div class="bg-[#11151B] border border-[#263241] p-3.5 rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#8D98A8]">
                        <span class="text-[11px] font-medium uppercase">Total</span>
                        <span class="material-symbols-outlined text-[18px] text-[#3D82C6]">receipt_long</span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold font-mono text-[#F2F5F8]">{{ $summary['total'] }}</span>
                        <span class="text-[10px] text-[#707B8A] block">Incidencias en reporte</span>
                    </div>
                </div>

                <!-- Nuevas -->
                <div class="bg-[#11151B] border border-[#263241] p-3.5 rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#8D98A8]">
                        <span class="text-[11px] font-medium uppercase text-blue-400">Nuevas</span>
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold font-mono text-blue-400">{{ $summary['by_status']['NUEVA'] ?? 0 }}</span>
                        <span class="text-[10px] text-[#707B8A] block">Por asignar</span>
                    </div>
                </div>

                <!-- En Atención -->
                <div class="bg-[#11151B] border border-[#263241] p-3.5 rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#8D98A8]">
                        <span class="text-[11px] font-medium uppercase text-amber-400">En Atención</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold font-mono text-amber-400">{{ $summary['by_status']['EN_ATENCION'] ?? 0 }}</span>
                        <span class="text-[10px] text-[#707B8A] block">Técnico en campo</span>
                    </div>
                </div>

                <!-- Resueltas -->
                <div class="bg-[#11151B] border border-[#263241] p-3.5 rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#8D98A8]">
                        <span class="text-[11px] font-medium uppercase text-emerald-400">Resueltas</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold font-mono text-emerald-400">{{ $summary['by_status']['RESUELTA'] ?? 0 }}</span>
                        <span class="text-[10px] text-[#707B8A] block">Pendientes VoBo</span>
                    </div>
                </div>

                <!-- Cerradas -->
                <div class="bg-[#11151B] border border-[#263241] p-3.5 rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#8D98A8]">
                        <span class="text-[11px] font-medium uppercase text-[#8D98A8]">Cerradas</span>
                        <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold font-mono text-[#DDE3EA]">{{ $summary['by_status']['CERRADA'] ?? 0 }}</span>
                        <span class="text-[10px] text-[#707B8A] block">Concluidas con VoBo</span>
                    </div>
                </div>

                <!-- Eficacia & MTTR -->
                <div class="bg-[#11151B] border border-[#263241] p-3.5 rounded-xl flex flex-col justify-between">
                    <div class="flex items-center justify-between text-[#8D98A8]">
                        <span class="text-[11px] font-medium uppercase text-[#3D82C6]">Eficacia</span>
                        <span class="material-symbols-outlined text-[18px] text-[#3D82C6]">speed</span>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-bold font-mono text-[#3D82C6]">{{ $summary['resolution_rate'] }}%</span>
                        </div>
                        <span class="text-[10px] text-[#707B8A] block">MTTR: {{ $summary['avg_resolution_time_formatted'] }}</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- SECCIÓN 4 / TABLA DE REGISTROS TABULARES -->
        <div class="bg-[#11151B] border border-[#263241] rounded-2xl overflow-hidden shadow-sm flex flex-col">
            <div class="p-4 border-b border-[#263241] flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase tracking-wider text-[#8D98A8] font-bold">Detalle Nominal de Incidencias</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-[#151B23] border border-[#263241] text-[#3D82C6]">
                        {{ $incidents->total() }} registros
                    </span>
                </div>
                <div class="text-[11px] text-[#707B8A]">
                    Página {{ $incidents->currentPage() }} de {{ $incidents->lastPage() }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#DDE3EA]">
                    <thead class="bg-[#0B0F14] border-b border-[#263241] text-[10px] uppercase font-bold text-[#8D98A8] tracking-wider">
                        <tr>
                            <th class="py-3 px-4">Folio</th>
                            <th class="py-3 px-4">Apertura</th>
                            <th class="py-3 px-4">Subsistema</th>
                            <th class="py-3 px-4">Ubicación</th>
                            <th class="py-3 px-4">Falla / Título</th>
                            <th class="py-3 px-4 text-center">Prioridad</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4">Especialista GTS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1F2937]">
                        @forelse($incidents as $incident)
                            <tr class="hover:bg-[#151B23] transition-colors">
                                <!-- Folio -->
                                <td class="py-3 px-4 font-mono font-bold text-[#3D82C6]">
                                    <a href="{{ route('incidents.show', $incident->folio) }}" class="hover:underline flex items-center gap-1">
                                        <span>{{ $incident->folio }}</span>
                                        <span class="material-symbols-outlined text-[14px] text-[#707B8A]">open_in_new</span>
                                    </a>
                                </td>

                                <!-- Apertura -->
                                <td class="py-3 px-4 font-mono text-[#8D98A8] whitespace-nowrap">
                                    {{ $incident->created_at ? $incident->created_at->format('d/m/Y H:i') : 'N/D' }}
                                </td>

                                <!-- Subsistema / Categoría -->
                                <td class="py-3 px-4 font-semibold text-[#F2F5F8] whitespace-nowrap">
                                    {{ $incident->category->name ?? 'Sin categoría' }}
                                </td>

                                <!-- Ubicación -->
                                <td class="py-3 px-4 text-[#A8B0BC] max-w-[160px] truncate" title="{{ $incident->location }}">
                                    {{ $incident->location }}
                                </td>

                                <!-- Falla / Título -->
                                <td class="py-3 px-4 text-[#DDE3EA] max-w-[220px] truncate font-medium" title="{{ $incident->title }}">
                                    {{ $incident->title }}
                                </td>

                                <!-- Prioridad (Paso 4 Badge) -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <x-badge-priority :priority="$incident->priority" />
                                </td>

                                <!-- Estado -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @php
                                        $statusStyles = [
                                            'NUEVA' => 'bg-blue-500/15 text-blue-400 border-blue-500/30',
                                            'ASIGNADA' => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
                                            'EN_ATENCION' => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
                                            'RESUELTA' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
                                            'CERRADA' => 'bg-slate-500/15 text-slate-400 border-slate-500/30',
                                        ];
                                        $style = $statusStyles[$incident->status->code ?? ''] ?? 'bg-gray-500/15 text-gray-400 border-gray-500/30';
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $style }}">
                                        {{ $incident->status->name ?? 'N/D' }}
                                    </span>
                                </td>

                                <!-- Asignado GTS -->
                                <td class="py-3 px-4 text-[#A8B0BC] whitespace-nowrap">
                                    @if($incident->assignedTo)
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#3D82C6]"></span>
                                            <span>{{ $incident->assignedTo->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-[#707B8A] italic">Sin asignar</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-[#707B8A]">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-[36px] text-[#263241]">search_off</span>
                                        <span class="text-xs font-medium">No se encontraron incidencias con los criterios de filtrado seleccionados.</span>
                                        <a href="{{ route('reports.index') }}" class="text-xs text-[#3D82C6] hover:underline mt-1">
                                            Restablecer todos los filtros
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($incidents->hasPages())
                <div class="p-4 border-t border-[#263241] bg-[#0B0F14]">
                    {{ $incidents->links() }}
                </div>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: ENVIAR POR CORREO ELECTRÓNICO OFICIAL -->
        <!-- ========================================================================= -->
        <div 
            x-show="showEmailModal" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
        >
            <div 
                @click.outside="showEmailModal = false"
                class="w-full max-w-lg bg-[#11151B] border border-[#263241] rounded-2xl shadow-2xl overflow-hidden flex flex-col"
            >
                <div class="p-5 border-b border-[#263241] bg-[#151B23] flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-[#3D82C6]/15 border border-[#3D82C6]/30 flex items-center justify-center text-[#3D82C6]">
                            <span class="material-symbols-outlined text-[19px]">mail</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#F2F5F8]">Despachar Reporte Oficial por Correo</h3>
                            <span class="text-[10px] text-[#8D98A8]">Adjunta automáticamente el informe PDF con los filtros activos</span>
                        </div>
                    </div>
                    <button type="button" @click="showEmailModal = false" class="text-[#707B8A] hover:text-[#F2F5F8]">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('reports.email') }}" class="p-5 flex flex-col gap-4">
                    @csrf
                    <!-- Pasar los filtros actuales serializados -->
                    @foreach($filters as $k => $v)
                        @if(!empty($v))
                            <input type="hidden" name="filters[{{ $k }}]" value="{{ $v }}">
                        @endif
                    @endforeach

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-[#DDE3EA]">Correo Institucional Destinatario:</label>
                        <input 
                            type="email" 
                            name="email" 
                            x-model="emailRecipient"
                            required
                            placeholder="destinatario@aifa.aero o usuario@dominio.com"
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#F2F5F8] placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6] focus:ring-1 focus:ring-[#3D82C6]"
                        >
                        <span class="text-[10px] text-[#707B8A]">Se enviará una copia con el resumen y el archivo PDF sellado pericialmente.</span>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-[#DDE3EA]">Notas u Observaciones del Solicitante (Opcional):</label>
                        <textarea 
                            name="notes" 
                            x-model="emailNotes"
                            rows="3"
                            placeholder="Ingrese notas aclaratorias sobre el turno, instrucciones para la jefatura de guardia o contexto relevante..."
                            class="w-full text-xs p-3 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#F2F5F8] placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6] focus:ring-1 focus:ring-[#3D82C6]"
                        ></textarea>
                    </div>

                    <div class="p-3 rounded-xl bg-[#0B0F14] border border-[#263241] text-[11px] text-[#8D98A8] flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-[18px] text-[#3D82C6]">attach_file</span>
                        <span>Se adjuntará el archivo: <strong class="text-[#DDE3EA] font-mono">Reporte_GTS_{{ date('Ymd') }}.pdf</strong> ({{ $summary['total'] }} incidencias)</span>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#1F2937]">
                        <button 
                            type="button" 
                            @click="showEmailModal = false"
                            class="px-4 py-2 rounded-xl bg-[#0B0F14] hover:bg-[#151B23] border border-[#263241] text-[#A8B0BC] text-xs font-medium transition-all"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 rounded-xl bg-[#3D82C6] hover:bg-[#316cb0] text-white text-xs font-semibold shadow-md transition-all flex items-center gap-2"
                        >
                            <span class="material-symbols-outlined text-[16px]">send</span>
                            <span>Enviar Reporte Ahora</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL: COMPARTIR VÍA WHATSAPP -->
        <!-- ========================================================================= -->
        <div 
            x-show="showWhatsAppModal" 
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
        >
            <div 
                @click.outside="showWhatsAppModal = false"
                class="w-full max-w-lg bg-[#11151B] border border-[#263241] rounded-2xl shadow-2xl overflow-hidden flex flex-col"
            >
                <div class="p-5 border-b border-[#263241] bg-[#151B23] flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                            <span class="material-symbols-outlined text-[19px]">chat</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#F2F5F8]">Compartir Resumen por WhatsApp</h3>
                            <span class="text-[10px] text-[#8D98A8]">Despacho mediante API o enlace seguro de WhatsApp Web/Móvil</span>
                        </div>
                    </div>
                    <button type="button" @click="showWhatsAppModal = false" class="text-[#707B8A] hover:text-[#F2F5F8]">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('reports.whatsapp') }}" class="p-5 flex flex-col gap-4">
                    @csrf
                    @foreach($filters as $k => $v)
                        @if(!empty($v))
                            <input type="hidden" name="filters[{{ $k }}]" value="{{ $v }}">
                        @endif
                    @endforeach

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-semibold text-[#DDE3EA]">Número Telefónico del Destinatario (con lada):</label>
                        <input 
                            type="tel" 
                            name="phone" 
                            x-model="whatsAppPhone"
                            required
                            placeholder="Ej. +52 55 1234 5678 o 5512345678"
                            class="w-full text-xs px-3.5 py-2.5 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#F2F5F8] placeholder-[#707B8A] focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 font-mono"
                        >
                        <span class="text-[10px] text-[#707B8A]">Formato estándar con 10 dígitos o prefijo internacional (+52 para México).</span>
                    </div>

                    <!-- Vista Previa del Mensaje Formateado -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-[#8D98A8]">Vista Previa del Mensaje Oficial:</label>
                            <button 
                                type="button" 
                                @click="copyPreview()"
                                class="text-[11px] text-[#3D82C6] hover:text-white flex items-center gap-1"
                            >
                                <span class="material-symbols-outlined text-[13px]">content_copy</span>
                                <span x-text="copiedWhatsApp ? '¡Copiado!' : 'Copiar texto'"></span>
                            </button>
                        </div>
                        <div 
                            x-ref="whatsAppText"
                            class="w-full text-[11px] p-3 rounded-xl bg-[#0B0F14] border border-[#263241] text-[#A8B0BC] font-mono whitespace-pre-wrap leading-relaxed max-h-48 overflow-y-auto"
                        >{{ $whatsAppPreviewText }}</div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#1F2937]">
                        <button 
                            type="button" 
                            @click="showWhatsAppModal = false"
                            class="px-4 py-2 rounded-xl bg-[#0B0F14] hover:bg-[#151B23] border border-[#263241] text-[#A8B0BC] text-xs font-medium transition-all"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shadow-md transition-all flex items-center gap-2"
                        >
                            <span class="material-symbols-outlined text-[16px]">send</span>
                            <span>Enviar por WhatsApp</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
