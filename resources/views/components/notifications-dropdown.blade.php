@php
    $user = auth()->user();
    $rawNotifications = $user 
        ? $user->notifications()->take(30)->get() 
        : collect();

    // Filtrar de forma defensiva notificaciones de expedientes que ya no existen en el sistema
    $notifications = $rawNotifications->filter(function ($notif) {
        $data = $notif->data ?? [];
        $folio = $data['folio'] ?? null;
        $incidentId = $data['incident_id'] ?? null;

        if ($folio && !\App\Models\Incident::where('folio', $folio)->exists()) {
            return false;
        }
        if ($incidentId && !\App\Models\Incident::where('id', $incidentId)->exists()) {
            return false;
        }

        return true;
    })->take(15);

    $unreadCount = $notifications->whereNull('read_at')->count();
@endphp

<div 
    x-data="{ 
        openNotif: false,
        markAsRead(id, targetUrl = null) {
            fetch('{{ url('/notificaciones') }}/' + id + '/leer', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                if (targetUrl && targetUrl !== '#') {
                    window.location.href = targetUrl;
                } else {
                    window.location.reload();
                }
            }).catch(() => {
                if (targetUrl && targetUrl !== '#') {
                    window.location.href = targetUrl;
                }
            });
        },
        markAllAsRead() {
            fetch('{{ route('notifications.mark-all-as-read') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                window.location.reload();
            });
        }
    }" 
    class="relative"
>
    <!-- Botón de la Campana de Alertas -->
    <button 
        @click="openNotif = !openNotif" 
        type="button"
        class="relative p-2 rounded-lg text-[#8D98A8] hover:text-[#F2F5F8] hover:bg-[#11151B] transition-colors focus:outline-none"
        title="Centro de Notificaciones GTS"
        aria-label="Notificaciones"
        :aria-expanded="openNotif"
    >
        <span class="material-symbols-outlined text-[20px]">notifications</span>
        
        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#D83B52] text-white text-[10px] font-bold flex items-center justify-center font-mono ring-2 ring-[#070A0F] animate-pulse">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Panel Flotante / Dropdown de Notificaciones -->
    <div 
        x-show="openNotif"
        x-cloak
        @click.away="openNotif = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="transform opacity-0 scale-95 -translate-y-1"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-[#11151B] border border-[#252C35] rounded-2xl shadow-enterprise z-50 overflow-hidden flex flex-col"
        style="display: none;"
    >
        <!-- Encabezado del Panel -->
        <div class="px-4 py-3 border-b border-[#1B2129] flex items-center justify-between bg-[#0D1117]">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-[#F2F5F8] tracking-wide">Notificaciones</span>
                @if($unreadCount > 0)
                    <span class="px-2 py-0.5 text-[10px] font-semibold font-mono rounded-full bg-[#D83B52]/20 text-[#E66A7A] border border-[#D83B52]/30">
                        {{ $unreadCount }} nuevas
                    </span>
                @endif
            </div>

            @if($unreadCount > 0)
                <button 
                    @click="markAllAsRead()"
                    type="button" 
                    class="text-[11px] font-medium text-[#3D82C6] hover:text-[#5B9FE0] transition-colors focus:outline-none flex items-center gap-1"
                    title="Marcar todas como leídas"
                >
                    <span class="material-symbols-outlined text-[14px]">done_all</span>
                    <span>Marcar todas</span>
                </button>
            @endif
        </div>

        <!-- Lista de Notificaciones con Scroll Vertical -->
        <div class="max-h-80 overflow-y-auto divide-y divide-[#1B2129] scrollbar-thin">
            @forelse($notifications as $notif)
                @php
                    $isUnread = is_null($notif->read_at);
                    $data = $notif->data ?? [];
                    $level = $data['level'] ?? 'info';
                    $icon = $data['icon'] ?? 'notifications';
                    $title = $data['title'] ?? 'Alerta Operacional';
                    $message = $data['message'] ?? '';
                    $folio = $data['folio'] ?? null;
                    $actionUrl = $data['action_url'] ?? '#';

                    $levelColors = [
                        'critical' => ['bg' => 'bg-[#D83B52]/15', 'text' => 'text-[#E66A7A]', 'border' => 'border-[#D83B52]/30'],
                        'warning'  => ['bg' => 'bg-[#E0A21A]/15', 'text' => 'text-[#E2B24C]', 'border' => 'border-[#E0A21A]/30'],
                        'success'  => ['bg' => 'bg-[#20C997]/15', 'text' => 'text-[#20C997]', 'border' => 'border-[#20C997]/30'],
                        'info'     => ['bg' => 'bg-[#123A70]/30', 'text' => 'text-[#3D82C6]', 'border' => 'border-[#164A8A]/40'],
                    ][$level] ?? ['bg' => 'bg-[#123A70]/30', 'text' => 'text-[#3D82C6]', 'border' => 'border-[#164A8A]/40'];
                @endphp

                <div class="p-3 transition-colors flex items-start gap-3 group {{ $isUnread ? 'bg-[#151A21]/70 hover:bg-[#18202A]' : 'bg-transparent hover:bg-[#151B23]' }}">
                    <!-- Icono Táctico -->
                    <div class="w-7 h-7 rounded-lg {{ $levelColors['bg'] }} {{ $levelColors['text'] }} border {{ $levelColors['border'] }} flex items-center justify-center shrink-0 mt-0.5">
                        <span class="material-symbols-outlined text-[16px]">{{ $icon }}</span>
                    </div>

                    <!-- Contenido Textual -->
                    <div 
                        @click="markAsRead('{{ $notif->id }}', '{{ $actionUrl }}')"
                        class="flex-1 min-w-0 cursor-pointer text-left"
                    >
                        <div class="flex items-center justify-between gap-1 mb-0.5">
                            <span class="text-xs font-semibold text-[#F2F5F8] truncate leading-tight group-hover:text-[#3D82C6] transition-colors">
                                {{ $title }}
                            </span>
                            <span class="text-[10px] font-mono text-[#778394] shrink-0">
                                {{ $notif->created_at->diffForHumans(null, true, true) }}
                            </span>
                        </div>

                        <p class="text-[11px] text-[#A8B0BC] leading-snug line-clamp-2">
                            {{ $message }}
                        </p>

                        @if($folio)
                            <div class="mt-1 flex items-center gap-2">
                                <span class="text-[10px] font-mono font-bold text-[#3D82C6] bg-[#123A70]/30 px-1.5 py-0.5 rounded border border-[#164A8A]/40">
                                    {{ $folio }}
                                </span>
                                @if($isUnread)
                                    <span class="inline-flex items-center gap-1 text-[9px] font-medium text-[#E2B24C]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#E0A21A]"></span>
                                        Sin leer
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Acción rápida: marcar leída sin redirigir -->
                    @if($isUnread)
                        <button 
                            @click.stop="markAsRead('{{ $notif->id }}')"
                            type="button" 
                            class="p-1 rounded text-[#778394] hover:text-[#20C997] hover:bg-[#11151B] transition-colors shrink-0 focus:outline-none"
                            title="Marcar como leída"
                        >
                            <span class="material-symbols-outlined text-[15px]">check</span>
                        </button>
                    @endif
                </div>
            @empty
                <!-- Estado Vacío -->
                <div class="py-8 px-4 text-center flex flex-col items-center justify-center gap-2 text-[#778394]">
                    <div class="w-10 h-10 rounded-full bg-[#0D1117] border border-[#252C35] flex items-center justify-center text-[#8D98A8]">
                        <span class="material-symbols-outlined text-[20px]">notifications_paused</span>
                    </div>
                    <p class="text-xs font-medium text-[#F2F5F8]">Sin notificaciones pendientes</p>
                    <p class="text-[11px] text-[#707B8A] max-w-[220px]">
                        Las alertas de asignación, resolución y cambios de estado aparecerán aquí.
                    </p>
                </div>
            @endforelse
        </div>

        <!-- Pie del Dropdown -->
        <div class="px-4 py-2 bg-[#0D1117] border-t border-[#1B2129] flex items-center justify-between text-[10px] font-mono text-[#778394]">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-[#20C997]"></span>
                <span>GTS • Canal Operacional Seguro</span>
            </span>
            <span class="text-[#8D98A8] font-sans">
                {{ auth()->user()->isGts() ? 'Perfil GTS' : (auth()->user()->isApoc() ? 'Perfil APOC' : (auth()->user()->isAdmin() ? 'Perfil Admin' : 'Perfil ' . (auth()->user()->role?->name ?? 'Usuario'))) }}
            </span>
        </div>
    </div>
</div>
