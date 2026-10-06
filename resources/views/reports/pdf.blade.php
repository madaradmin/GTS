<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Expediente Técnico GTS AIFA - {{ $reportFolio }}</title>
    <style>
        @page {
            margin-top: 32mm;
            margin-bottom: 12mm;
            margin-left: 12mm;
            margin-right: 12mm;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5px;
            color: #0F172A;
            margin: 0;
            padding: 0;
            line-height: 1.3;
            background-color: #FFFFFF;
        }
        .incident-sheet {
            width: 100%;
        }
        .page-break {
            page-break-after: always;
        }

        /* Encabezado Institucional Permanente en Todas las Hojas */
        header {
            position: fixed;
            top: -26mm;
            left: 0;
            right: 0;
            height: 22mm;
            border-bottom: 2px solid #0F172A;
            padding-bottom: 3px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-left {
            width: 65px;
            vertical-align: middle;
            text-align: left;
        }
        .header-logo-gts {
            height: 48px;
            width: auto;
            max-width: 60px;
            display: block;
        }
        .header-center {
            vertical-align: middle;
            text-align: center;
            padding: 0 8px;
        }
        .header-logo-aifa {
            height: 28px;
            width: auto;
            max-width: 120px;
            display: block;
            margin: 0 auto 2px auto;
        }
        .inst-title {
            font-size: 8.5px;
            font-weight: bold;
            color: #0F172A;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .inst-sub {
            font-size: 7.5px;
            font-weight: bold;
            color: #1D4ED8;
            letter-spacing: 0.02em;
            line-height: 1.15;
            margin-top: 1px;
        }
        .inst-dept {
            font-size: 6.5px;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            line-height: 1.15;
            margin-top: 1px;
        }
        .header-right {
            width: 195px;
            vertical-align: middle;
            text-align: right;
        }
        .control-box {
            border: 1px solid #CBD5E1;
            background-color: #F8FAFC;
            padding: 3px 6px;
            border-radius: 4px;
            display: inline-block;
            text-align: left;
            width: 100%;
        }
        .control-row {
            font-size: 7px;
            margin-bottom: 1px;
        }
        .control-label {
            color: #64748B;
            font-weight: normal;
        }
        .control-val {
            color: #0F172A;
            font-weight: bold;
            font-family: 'Courier', monospace;
        }
        .classification-badge {
            display: inline-block;
            background-color: #0F172A;
            color: #FFFFFF;
            font-size: 6.5px;
            font-weight: bold;
            padding: 1.5px 5px;
            border-radius: 2px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-top: 1px;
        }

        /* Banner de Identificación de Ficha */
        .incident-title-bar {
            background-color: #0F172A;
            color: #FFFFFF;
            padding: 5px 8px;
            border-radius: 3px;
            margin-bottom: 7px;
            width: 100%;
        }
        .incident-folio-large {
            font-size: 11px;
            font-weight: bold;
            font-family: 'Courier', monospace;
            letter-spacing: 0.05em;
            color: #93C5FD;
        }
        .incident-status-tag {
            float: right;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 2px;
            background-color: #1E293B;
            color: #F8FAFC;
            border: 1px solid #334155;
        }

        /* Secciones y Bloques de Información */
        .section-box {
            margin-bottom: 6px;
            border: 1px solid #E2E8F0;
            border-radius: 3px;
            overflow: hidden;
        }
        .section-header {
            background-color: #F1F5F9;
            border-bottom: 1px solid #CBD5E1;
            padding: 3px 6px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #334155;
            letter-spacing: 0.04em;
        }
        .section-body {
            padding: 5px 7px;
            background-color: #FFFFFF;
        }

        /* Tablas de Metadatos Clave-Valor */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
        }
        .grid-table td {
            padding: 2.5px 4px;
            vertical-align: top;
            font-size: 7.5px;
        }
        .field-label {
            color: #64748B;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            width: 25%;
        }
        .field-value {
            color: #0F172A;
            font-weight: normal;
        }
        .field-value strong {
            color: #0F172A;
        }

        /* Badges de Prioridad */
        .badge-priority {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge-priority-critica {
            background-color: #FEE2E2;
            color: #B91C1C;
            border: 1px solid #FCA5A5;
        }
        .badge-priority-alta {
            background-color: #FFEDD5;
            color: #C2410C;
            border: 1px solid #FDBA74;
        }
        .badge-priority-media {
            background-color: #D1FAE5;
            color: #065F46;
            border: 1px solid #6EE7B7;
        }
        .badge-priority-baja {
            background-color: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
        }

        /* Badges de Estado */
        .badge-status {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-status-nueva {
            background-color: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
        }
        .badge-status-asignada {
            background-color: #F5F3FF;
            color: #6D28D9;
            border: 1px solid #DDD6FE;
        }
        .badge-status-en_atencion {
            background-color: #FEF3C7;
            color: #B45309;
            border: 1px solid #FDE68A;
        }
        .badge-status-resuelta {
            background-color: #ECFDF5;
            color: #047857;
            border: 1px solid #A7F3D0;
        }
        .badge-status-cerrada {
            background-color: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
        }

        /* Tabla de Trazabilidad */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        .history-table th {
            background-color: #F8FAFC;
            color: #475569;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 5px;
            border: 1px solid #E2E8F0;
            text-align: left;
        }
        .history-table td {
            padding: 3px 5px;
            border: 1px solid #E2E8F0;
            font-size: 7.2px;
            vertical-align: top;
        }
        .history-table tr:nth-child(even) td {
            background-color: #F8FAFC;
        }
        .action-tag {
            font-weight: bold;
            color: #1E293B;
            font-size: 7px;
        }
        .history-date {
            font-family: 'Courier', monospace;
            color: #475569;
            white-space: nowrap;
            font-size: 7px;
        }

        /* Texto y Cajas de Solución */
        .text-box {
            font-size: 7.5px;
            line-height: 1.35;
            color: #1E293B;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 3px;
            padding: 4px 6px;
            margin-top: 2px;
            white-space: pre-wrap;
        }

        /* Firmas de Conformidad */
        .signatures-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .signature-cell {
            width: 48%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }
        .signature-line {
            border-top: 1px solid #475569;
            margin-bottom: 3px;
        }
        .signature-name {
            font-size: 7.5px;
            font-weight: bold;
            color: #0F172A;
        }
        .signature-role {
            font-size: 6.5px;
            color: #64748B;
            text-transform: uppercase;
        }

        /* Footer */
        .footer-note {
            margin-top: 8px;
            border-top: 1px solid #E2E8F0;
            padding-top: 3px;
            font-size: 6.5px;
            color: #64748B;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $gtsLogoSrc = $gtsLogoBase64 ?? (file_exists(public_path('images/gts-logo.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/gts-logo.png'))) : '');
        $aifaLogoSrc = $aifaLogoBase64 ?? (file_exists(public_path('images/aifa-logo.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/aifa-logo.png'))) : '');
    @endphp

    <!-- Encabezado Institucional AIFA & GTS Permanente en Todas las Hojas -->
    <header>
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <!-- 1. Logo GTS (Parte Superior Izquierda) -->
                <td class="header-left">
                    @if($gtsLogoSrc)
                        <img src="{{ $gtsLogoSrc }}" class="header-logo-gts" alt="Logo GTS">
                    @endif
                </td>

                <!-- 2. Logo AIFA (Centrado) y Títulos Institucionales -->
                <td class="header-center">
                    @if($aifaLogoSrc)
                        <img src="{{ $aifaLogoSrc }}" class="header-logo-aifa" alt="Logo AIFA">
                    @endif
                    <div class="inst-title">Aeropuerto Internacional Felipe Ángeles</div>
                    <div class="inst-sub">GTS — Grupo de Seguridad Tecnológica</div>
                    <div class="inst-dept">Expediente Técnico Pericial y Dictamen de Falla Tecnológica</div>
                </td>

                <!-- 3. Metadatos de Control Operativo -->
                <td class="header-right">
                    <div class="control-box">
                        <div class="control-row"><span class="control-label">Folio Reporte:</span> <span class="control-val">{{ $reportFolio }}</span></div>
                        <div class="control-row"><span class="control-label">Emisión:</span> <span class="control-val">{{ $generatedAt }}</span></div>
                        <div class="control-row"><span class="control-label">Solicitante:</span> <span class="control-val">{{ $requester->name }} ({{ $requester->badge_number ?? 'S/N' }})</span></div>
                        <div style="text-align: right;">
                            <span class="classification-badge">Seguridad Operacional • AIFA</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </header>

    @forelse($incidents as $index => $inc)
        <div class="incident-sheet @if(!$loop->last) page-break @endif">

            <!-- Barra de Identificación Destacada de la Incidencia -->
            <div class="incident-title-bar">
                <span class="incident-folio-large">EXPEDIENTE: {{ $inc->folio }}</span>
                @if($inc->isFromCco())
                    <span style="font-size: 8px; font-weight: bold; color: #B45309; background-color: #FEF3C7; border: 1px solid #FCD34D; padding: 1px 4px; border-radius: 2px; margin-left: 5px;">ORIGEN: CCO</span>
                @elseif($inc->isFromCsa())
                    <span style="font-size: 8px; font-weight: bold; color: #C2410C; background-color: #FFEDD5; border: 1px solid #FDBA74; padding: 1px 4px; border-radius: 2px; margin-left: 5px;">ORIGEN: CSA</span>
                @endif
                <span style="font-size: 8px; color: #CBD5E1; margin-left: 8px;">
                    Registrado: {{ $inc->created_at ? $inc->created_at->format('d/m/Y H:i:s') : 'N/D' }}
                </span>
                <span class="incident-status-tag">
                    ESTADO: {{ $inc->status->name ?? 'NUEVA' }}
                </span>
            </div>

            <!-- Bloque 1 y 2: Identificación y Clasificación Técnica -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
                <tr>
                    <!-- Identificación -->
                    <td style="width: 50%; vertical-align: top; padding-right: 4px;">
                        <div class="section-box">
                            <div class="section-header">1. Identificación y Generales</div>
                            <div class="section-body">
                                <table class="grid-table">
                                    <tr>
                                        <td class="field-label">Folio:</td>
                                        <td class="field-value"><strong>{{ $inc->folio }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="field-label">Apertura:</td>
                                        <td class="field-value">{{ $inc->created_at ? $inc->created_at->format('d/m/Y H:i:s') : 'N/D' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="field-label">Reportante:</td>
                                        <td class="field-value">
                                            {{ $inc->reportedBy->name ?? 'Sistema Automático' }}
                                            @if($inc->reportedBy?->badge_number)
                                                ({{ $inc->reportedBy->badge_number }})
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="field-label">Procedencia:</td>
                                        <td class="field-value">
                                            <strong>
                                                @if($inc->isFromCco())
                                                    APOC-CCO (Centro de Control Operativo)
                                                @elseif($inc->isFromCsa())
                                                    APOC-CSA (Centro de Servicios Aeroportuarios)
                                                @else
                                                    {{ $inc->origin_group ?? ($inc->reportedBy->role->name ?? 'GTS') }}
                                                @endif
                                            </strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="field-label">Rol Emisor:</td>
                                        <td class="field-value">{{ $inc->reportedBy->role->name ?? 'APOC Operador' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </td>

                    <!-- Clasificación -->
                    <td style="width: 50%; vertical-align: top; padding-left: 4px;">
                        <div class="section-box">
                            <div class="section-header">2. Clasificación Técnica y Tiempo Estimado</div>
                            <div class="section-body">
                                <table class="grid-table">
                                    <tr>
                                        <td class="field-label">Subsistema:</td>
                                        <td class="field-value"><strong>{{ $inc->category->name ?? 'Sin asignar' }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="field-label">Ubicación:</td>
                                        <td class="field-value">{{ $inc->location ?? 'No especificada' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="field-label">Prioridad:</td>
                                        <td class="field-value">
                                            @php $pSlug = $inc->priority->slug ?? 'media'; @endphp
                                            <span class="badge-priority badge-priority-{{ $pSlug }}">
                                                {{ $inc->priority->name ?? 'Media' }}
                                            </span>
                                            @if($inc->priority?->sla_resolution_hours)
                                                <span style="color: #64748B; font-size: 6.8px;">(Tiempo estimado: {{ $inc->priority->sla_resolution_hours }} hrs)</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="field-label">Estado Actual:</td>
                                        <td class="field-value">
                                            @php $sCode = strtolower($inc->status->code ?? 'nueva'); @endphp
                                            <span class="badge-status badge-status-{{ $sCode }}">
                                                {{ $inc->status->name ?? 'Nueva' }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Bloque 3: Ciclo de Atención y Tiempos Operacionales -->
            <div class="section-box">
                <div class="section-header">3. Ciclo de Atención y Cronometría Operativa</div>
                <div class="section-body">
                    <table class="grid-table">
                        <tr>
                            <td class="field-label" style="width: 20%;">Especialista Asignado:</td>
                            <td class="field-value" style="width: 30%;">
                                @if($inc->isAssignedToTics())
                                    <strong>TICS</strong> (Área Tecnológica)
                                @elseif($inc->assignedTo)
                                    <strong>{{ $inc->assignedTo->name }}</strong>
                                    @if($inc->assignedTo->badge_number)
                                        ({{ $inc->assignedTo->badge_number }})
                                    @endif
                                @else
                                    <strong>Sin asignar a cuadrilla</strong>
                                @endif
                            </td>
                            <td class="field-label" style="width: 20%;">Fecha Asignación:</td>
                            <td class="field-value" style="width: 30%;">
                                {{ $inc->assigned_at ? $inc->assigned_at->format('d/m/Y H:i:s') : 'Pendiente' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="field-label">Primera Atención:</td>
                            <td class="field-value">
                                {{ $inc->attended_at ? $inc->attended_at->format('d/m/Y H:i:s') : 'Sin registro de campo' }}
                            </td>
                            <td class="field-label">Resolución Técnica:</td>
                            <td class="field-value">
                                {{ $inc->resolved_at ? $inc->resolved_at->format('d/m/Y H:i:s') : 'En proceso técnico' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="field-label">Cierre Formal (VoBo):</td>
                            <td class="field-value">
                                {{ $inc->closed_at ? $inc->closed_at->format('d/m/Y H:i:s') : 'Pendiente de VoBo APOC' }}
                            </td>
                            <td class="field-label">Tiempo Total Solución:</td>
                            <td class="field-value">
                                @if($inc->created_at && $inc->resolved_at)
                                    @php
                                        $diffMinutes = $inc->created_at->diffInMinutes($inc->resolved_at);
                                        $hrs = intdiv($diffMinutes, 60);
                                        $mins = $diffMinutes % 60;
                                        $timeStr = $hrs > 0 ? "{$hrs}h {$mins}m" : "{$mins} min";
                                    @endphp
                                    <strong>{{ $timeStr }}</strong>
                                    @if($inc->priority?->sla_resolution_hours)
                                        @if($diffMinutes <= ($inc->priority->sla_resolution_hours * 60))
                                            <span style="color: #059669; font-weight: bold;">(En tiempo estimado)</span>
                                        @else
                                            <span style="color: #DC2626; font-weight: bold;">(Tiempo excedido)</span>
                                        @endif
                                    @endif
                                @else
                                    <span style="color: #64748B;">En curso</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Bloque 4: Descripción Original de la Falla -->
            <div class="section-box">
                <div class="section-header">4. Descripción Operativa del Reporte Original</div>
                <div class="section-body">
                    <div style="font-weight: bold; color: #0F172A; font-size: 8px; margin-bottom: 2px;">
                        Falla: {{ $inc->title }}
                    </div>
                    <div class="text-box">
                        {{ $inc->description ?? 'Sin descripción adicional registrada.' }}
                    </div>
                </div>
            </div>

            <!-- Bloque 5: Diagnóstico y Resolución Técnica -->
            <div class="section-box">
                <div class="section-header">5. Diagnóstico y Dictamen de Solución Técnica</div>
                <div class="section-body">
                    <div class="text-box">
                        @if(!empty($inc->solution_description))
                            {{ $inc->solution_description }}
                        @else
                            <em style="color: #64748B;">La incidencia no cuenta con solución definitiva registrada. Continúa en protocolo técnico de atención.</em>
                        @endif
                    </div>
                    @if(!empty($inc->rejection_reason))
                        <div style="margin-top: 4px; padding: 4px 6px; background-color: #FEF2F2; border: 1px solid #FECACA; border-radius: 3px; font-size: 7px; color: #991B1B;">
                            <strong>Motivo de rechazo de solución previo por APOC:</strong> {{ $inc->rejection_reason }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Bloque 6: Bitácora de Trazabilidad Pericial (Historial Real de Eventos) -->
            <div class="section-box">
                <div class="section-header">
                    6. Bitácora de Trazabilidad Cronológica ({{ $inc->histories->count() }} Eventos Registrados)
                </div>
                <div class="section-body" style="padding: 2px 4px;">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th style="width: 19%;">Fecha / Hora</th>
                                <th style="width: 18%;">Usuario Responsable</th>
                                <th style="width: 21%;">Acción Registrada</th>
                                <th style="width: 42%;">Detalle / Comentario de Campo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inc->histories as $history)
                                <tr>
                                    <td class="history-date">
                                        {{ $history->created_at ? $history->created_at->format('d/m/Y H:i:s') : 'N/D' }}
                                    </td>
                                    <td>
                                        <strong>{{ $history->user->name ?? 'Sistema' }}</strong>
                                        @if($history->user?->role)
                                            <span style="color: #64748B; font-size: 6.5px;">({{ $history->user->role->name }})</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="action-tag">{{ $history->getActionLabel() }}</span>
                                    </td>
                                    <td>
                                        @if(!empty($history->comment))
                                            {{ $history->comment }}
                                        @elseif(!empty($history->field_name))
                                            <em>Cambio en {{ $history->field_name }}: {{ $history->old_value ?? 'N/D' }} &rarr; {{ $history->new_value ?? 'N/D' }}</em>
                                        @else
                                            <span style="color: #94A3B8;">Acción ejecutada sin nota adicional</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #64748B; padding: 6px;">
                                        Sin registros adicionales en la bitácora pericial de trazabilidad.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bloque 7: Evidencias y Archivos Adjuntos Asociados -->
            <div class="section-box">
                <div class="section-header">7. Evidencias Fotográficas y Documentación Adjunta</div>
                <div class="section-body">
                    @if($inc->attachments && $inc->attachments->isNotEmpty())
                        <table class="grid-table">
                            @foreach($inc->attachments as $att)
                                <tr>
                                    <td style="width: 4%; font-family: monospace;">📎</td>
                                    <td style="width: 46%; font-weight: bold; color: #0F172A;">
                                        {{ $att->original_name }}
                                    </td>
                                    <td style="width: 25%; color: #64748B;">
                                        {{ round(($att->file_size ?? 0) / 1024, 1) }} KB ({{ $att->mime_type ?? 'archivo' }})
                                    </td>
                                    <td style="width: 25%; color: #64748B;">
                                        Cargado por: {{ $att->user->name ?? 'Usuario' }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <span style="color: #64748B; font-size: 7.2px;">No se registraron archivos o evidencias fotográficas anexas a este expediente.</span>
                    @endif
                </div>
            </div>

            <!-- Firmas Periciales de Conformidad -->
            <table class="signatures-table">
                <tr>
                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $inc->isAssignedToTics() ? 'Especialista TICS' : ($inc->assignedTo->name ?? 'Técnico Especialista GTS') }}</div>
                        <div class="signature-role">{{ $inc->isAssignedToTics() ? 'Área de Tecnologías de Información y Comunicaciones' : 'Responsable de Atención / Cuadrilla GTS' }}</div>
                    </td>
                    <td class="signature-cell">
                        <div class="signature-line"></div>
                        <div class="signature-name">{{ $inc->reportedBy->name ?? 'Centro APOC' }}</div>
                        <div class="signature-role">Supervisión y Conformidad Operacional APOC</div>
                    </td>
                </tr>
            </table>

            <div class="footer-note">
                Ficha Oficial de Incidencia Técnica emitida por el Sistema de Gestión Tecnológica (GTS) del Aeropuerto Internacional Felipe Ángeles.<br>
                Información clasificada para fines de trazabilidad operativa y mantenimiento de infraestructuras críticas. Control: {{ $reportFolio }} • {{ $inc->folio }}
            </div>

        </div>
    @empty
        <div style="text-align: center; padding: 40px; font-family: sans-serif;">
            <h2 style="color: #0F172A;">No se encontraron incidencias</h2>
            <p style="color: #64748B; font-size: 11px;">
                No se seleccionó ninguna incidencia o los criterios de filtrado actuales no arrojaron resultados en la base de datos.
            </p>
        </div>
    @endforelse

    <!-- Script DomPDF para Numeración Institucional -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 7;
            $color = array(0.35, 0.4, 0.45);
            $pdf->page_text(510, 820, $text, $font, $size, $color);
            $pdf->page_text(35, 820, "AIFA • Grupo de Seguridad Tecnológica | Folio Reporte: {{ $reportFolio }}", $font, $size, $color);
        }
    </script>
</body>
</html>
