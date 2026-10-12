@if($recentIncidents->isNotEmpty())
    @foreach($recentIncidents->take(5) as $inc)
        <tr 
            onclick="window.location='{{ route('incidents.show', $inc->folio) }}'"
            class="hover:bg-[#151B23] cursor-pointer transition-colors group"
            title="Ver expediente {{ $inc->folio }}"
        >
            <td class="py-1.5 px-3.5 font-mono font-bold text-[#3D82C6] whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('incidents.show', $inc->folio) }}" class="group-hover:underline">
                        {{ $inc->folio }}
                    </a>
                    @if($badge = $inc->origin_badge)
                        <span class="inline-flex items-center px-1 py-0.2 rounded text-[8.5px] font-bold font-mono tracking-wider {{ $badge['bg'] }} {{ $badge['text'] }} border {{ $badge['border'] }}" title="{{ $badge['title'] }}">{{ $badge['label'] }}</span>
                    @endif
                </div>
            </td>
            <td class="py-1.5 px-3.5 text-[#7E8998] font-mono whitespace-nowrap">
                {{ $inc->created_at->format('d/m/Y H:i') }}
            </td>
            <td class="py-1.5 px-3.5 whitespace-nowrap">
                <span class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[15px] text-[#707B8A]">
                        {{ $inc->category?->slug === 'cctv' ? 'videocam' : ($inc->category?->slug === 'control-acceso' ? 'door_front' : ($inc->category?->slug === 'fids' ? 'tv' : 'radio')) }}
                    </span>
                    <span>{{ $inc->category?->name ?? 'CCTV' }}</span>
                </span>
            </td>
            <td class="py-1.5 px-3.5 whitespace-nowrap">
                <x-badge-status :status="$inc->status" />
            </td>
            <td class="py-1.5 px-3.5 whitespace-nowrap">
                <x-badge-priority :priority="$inc->priority" />
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="5" class="py-6 text-center text-xs text-[#7E8998]">
            No hay incidencias registradas en el sistema.
        </td>
    </tr>
@endif

