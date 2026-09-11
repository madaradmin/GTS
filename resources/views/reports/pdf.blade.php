<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Operativo GTS AIFA - {{ $reportFolio }}</title>
    <style>
        @page {
            margin: 12mm 14mm 16mm 14mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #1A202C;
            margin: 0;
            padding: 0;
            line-height: 1.35;
            background-color: #FFFFFF;
        }
        /* Header institucional */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1E293B;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-logo-title {
            vertical-align: top;
            text-align: left;
        }
        .inst-title {
            font-size: 13px;
            font-weight: bold;
            color: #0F172A;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .inst-sub {
            font-size: 10px;
            font-weight: bold;
            color: #2563EB;
            letter-spacing: 0.04em;
        }
        .inst-dept {
            font-size: 8px;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 2px;
        }
        .header-meta {
            vertical-align: top;
            text-align: right;
            width: 250px;
        }
        .control-box {
            border: 1px solid #CBD5E1;
            background-color: #F8FAFC;
            padding: 6px 10px;
            border-radius: 4px;
            display: inline-block;
            text-align: left;
            width: 100%;
        }
        .control-row {
            font-size: 8.5px;
            margin-bottom: 2px;
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
            font-size: 7.5px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 3px;
        }

        /* Sección de Filtros */
        .filter-section {
            background-color: #F1F5F9;
            border: 1px solid #E2E8F0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 12px;
        }
        .filter-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 4px;
            letter-spacing: 0.05em;
        }
        .filter-tags {
            font-size: 8.5px;
        }
        .filter-tag-item {
            display: inline-block;
            margin-right: 14px;
            color: #1E293B;
        }
        .filter-tag-item strong {
            color: #475569;
        }

        /* Resumen Ejecutivo KPI */
        .summary-title-main {
            font-size: 9.5px;
            font-weight: bold;
            color: #0F172A;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary-table td {
            border: 1px solid #CBD5E1;
            padding: 6px 8px;
            text-align: center;
            background-color: #FFFFFF;
        }
        .summary-table th {
            border: 1px solid #CBD5E1;
            padding: 4px 6px;
            font-size: 8px;
            text-transform: uppercase;
            background-color: #F8FAFC;
            color: #475569;
            font-weight: bold;
        }
        .summary-kpi-val {
            font-size: 14px;
            font-weight: bold;
            font-family: 'Courier', monospace;
            color: #0F172A;
        }

        /* Tabla de Incidencias */
        .incidents-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .incidents-table th {
            background-color: #0F172A;
            color: #F8FAFC;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 5px 6px;
            text-align: left;
            border: 1px solid #0F172A;
        }
        .incidents-table td {
            padding: 5px 6px;
            border: 1px solid #E2E8F0;
            font-size: 8px;
            vertical-align: middle;
        }
        .incidents-table tr:nth-child(even) td {
            background-color: #F8FAFC;
        }
        .folio-cell {
            font-family: 'Courier', monospace;
            font-weight: bold;
            color: #1D4ED8;
            white-space: nowrap;
        }
        .date-cell {
            white-space: nowrap;
            color: #475569;
            font-family: 'Courier', monospace;
            font-size: 7.5px;
        }
        
        /* Badges de Prioridad (Paso 4 Colores) */
        .badge-priority {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-priority-critica {
            background-color: #FEE2E2;
            color: #D83B52;
            border: 1px solid #FCA5A5;
        }
        .badge-priority-alta {
            background-color: #FFEDD5;
            color: #E06D24;
            border: 1px solid #FDBA74;
        }
        .badge-priority-media {
            background-color: #FEF3C7;
            color: #B45309;
            border: 1px solid #FCD34D;
        }
        .badge-priority-baja {
            background-color: #EFF6FF;
            color: #5B86B4;
            border: 1px solid #BFDBFE;
        }

        /* Badges de Estado */
        .badge-status {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-status-nueva {
            background-color: #EFF6FF;
            color: #2563EB;
            border: 1px solid #BFDBFE;
        }
        .badge-status-asignada {
            background-color: #F5F3FF;
            color: #7C3AED;
            border: 1px solid #DDD6FE;
        }
        .badge-status-en_atencion {
            background-color: #FEF3C7;
            color: #D97706;
            border: 1px solid #FDE68A;
        }
        .badge-status-resuelta {
            background-color: #ECFDF5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        .badge-status-cerrada {
            background-color: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
        }

        /* Footer institucional */
        .footer-note {
            margin-top: 14px;
            border-top: 1px solid #CBD5E1;
            padding-top: 6px;
            font-size: 7px;
            color: #64748B;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Encabezado Institucional -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="header-logo-title">
                <div class="inst-title">Aeropuerto Internacional Felipe Ángeles</div>
                <div class="inst-sub">GTS — Grupo de Seguridad Tecnológica</div>
                <div class="inst-dept">Centro de Mando Operativo • Informe Ejecutivo Pericial y de Cumplimiento Técnico</div>
            </td>
            <td class="header-meta">
                <div class="control-box">
                    <div class="control-row"><span class="control-label">Folio Reporte:</span> <span class="control-val">{{ $reportFolio }}</span></div>
                    <div class="control-row"><span class="control-label">Emisión:</span> <span class="control-val">{{ $generatedAt }}</span></div>
                    <div class="control-row"><span class="control-label">Solicitante:</span> <span class="control-val">{{ $requester->name }} ({{ $requester->badge_number ?? 'S/N' }})</span></div>
                    <div style="text-align: right;">
                        <span class="classification-badge">Seguridad Operacional</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Filtros de Consulta Aplicados -->
    <div class="filter-section">
        <div class="filter-title">Parámetros y Criterios del Reporte</div>
        <div class="filter-tags">
            @foreach($appliedFilters as $label => $val)
                <span class="filter-tag-item"><strong>{{ $label }}:</strong> {{ $val }}</span>
            @endforeach
        </div>
    </div>

    <!-- Resumen Ejecutivo Previo -->
    <div class="summary-title-main">Resumen Ejecutivo y Métricas Operativas</div>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Total Incidencias</th>
                <th>Nuevas</th>
                <th>Asignadas</th>
                <th>En Atención</th>
                <th>Resueltas</th>
                <th>Cerradas</th>
                <th>Tasa Eficacia</th>
                <th>MTTR Promedio</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="summary-kpi-val">{{ $summary['total'] }}</span></td>
                <td><span class="summary-kpi-val" style="color: #2563EB;">{{ $summary['by_status']['NUEVA'] ?? 0 }}</span></td>
                <td><span class="summary-kpi-val" style="color: #7C3AED;">{{ $summary['by_status']['ASIGNADA'] ?? 0 }}</span></td>
                <td><span class="summary-kpi-val" style="color: #D97706;">{{ $summary['by_status']['EN_ATENCION'] ?? 0 }}</span></td>
                <td><span class="summary-kpi-val" style="color: #059669;">{{ $summary['by_status']['RESUELTA'] ?? 0 }}</span></td>
                <td><span class="summary-kpi-val" style="color: #475569;">{{ $summary['by_status']['CERRADA'] ?? 0 }}</span></td>
                <td><span class="summary-kpi-val" style="color: #0284C7;">{{ $summary['resolution_rate'] }}%</span></td>
                <td><span class="summary-kpi-val" style="color: #0F172A;">{{ $summary['avg_resolution_time_formatted'] }}</span></td>
            </tr>
        </tbody>
    </table>

    <!-- Tabla Detallada de Incidencias -->
    <div class="summary-title-main" style="margin-top: 8px;">
        Relación Nominal de Incidencias Registradas ({{ $incidents->count() }})
    </div>
    <table class="incidents-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 11%;">Folio</th>
                <th style="width: 11%;">Fecha/Hora</th>
                <th style="width: 14%;">Subsistema</th>
                <th style="width: 14%;">Ubicación</th>
                <th style="width: 21%;">Falla / Título</th>
                <th style="width: 9%; text-align: center;">Prioridad</th>
                <th style="width: 9%; text-align: center;">Estado</th>
                <th style="width: 11%;">Especialista GTS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incidents as $inc)
                <tr>
                    <td class="folio-cell">{{ $inc->folio }}</td>
                    <td class="date-cell">{{ $inc->created_at ? $inc->created_at->format('d/m/Y H:i') : 'N/D' }}</td>
                    <td><strong>{{ $inc->category->name ?? 'Sin categoría' }}</strong></td>
                    <td>{{ Str::limit($inc->location, 28) }}</td>
                    <td>{{ Str::limit($inc->title, 42) }}</td>
                    <td style="text-align: center;">
                        @php
                            $pSlug = $inc->priority->slug ?? 'media';
                        @endphp
                        <span class="badge-priority badge-priority-{{ $pSlug }}">
                            {{ $inc->priority->name ?? 'Media' }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        @php
                            $statusCode = strtolower($inc->status->code ?? 'nueva');
                        @endphp
                        <span class="badge-status badge-status-{{ $statusCode }}">
                            {{ $inc->status->name ?? 'Nueva' }}
                        </span>
                    </td>
                    <td>
                        {{ $inc->assignedTo ? Str::limit($inc->assignedTo->name, 18) : 'Sin asignar' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 16px; color: #64748B;">
                        No se encontraron incidencias que coincidan con los criterios de filtrado seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Este documento es generado automáticamente por el Sistema de Gestión de Seguridad Tecnológica (GTS) del Aeropuerto Internacional Felipe Ángeles.<br>
        La información aquí consignada es de carácter reservado para fines de trazabilidad operativa y mantenimiento pericial de infraestructuras críticas.
    </div>

    <!-- Script DomPDF para Paginación Institucional -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página " . $PAGE_NUM . " de " . $PAGE_COUNT;
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 7.5;
            $color = array(0.35, 0.4, 0.45);
            $pdf->page_text(740, 568, $text, $font, $size, $color);
            $pdf->page_text(40, 568, "AIFA • Grupo de Seguridad Tecnológica  |  Control Documental: {{ $reportFolio }}", $font, $size, $color);
        }
    </script>
</body>
</html>
