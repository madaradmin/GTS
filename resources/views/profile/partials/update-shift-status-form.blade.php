<section class="space-y-6">
    <header class="flex items-center justify-between gap-4 border-b border-[#263241] pb-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs uppercase tracking-widest text-[#3D82C6] font-semibold font-mono">Control de Guardia</span>
                <span class="text-[#707B8A]">•</span>
                <span class="text-xs uppercase tracking-widest text-[#8D98A8] font-mono">GTS Operaciones</span>
            </div>
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px] text-[#10B981]">timelapse</span>
                <span>Estado de Turno y Disponibilidad Operacional</span>
            </h2>
            <p class="mt-1 text-xs text-[#8D98A8]">
                Define tu disponibilidad inmediata para la recepción, despacho y asignación de incidencias en campo y consola AIFA.
            </p>
        </div>

        <!-- Badge de Estado Actual -->
        <div class="shrink-0">
            @if(auth()->user()->isShiftActive())
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-[#063C2E] text-[#10B981] border border-[#10B981]/40 shadow-sm">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#10B981] animate-pulse"></span>
                    <span>🟢 ACTIVO / DISPONIBLE</span>
                </div>
            @else
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-[#1F2732] text-[#94A3B8] border border-[#475569]/40 shadow-sm">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#94A3B8]"></span>
                    <span>⚪ NO DISPONIBLE</span>
                </div>
            @endif
        </div>
    </header>

    @if (session('status') && str_contains(session('status'), 'Disponibilidad operativa'))
        <div class="p-3 rounded-xl bg-[#063C2E]/80 border border-[#10B981]/40 text-[#6EE7B7] text-xs flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px] text-[#10B981]">check_circle</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Formulario para Cambio de Estado -->
    <form method="POST" action="{{ route('profile.shift-status') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <!-- Opción: ACTIVO -->
            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all {{ auth()->user()->isShiftActive() ? 'border-[#10B981] bg-[#063C2E]/20 text-white shadow-sm' : 'border-[#263241] bg-[#161F2C] hover:bg-[#1C2737] text-[#C5CED8]' }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#10B981] {{ auth()->user()->isShiftActive() ? 'animate-ping' : '' }}"></span>
                        <span class="font-bold text-sm text-[#10B981]">🟢 ACTIVO</span>
                    </div>
                    <input 
                        type="radio" 
                        name="status" 
                        value="ACTIVO" 
                        {{ auth()->user()->isShiftActive() ? 'checked' : '' }}
                        class="text-[#10B981] focus:ring-0"
                    >
                </div>
                <p class="text-[11px] text-[#8D98A8] leading-relaxed">
                    Disponible en posición para recibir y atender reportes de fallas tecnológicas en terminales, filtros y lado aire.
                </p>
            </label>

            <!-- Opción: NO DISPONIBLE -->
            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all {{ auth()->user()->isShiftUnavailable() ? 'border-[#94A3B8] bg-[#1F2732]/40 text-white shadow-sm' : 'border-[#263241] bg-[#161F2C] hover:bg-[#1C2737] text-[#C5CED8]' }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-[#94A3B8]"></span>
                        <span class="font-bold text-sm text-[#94A3B8]">⚪ NO DISPONIBLE</span>
                    </div>
                    <input 
                        type="radio" 
                        name="status" 
                        value="NO DISPONIBLE" 
                        {{ auth()->user()->isShiftUnavailable() ? 'checked' : '' }}
                        class="text-[#94A3B8] focus:ring-0"
                    >
                </div>
                <p class="text-[11px] text-[#8D98A8] leading-relaxed">
                    En descanso, receso programado, traslado o asignación especial no disponible para nuevas incidencias.
                </p>
            </label>
        </div>

        <!-- Motivo / Justificación opcional -->
        <div>
            <label for="shift_reason" class="block text-xs font-medium text-[#C5CED8] mb-1">
                Motivo / Nota de Bitácora (Opcional)
            </label>
            <input 
                type="text" 
                id="shift_reason" 
                name="reason" 
                placeholder="Ej: Inicio de guardia matutina, Receso de alimentos, Atención en sitio..."
                class="w-full px-3.5 py-2.5 bg-[#161F2C] border border-[#263241] rounded-xl text-xs text-white placeholder-[#707B8A] focus:outline-none focus:border-[#3D82C6] transition-colors"
            >
        </div>

        <div class="flex items-center justify-between pt-2">
            <div class="text-[11px] text-[#707B8A] font-mono">
                @if(auth()->user()->shift_status_updated_at)
                    Último cambio: {{ auth()->user()->shift_status_updated_at->format('d/m/Y H:i:s') }}
                @else
                    Sin modificaciones registradas en turno
                @endif
            </div>

            <button 
                type="submit" 
                class="px-5 py-2 rounded-xl bg-[#3D82C6] hover:bg-[#2A65A0] text-white text-xs font-semibold flex items-center gap-2 shadow-md transition-all active:scale-95"
            >
                <span class="material-symbols-outlined text-[16px]">sync</span>
                <span>Actualizar Disponibilidad</span>
            </button>
        </div>
    </form>

    <!-- Historial Reciente de Cambios de Disponibilidad -->
    @php
        $recentLogs = auth()->user()->availabilityLogs()->take(5)->get();
    @endphp
    @if($recentLogs->isNotEmpty())
        <div class="mt-6 pt-4 border-t border-[#263241]">
            <h3 class="text-xs font-bold uppercase tracking-wider text-[#8D98A8] font-mono mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px] text-[#3D82C6]">history</span>
                <span>Historial Reciente de Guardia y Disponibilidad</span>
            </h3>

            <div class="space-y-2">
                @foreach($recentLogs as $log)
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-[#161F2C] border border-[#263241] text-xs">
                        <div class="flex items-center gap-3">
                            @if($log->status === 'ACTIVO')
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#063C2E] text-[#10B981] border border-[#10B981]/30">
                                    🟢 ACTIVO
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#1F2732] text-[#94A3B8] border border-[#475569]/30">
                                    ⚪ NO DISPONIBLE
                                </span>
                            @endif
                            <span class="text-[#DDE3EA]">
                                {{ $log->reason ?: 'Actualización manual de estado' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] font-mono text-[#707B8A]">
                            @if($log->ip_address)
                                <span class="hidden sm:inline">IP: {{ $log->ip_address }}</span>
                            @endif
                            <span>{{ $log->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Descargo Institucional -->
    <div class="p-3 rounded-xl bg-[#151D28] border border-[#263241] text-[11px] text-[#8D98A8] flex items-start gap-2.5">
        <span class="material-symbols-outlined text-[16px] text-[#3D82C6] shrink-0 mt-0.5">info</span>
        <p class="leading-relaxed">
            <strong>Disponibilidad Operativa:</strong> Este control gobierna exclusivamente la asignación y despacho de incidencias dentro del sistema GTS. No altera el cómputo de horarios laborales ni interfiere con los registros de Recursos Humanos del Aeropuerto Internacional Felipe Ángeles.
        </p>
    </div>
</section>
