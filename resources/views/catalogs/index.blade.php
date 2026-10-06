<x-app-layout>
    <div 
        x-data="{
            showCreateModal: false,
            showEditModal: false,
            showStatusModal: false,
            showViewModal: false,
            activeItem: {
                id: null,
                name: '',
                code: '',
                slug: '',
                description: '',
                location: '',
                technical_specs: '',
                icon: '',
                color: '#06B6D4',
                status: 'ACTIVO',
                incidents_count: 0,
                active_incidents_count: 0,
                availability: 100
            },
            openEdit(item) {
                this.activeItem = JSON.parse(JSON.stringify(item));
                this.showEditModal = true;
            },
            openStatus(item) {
                this.activeItem = JSON.parse(JSON.stringify(item));
                this.showStatusModal = true;
            },
            openView(item) {
                this.activeItem = JSON.parse(JSON.stringify(item));
                this.showViewModal = true;
            }
        }"
        class="min-h-screen bg-brand-canvas text-[#F2F5F8] p-4 lg:p-6 space-y-6"
    >
        <!-- 1. Encabezado institucional & Breadcrumbs -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-[#263241] pb-5">
            <div>
                <!-- Migas de pan periciales -->
                <nav class="flex items-center gap-2 text-xs font-mono text-[#707B8A] mb-1.5" aria-label="Breadcrumb">
                    <a href="{{ route('dashboard') }}" class="hover:text-[#3D82C6] transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">home</span>
                        <span>Inicio</span>
                    </a>
                    <span>/</span>
                    <span class="text-[#A8B0BC]">Infraestructura</span>
                    <span>/</span>
                    <span class="text-[#3D82C6] font-semibold">Catálogos Tecnológicos</span>
                </nav>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0E2D56] border border-[#164A8A]/50 flex items-center justify-center text-[#93C5FD]">
                        <span class="material-symbols-outlined text-[24px]">layers</span>
                    </div>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-bold tracking-tight text-white flex items-center gap-2.5">
                            Catálogos e Infraestructura Tecnológica
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-[#16273B] text-[#93C5FD] border border-[#234269]">
                                AIFA-GTS
                            </span>
                        </h1>
                        <p class="text-xs text-[#8D98A8] mt-0.5">
                            Inventario maestro, disponibilidad operacional y gestión de subsistemas de seguridad y periféricos.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Acciones de cabecera -->
            <div class="flex items-center gap-3">
                @can('create', App\Models\Category::class)
                    <button 
                        @click="showCreateModal = true"
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-[#3D82C6] hover:bg-[#2A65A0] text-white text-xs font-semibold flex items-center gap-2 transition-all shadow-md active:scale-95"
                    >
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>Alta de Infraestructura</span>
                    </button>
                @else
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[#151D28] border border-[#263241] text-xs text-[#8D98A8]">
                        <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">visibility</span>
                        <span>Modo Consulta (Solo Lectura)</span>
                    </div>
                @endcan
            </div>
        </div>

        <!-- Banners informativos / Alertas de sesión -->
        @if (session('success'))
            <div class="p-3.5 rounded-xl bg-[#063C2E]/80 border border-[#10B981]/40 text-[#6EE7B7] text-xs flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[18px] text-[#10B981]">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-[#6EE7B7] hover:text-white">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="p-3.5 rounded-xl bg-[#45121A]/80 border border-[#D83B52]/40 text-[#FCA5A5] text-xs flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[18px] text-[#D83B52]">error</span>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-[#FCA5A5] hover:text-white">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>
        @endif

        <!-- 2. Tarjetas de Telemetría e Indicadores Operacionales -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
            <!-- Total Infraestructura -->
            <div class="bg-[#11151B] border border-[#263241] rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-[#8D98A8] font-medium">Total Infraestructura</span>
                    <div class="w-8 h-8 rounded-lg bg-[#16273B] flex items-center justify-center text-[#93C5FD]">
                        <span class="material-symbols-outlined text-[18px]">domain</span>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-mono text-white">{{ $totalInfrastructure }}</span>
                    <span class="text-[11px] text-[#707B8A]">subsistemas</span>
                </div>
            </div>

            <!-- Activos Operacionales -->
            <div class="bg-[#11151B] border border-[#263241] rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-[#8D98A8] font-medium">Activos Operacionales</span>
                    <div class="w-8 h-8 rounded-lg bg-[#063C2E] flex items-center justify-center text-[#10B981]">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-mono text-[#10B981]">{{ $activeInfrastructure }}</span>
                    <span class="text-[11px] text-[#707B8A]">en línea</span>
                </div>
            </div>

            <!-- Fuera de Servicio -->
            <div class="bg-[#11151B] border border-[#263241] rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-[#8D98A8] font-medium">Fuera de Servicio</span>
                    <div class="w-8 h-8 rounded-lg bg-[#45121A] flex items-center justify-center text-[#D83B52]">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-mono text-[#D83B52]">{{ $outOfService }}</span>
                    <span class="text-[11px] text-[#707B8A]">críticos</span>
                </div>
            </div>

            <!-- Inactivos / Mant. -->
            <div class="bg-[#11151B] border border-[#263241] rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-[#8D98A8] font-medium">Inactivos / Mant.</span>
                    <div class="w-8 h-8 rounded-lg bg-[#1F2732] flex items-center justify-center text-[#94A3B8]">
                        <span class="material-symbols-outlined text-[18px]">pause_circle</span>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-mono text-[#94A3B8]">{{ $inactiveInfrastructure }}</span>
                    <span class="text-[11px] text-[#707B8A]">pausados</span>
                </div>
            </div>

            <!-- Disponibilidad Global -->
            <div class="bg-[#11151B] border border-[#263241] rounded-xl p-4 flex flex-col justify-between shadow-sm col-span-2 md:col-span-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-[#8D98A8] font-medium">Disponibilidad Global</span>
                    <div class="w-8 h-8 rounded-lg bg-[#0E2D56] flex items-center justify-center text-[#06B6D4]">
                        <span class="material-symbols-outlined text-[18px]">speed</span>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold font-mono {{ $availabilityRate >= 80 ? 'text-[#10B981]' : ($availabilityRate >= 50 ? 'text-[#E0A21A]' : 'text-[#D83B52]') }}">
                        {{ $availabilityRate }}%
                    </span>
                    <span class="text-[11px] text-[#707B8A]">Disponibilidad estimada</span>
                </div>
            </div>
        </div>

        <!-- 3. Gráficas Operativas Profesionales (Máximo 3, respondiendo dudas de ingeniería) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Gráfica 1: Distribución por Categoría / Subsistema -->
            <div class="bg-[#11151B] border border-[#263241] rounded-xl p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-[17px] text-[#3D82C6]">pie_chart</span>
                        <span>Distribución por Categoría</span>
                    </h3>
                    <span class="text-[10px] text-[#707B8A] font-mono">Inventario Base</span>
                </div>

                <div class="flex items-center justify-between gap-3 flex-1 min-h-[130px]">
                    <!-- Donut SVG Vectorial -->
                    <div class="relative w-28 h-28 shrink-0 flex items-center justify-center">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="38" fill="none" stroke="#1F2937" stroke-width="12" />
                            @php
                                $circumference = 238.76;
                                $runningOffset = 0;
                            @endphp
                            @foreach($distributionData as $item)
                                @php
                                    $dash = ($item['percentage'] / 100) * $circumference;
                                @endphp
                                <circle 
                                    cx="50" 
                                    cy="50" 
                                    r="38" 
                                    fill="none" 
                                    stroke="{{ $item['color'] }}" 
                                    stroke-width="12" 
                                    stroke-dasharray="{{ $dash }} {{ $circumference }}" 
                                    stroke-dashoffset="-{{ $runningOffset }}" 
                                />
                                @php
                                    $runningOffset += $dash;
                                @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="font-mono text-base font-bold text-white leading-none">{{ $totalInfrastructure }}</span>
                            <span class="text-[9px] text-[#707B8A] mt-0.5">Activos</span>
                        </div>
                    </div>

                    <!-- Leyenda interactiva -->
                    <div class="flex flex-col gap-1.5 text-[11px] min-w-0 flex-1">
                        @foreach($distributionData as $distItem)
                            <div class="flex items-center justify-between text-xs">
                                <span class="flex items-center gap-2 text-[#DDE3EA] truncate">
                                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $distItem['color'] }}"></span>
                                    <span class="truncate">{{ $distItem['name'] }}</span>
                                </span>
                                <span class="font-mono text-[#8D98A8] shrink-0 ml-1">
                                    {{ $distItem['percentage'] }}%
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Gráfica 2: Estado de Salud del Parque Tecnológico -->
            <div class="bg-[#11151B] border border-[#263241] rounded-xl p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-[17px] text-[#10B981]">health_and_safety</span>
                        <span>Salud Operativa del Parque</span>
                    </h3>
                    <span class="text-[10px] text-[#707B8A] font-mono">Disponibilidad</span>
                </div>

                <div class="space-y-3 flex-1 flex flex-col justify-center">
                    @foreach($statusBreakdown as $sb)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-[#DDE3EA] flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $sb['color'] }}"></span>
                                    <span>{{ $sb['label'] }}</span>
                                </span>
                                <span class="font-mono text-[#8D98A8]">
                                    <strong class="text-white">{{ $sb['count'] }}</strong> ({{ $sb['percentage'] }}%)
                                </span>
                            </div>
                            <div class="w-full bg-[#1E293B] rounded-full h-2.5 overflow-hidden">
                                <div 
                                    class="h-full rounded-full transition-all duration-500" 
                                    style="width: {{ $sb['percentage'] }}%; background-color: {{ $sb['color'] }};"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Gráfica 3: Incidencias Asociadas por Infraestructura -->
            <div class="bg-[#11151B] border border-[#263241] rounded-xl p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-[17px] text-[#E0A21A]">build_circle</span>
                        <span>Incidencias por Infraestructura</span>
                    </h3>
                    <span class="text-[10px] text-[#707B8A] font-mono">Histórico vs Activas</span>
                </div>

                <div class="space-y-2 flex-1 flex flex-col justify-center text-xs">
                    @forelse($incidentsByInfrastructure as $incInfra)
                        <div>
                            <div class="flex items-center justify-between text-[11px] mb-1">
                                <span class="text-[#DDE3EA] truncate max-w-[140px]">{{ $incInfra['name'] }}</span>
                                <span class="font-mono text-[#707B8A]">
                                    <span class="text-[#D83B52] font-semibold">{{ $incInfra['active'] }} activas</span> / 
                                    <span>{{ $incInfra['total'] }} total</span>
                                </span>
                            </div>
                            <div class="w-full bg-[#1E293B] rounded-full h-2 overflow-hidden flex">
                                @php
                                    $actWidth = $incInfra['total'] > 0 ? ($incInfra['active'] / $incInfra['total']) * 100 : 0;
                                    $resWidth = 100 - $actWidth;
                                @endphp
                                <div class="bg-[#D83B52] h-full" style="width: {{ $actWidth }}%"></div>
                                <div class="bg-[#10B981]/70 h-full" style="width: {{ $resWidth }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-[#707B8A] py-6 text-xs">Sin datos registrados</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 4. Barra de Filtros y Búsqueda -->
        <div class="bg-[#11151B] border border-[#263241] rounded-xl p-3.5">
            <form method="GET" action="{{ route('catalogs.index') }}" class="flex flex-col md:flex-row items-center gap-3" autocomplete="off">
                <!-- Búsqueda textual -->
                <div class="relative flex-1 w-full">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-[18px] text-[#707B8A]">search</span>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $filters['search'] ?? '' }}" 
                        placeholder="Buscar por código, nombre, ubicación o especificación técnica..."
                        class="w-full pl-9 pr-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6] transition-colors"
                    >
                </div>

                <!-- Filtro de Categoría / Subsistema -->
                <div class="w-full md:w-56">
                    <select 
                        name="category" 
                        class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6] transition-colors"
                    >
                        <option value="">Todos los subsistemas</option>
                        @foreach($categoriesList as $catOpt)
                            <option value="{{ $catOpt->slug }}" {{ ($filters['category'] ?? '') === $catOpt->slug ? 'selected' : '' }}>
                                {{ $catOpt->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Estado -->
                <div class="w-full md:w-48">
                    <select 
                        name="status" 
                        class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6] transition-colors"
                    >
                        <option value="">Todos los estados</option>
                        <option value="ACTIVO" {{ ($filters['status'] ?? '') === 'ACTIVO' ? 'selected' : '' }}>🟢 ACTIVO</option>
                        <option value="FUERA DE SERVICIO" {{ ($filters['status'] ?? '') === 'FUERA DE SERVICIO' ? 'selected' : '' }}>🔴 FUERA DE SERVICIO</option>
                        <option value="INACTIVO" {{ ($filters['status'] ?? '') === 'INACTIVO' ? 'selected' : '' }}>⚪ INACTIVO / MANT.</option>
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button 
                        type="submit" 
                        class="w-full md:w-auto px-4 py-2 bg-[#1E293B] hover:bg-[#2A3749] text-[#F2F5F8] text-xs font-semibold rounded-xl border border-[#334155] transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span class="material-symbols-outlined text-[16px]">filter_alt</span>
                        <span>Filtrar</span>
                    </button>
                    @if(!empty($filters['search']) || !empty($filters['category']) || !empty($filters['status']))
                        <a 
                            href="{{ route('catalogs.index') }}" 
                            class="px-3 py-2 bg-[#161F2C] hover:bg-[#1E2732] text-[#8D98A8] hover:text-white text-xs rounded-xl border border-[#263241] transition-colors flex items-center justify-center"
                            title="Limpiar filtros"
                        >
                            <span class="material-symbols-outlined text-[16px]">clear_all</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- 5. Tabla de Inventario de Infraestructura -->
        <div class="bg-[#11151B] border border-[#263241] rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-[#161F2C] border-b border-[#263241] text-[#8D98A8] font-mono uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-4">Código</th>
                            <th class="py-3 px-4">Subsistema / Infraestructura</th>
                            <th class="py-3 px-4">Cobertura / Ubicación</th>
                            <th class="py-3 px-4 text-center">Incidencias</th>
                            <th class="py-3 px-4 text-center">Disponibilidad</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1D2533]">
                        @forelse($infrastructureItems as $infra)
                            <tr class="hover:bg-[#151D28]/60 transition-colors">
                                <!-- Código -->
                                <td class="py-3 px-4 font-mono font-bold text-[#3D82C6] whitespace-nowrap">
                                    {{ $infra->code ?: 'INF-'.strtoupper($infra->slug) }}
                                </td>

                                <!-- Nombre y Tipo -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div 
                                            class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0" 
                                            style="background-color: {{ $infra->color }}22; border: 1px solid {{ $infra->color }}44; color: {{ $infra->color }};"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">{{ $infra->icon ?: 'hub' }}</span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-white">{{ $infra->name }}</div>
                                            <div class="text-[11px] text-[#707B8A] truncate max-w-xs">{{ $infra->description }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Cobertura / Ubicación -->
                                <td class="py-3 px-4 text-[#C5CED8]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[14px] text-[#707B8A]">location_on</span>
                                        <span class="truncate max-w-[200px]">{{ $infra->location ?: 'Aeropuerto General (Lado Aire / Lado Tierra)' }}</span>
                                    </div>
                                </td>

                                <!-- Incidencias Asociadas -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-[#1F2937] text-[#9CA3AF] border border-[#374151]">
                                            {{ $infra->incidents_count }} total
                                        </span>
                                        @if($infra->active_incidents_count > 0)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-[#45121A] text-[#F87171] border border-[#7F1D1D]">
                                                {{ $infra->active_incidents_count }} activas
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Disponibilidad -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="font-mono font-bold text-xs {{ $infra->availability_percentage >= 90 ? 'text-[#10B981]' : ($infra->availability_percentage >= 70 ? 'text-[#E0A21A]' : 'text-[#D83B52]') }}">
                                            {{ $infra->availability_percentage }}%
                                        </span>
                                        <div class="w-16 bg-[#1F2937] rounded-full h-1.5 mt-1 overflow-hidden">
                                            <div 
                                                class="h-full rounded-full {{ $infra->availability_percentage >= 90 ? 'bg-[#10B981]' : ($infra->availability_percentage >= 70 ? 'bg-[#E0A21A]' : 'bg-[#D83B52]') }}" 
                                                style="width: {{ $infra->availability_percentage }}%"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Estado Operacional -->
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($infra->status === 'ACTIVO')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-[#063C2E] text-[#10B981] border border-[#10B981]/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#10B981] animate-pulse"></span>
                                            <span>ACTIVO</span>
                                        </span>
                                    @elseif($infra->status === 'FUERA DE SERVICIO')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-[#45121A] text-[#F87171] border border-[#D83B52]/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#D83B52]"></span>
                                            <span>FUERA DE SERVICIO</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-[#1F2732] text-[#94A3B8] border border-[#475569]/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#94A3B8]"></span>
                                            <span>INACTIVO</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Acciones -->
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Botón Ver Detalles Técnicos (Todos los perfiles) -->
                                        <button 
                                            @click="openView({{ json_encode([
                                                'id' => $infra->id,
                                                'name' => $infra->name,
                                                'code' => $infra->code ?: 'INF-'.strtoupper($infra->slug),
                                                'slug' => $infra->slug,
                                                'description' => $infra->description,
                                                'location' => $infra->location,
                                                'technical_specs' => $infra->technical_specs,
                                                'icon' => $infra->icon,
                                                'color' => $infra->color,
                                                'status' => $infra->status,
                                                'incidents_count' => $infra->incidents_count,
                                                'active_incidents_count' => $infra->active_incidents_count,
                                                'availability' => $infra->availability_percentage,
                                            ]) }})"
                                            type="button" 
                                            class="p-1.5 rounded-lg bg-[#161F2C] hover:bg-[#1E293B] text-[#A8B0BC] hover:text-white border border-[#263241] transition-colors"
                                            title="Ver Ficha Técnica"
                                        >
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                        </button>

                                        @can('update', $infra)
                                            <!-- Botón Editar (Solo Administrador) -->
                                            <button 
                                                @click="openEdit({{ json_encode([
                                                    'id' => $infra->id,
                                                    'name' => $infra->name,
                                                    'code' => $infra->code ?: 'INF-'.strtoupper($infra->slug),
                                                    'slug' => $infra->slug,
                                                    'description' => $infra->description,
                                                    'location' => $infra->location,
                                                    'technical_specs' => $infra->technical_specs,
                                                    'icon' => $infra->icon,
                                                    'color' => $infra->color,
                                                    'status' => $infra->status,
                                                ]) }})"
                                                type="button" 
                                                class="p-1.5 rounded-lg bg-[#161F2C] hover:bg-[#1E293B] text-[#3D82C6] hover:text-[#93C5FD] border border-[#263241] transition-colors"
                                                title="Editar Registro"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                            </button>
                                        @endcan

                                        @can('toggleStatus', $infra)
                                            <!-- Botón Cambiar Estado / Dar de baja (Solo Administrador) -->
                                            <button 
                                                @click="openStatus({{ json_encode([
                                                    'id' => $infra->id,
                                                    'name' => $infra->name,
                                                    'code' => $infra->code ?: 'INF-'.strtoupper($infra->slug),
                                                    'status' => $infra->status,
                                                ]) }})"
                                                type="button" 
                                                class="p-1.5 rounded-lg bg-[#161F2C] hover:bg-[#1E293B] text-[#E0A21A] hover:text-[#FCD34D] border border-[#263241] transition-colors"
                                                title="Dar de Baja / Cambiar Estado Operacional"
                                            >
                                                <span class="material-symbols-outlined text-[16px]">toggle_on</span>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-[#707B8A]">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="material-symbols-outlined text-[36px] text-[#475569] mb-2">layers_clear</span>
                                        <p class="font-medium text-white text-sm">No se encontraron recursos de infraestructura</p>
                                        <p class="text-xs text-[#707B8A] mt-1">Pruebe ajustando los filtros de búsqueda o categoría.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($infrastructureItems->hasPages())
                <div class="p-3.5 border-t border-[#263241] bg-[#161F2C]">
                    {{ $infrastructureItems->links() }}
                </div>
            @endif
        </div>

        <!-- ================= MODALS ================= -->

        <!-- MODAL 1: ALTA DE INFRAESTRUCTURA (ADMIN) -->
        <div 
            x-show="showCreateModal" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
        >
            <div 
                @click.away="showCreateModal = false"
                class="bg-[#11151B] border border-[#263241] rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl p-6 space-y-5"
            >
                <div class="flex items-center justify-between border-b border-[#263241] pb-3.5">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-[#3D82C6]">add_circle</span>
                        <span>Dar de Alta Recurso de Infraestructura</span>
                    </h3>
                    <button @click="showCreateModal = false" class="text-[#707B8A] hover:text-white">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form method="POST" action="{{ route('catalogs.store') }}" class="space-y-4" autocomplete="off">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Nombre del Subsistema / Recurso *</label>
                            <input 
                                type="text" 
                                name="name" 
                                required 
                                placeholder="Ej: Megafonía PAS, Cámaras Perimétricas..."
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Código de Identificación</label>
                            <input 
                                type="text" 
                                name="code" 
                                placeholder="Ej: INF-PAS, INF-CCTV-02..."
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Estado Operativo Inicial *</label>
                            <select 
                                name="status" 
                                required 
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                                <option value="ACTIVO">🟢 ACTIVO (Operacional)</option>
                                <option value="FUERA DE SERVICIO">🔴 FUERA DE SERVICIO (Falla / Indisponible)</option>
                                <option value="INACTIVO">⚪ INACTIVO (Mantenimiento / Pausado)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Ubicación / Cobertura Física</label>
                            <input 
                                type="text" 
                                name="location" 
                                placeholder="Ej: Terminal de Pasajeros T1, Pistas, Torre..."
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-[#C5CED8] mb-1">Descripción General</label>
                        <textarea 
                            name="description" 
                            rows="2" 
                            placeholder="Descripción operativa del subsistema y componentes..."
                            class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6]"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-[#C5CED8] mb-1">Especificaciones Técnicas / Topología</label>
                        <textarea 
                            name="technical_specs" 
                            rows="2" 
                            placeholder="Fabricante, modelos, protocolos IP, switches de interconexión..."
                            class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6]"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Icono Material</label>
                            <input 
                                type="text" 
                                name="icon" 
                                value="hub" 
                                placeholder="videocam, fingerprint, tv, radio, hub..."
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Color Institucional</label>
                            <input 
                                type="text" 
                                name="color" 
                                value="#06B6D4" 
                                placeholder="#06B6D4, #8B5CF6, #3D82C6..."
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#263241]">
                        <button 
                            @click="showCreateModal = false" 
                            type="button" 
                            class="px-4 py-2 bg-[#1E293B] hover:bg-[#263241] text-[#A8B0BC] text-xs font-semibold rounded-xl transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 bg-[#3D82C6] hover:bg-[#2A65A0] text-white text-xs font-semibold rounded-xl shadow-md transition-all"
                        >
                            Guardar en Catálogo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: EDITAR INFRAESTRUCTURA (ADMIN) -->
        <div 
            x-show="showEditModal" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
        >
            <div 
                @click.away="showEditModal = false"
                class="bg-[#11151B] border border-[#263241] rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto shadow-2xl p-6 space-y-5"
            >
                <div class="flex items-center justify-between border-b border-[#263241] pb-3.5">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-[#3D82C6]">edit</span>
                        <span>Editar Ficha de Infraestructura</span>
                    </h3>
                    <button @click="showEditModal = false" class="text-[#707B8A] hover:text-white">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <form 
                    method="POST" 
                    :action="'/catalogos/' + activeItem.id" 
                    class="space-y-4"
                >
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Nombre *</label>
                            <input 
                                type="text" 
                                name="name" 
                                x-model="activeItem.name" 
                                required 
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Código</label>
                            <input 
                                type="text" 
                                name="code" 
                                x-model="activeItem.code" 
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Estado Operacional *</label>
                            <select 
                                name="status" 
                                x-model="activeItem.status" 
                                required 
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                                <option value="ACTIVO">🟢 ACTIVO</option>
                                <option value="FUERA DE SERVICIO">🔴 FUERA DE SERVICIO</option>
                                <option value="INACTIVO">⚪ INACTIVO</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Ubicación / Cobertura</label>
                            <input 
                                type="text" 
                                name="location" 
                                x-model="activeItem.location" 
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-[#C5CED8] mb-1">Descripción</label>
                        <textarea 
                            name="description" 
                            x-model="activeItem.description" 
                            rows="2" 
                            class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-[#C5CED8] mb-1">Especificaciones Técnicas</label>
                        <textarea 
                            name="technical_specs" 
                            x-model="activeItem.technical_specs" 
                            rows="2" 
                            class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Icono Material</label>
                            <input 
                                type="text" 
                                name="icon" 
                                x-model="activeItem.icon" 
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#C5CED8] mb-1">Color Hex</label>
                            <input 
                                type="text" 
                                name="color" 
                                x-model="activeItem.color" 
                                class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white focus:outline-none focus:border-[#3D82C6]"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#263241]">
                        <button 
                            @click="showEditModal = false" 
                            type="button" 
                            class="px-4 py-2 bg-[#1E293B] hover:bg-[#263241] text-[#A8B0BC] text-xs font-semibold rounded-xl transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 bg-[#3D82C6] hover:bg-[#2A65A0] text-white text-xs font-semibold rounded-xl shadow-md transition-all"
                        >
                            Actualizar Registro
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 3: CAMBIAR ESTADO / DAR DE BAJA (ADMIN) -->
        <div 
            x-show="showStatusModal" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
        >
            <div 
                @click.away="showStatusModal = false"
                class="bg-[#11151B] border border-[#263241] rounded-2xl w-full max-w-md shadow-2xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between border-b border-[#263241] pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-[#E0A21A]">toggle_on</span>
                        <span>Gestionar Estado Operacional</span>
                    </h3>
                    <button @click="showStatusModal = false" class="text-[#707B8A] hover:text-white">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="p-3 rounded-xl bg-[#16273B] border border-[#234269] text-xs text-[#93C5FD]">
                    <div class="font-bold mb-1" x-text="activeItem.code + ' — ' + activeItem.name"></div>
                    <p class="text-[11px] text-[#A8B0BC]">
                        ℹ️ <strong>Nota de Seguridad Operativa:</strong> "Dar de baja" o poner fuera de servicio no elimina el registro físico de la base de datos, preservando la trazabilidad pericial e histórico de tickets.
                    </p>
                </div>

                <form 
                    method="POST" 
                    :action="'/catalogos/' + activeItem.id + '/status'" 
                    class="space-y-4"
                >
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-[#C5CED8] mb-1.5">Nuevo Estado *</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 p-2.5 rounded-xl border border-[#263241] bg-[#161F2C] hover:bg-[#1E2732] cursor-pointer">
                                <input type="radio" name="status" value="ACTIVO" x-model="activeItem.status" class="text-[#10B981] focus:ring-0">
                                <div>
                                    <div class="font-semibold text-xs text-[#10B981]">🟢 ACTIVO</div>
                                    <div class="text-[11px] text-[#707B8A]">Operativo y disponible para el servicio regular en AIFA.</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-2.5 rounded-xl border border-[#263241] bg-[#161F2C] hover:bg-[#1E2732] cursor-pointer">
                                <input type="radio" name="status" value="FUERA DE SERVICIO" x-model="activeItem.status" class="text-[#D83B52] focus:ring-0">
                                <div>
                                    <div class="font-semibold text-xs text-[#D83B52]">🔴 FUERA DE SERVICIO</div>
                                    <div class="text-[11px] text-[#707B8A]">Indisponible por falla crítica o degradación estructural.</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-2.5 rounded-xl border border-[#263241] bg-[#161F2C] hover:bg-[#1E2732] cursor-pointer">
                                <input type="radio" name="status" value="INACTIVO" x-model="activeItem.status" class="text-[#94A3B8] focus:ring-0">
                                <div>
                                    <div class="font-semibold text-xs text-[#94A3B8]">⚪ INACTIVO (Dar de Baja)</div>
                                    <div class="text-[11px] text-[#707B8A]">Retirado temporalmente de operación por ventana de mantenimiento.</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-[#C5CED8] mb-1">Motivo / Bitácora del Cambio (Opcional)</label>
                        <input 
                            type="text" 
                            name="reason" 
                            placeholder="Ej: Mantenimiento correctivo mayor programado..."
                            class="w-full px-3 py-2 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6]"
                        >
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#263241]">
                        <button 
                            @click="showStatusModal = false" 
                            type="button" 
                            class="px-4 py-2 bg-[#1E293B] hover:bg-[#263241] text-[#A8B0BC] text-xs font-semibold rounded-xl transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 bg-[#3D82C6] hover:bg-[#2A65A0] text-white text-xs font-semibold rounded-xl shadow-md transition-all"
                        >
                            Aplicar Cambio
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 4: FICHA TÉCNICA / CONSULTA (ACCESIBLE A TODOS: APOC, GTS, ADMIN) -->
        <div 
            x-show="showViewModal" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
        >
            <div 
                @click.away="showViewModal = false"
                class="bg-[#11151B] border border-[#263241] rounded-2xl w-full max-w-lg shadow-2xl p-6 space-y-4"
            >
                <div class="flex items-center justify-between border-b border-[#263241] pb-3">
                    <div class="flex items-center gap-2.5">
                        <div 
                            class="w-8 h-8 rounded-lg flex items-center justify-center" 
                            :style="'background-color: ' + activeItem.color + '22; color: ' + activeItem.color + '; border: 1px solid ' + activeItem.color + '44;'"
                        >
                            <span class="material-symbols-outlined text-[18px]" x-text="activeItem.icon || 'hub'"></span>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white" x-text="activeItem.name"></h3>
                            <span class="text-[11px] font-mono text-[#3D82C6]" x-text="activeItem.code"></span>
                        </div>
                    </div>
                    <button @click="showViewModal = false" class="text-[#707B8A] hover:text-white">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="space-y-3.5 text-xs">
                    <!-- Estado y Disponibilidad -->
                    <div class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-[#161F2C] border border-[#263241]">
                        <div>
                            <span class="text-[#707B8A] text-[10px] block uppercase font-mono">Estado Operacional</span>
                            <span 
                                class="font-bold inline-block mt-0.5" 
                                :class="activeItem.status === 'ACTIVO' ? 'text-[#10B981]' : (activeItem.status === 'FUERA DE SERVICIO' ? 'text-[#D83B52]' : 'text-[#94A3B8]')"
                                x-text="activeItem.status"
                            ></span>
                        </div>
                        <div>
                            <span class="text-[#707B8A] text-[10px] block uppercase font-mono">Disponibilidad Estimada</span>
                            <span 
                                class="font-mono font-bold inline-block mt-0.5" 
                                :class="activeItem.availability >= 90 ? 'text-[#10B981]' : 'text-[#E0A21A]'"
                                x-text="activeItem.availability + '%'"
                            ></span>
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div>
                        <span class="text-[#707B8A] text-[10px] uppercase font-mono block">Ubicación y Cobertura</span>
                        <p class="text-white mt-0.5" x-text="activeItem.location || 'Todo el complejo aeroportuario AIFA'"></p>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <span class="text-[#707B8A] text-[10px] uppercase font-mono block">Descripción Operativa</span>
                        <p class="text-[#C5CED8] mt-0.5 leading-relaxed" x-text="activeItem.description || 'Sin descripción detallada'"></p>
                    </div>

                    <!-- Especificaciones Técnicas -->
                    <div>
                        <span class="text-[#707B8A] text-[10px] uppercase font-mono block">Especificaciones Técnicas</span>
                        <div class="p-2.5 rounded-xl bg-[#0F1620] border border-[#263241] text-[#93C5FD] font-mono text-[11px] mt-0.5" x-text="activeItem.technical_specs || 'Equipos IP industriales estándar AIFA.'"></div>
                    </div>

                    <!-- Historial de Incidencias -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-[#161F2C] border border-[#263241]">
                        <span class="text-[#C5CED8]">Incidencias Registradas:</span>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-white font-bold" x-text="activeItem.incidents_count + ' totales'"></span>
                            <template x-if="activeItem.active_incidents_count > 0">
                                <span class="font-mono text-[#F87171] font-semibold" x-text="'(' + activeItem.active_incidents_count + ' activas)'"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-3 border-t border-[#263241]">
                    <button 
                        @click="showViewModal = false" 
                        type="button" 
                        class="px-5 py-2 bg-[#1E293B] hover:bg-[#263241] text-white text-xs font-semibold rounded-xl transition-colors"
                    >
                        Cerrar Ficha
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>

