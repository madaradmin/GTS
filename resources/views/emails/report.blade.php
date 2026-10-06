<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Operativo de Incidencias GTS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0B0F14;
            color: #DDE3EA;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 620px;
            margin: 0 auto;
            background-color: #11151B;
            border: 1px solid #263241;
            border-radius: 12px;
            overflow: hidden;
        }
        .header {
            background-color: #151B23;
            border-bottom: 1px solid #263241;
            padding: 24px 28px;
            text-align: left;
        }
        .header h1 {
            color: #FFFFFF;
            font-size: 18px;
            margin: 0 0 6px 0;
            letter-spacing: -0.02em;
        }
        .header .subtitle {
            color: #3D82C6;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .content {
            padding: 28px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
            font-family: monospace;
            background-color: rgba(61, 130, 198, 0.15);
            color: #6FA2D2;
            border: 1px solid rgba(61, 130, 198, 0.3);
        }
        .meta-box {
            background-color: #0B0F14;
            border: 1px solid #1E2734;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .meta-grid {
            display: table;
            width: 100%;
        }
        .meta-row {
            display: table-row;
        }
        .meta-label {
            display: table-cell;
            color: #707B8A;
            font-size: 12px;
            padding: 4px 8px 4px 0;
            width: 35%;
        }
        .meta-val {
            display: table-cell;
            color: #F2F5F8;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 0;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 24px;
            border-collapse: separate;
            border-spacing: 6px;
        }
        .summary-cell {
            display: table-cell;
            background-color: #151B23;
            border: 1px solid #263241;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            width: 33.33%;
        }
        .summary-num {
            font-size: 20px;
            font-weight: bold;
            color: #3D82C6;
            margin-bottom: 2px;
            font-family: monospace;
        }
        .summary-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #8D98A8;
            letter-spacing: 0.05em;
        }
        .notes-box {
            background-color: rgba(224, 162, 26, 0.08);
            border-left: 3px solid #E0A21A;
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 13px;
            color: #E2B24C;
            margin-bottom: 24px;
        }
        .attachment-note {
            background-color: #0F141B;
            border: 1px dashed #2E3846;
            padding: 14px;
            border-radius: 8px;
            font-size: 12px;
            color: #A8B0BC;
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            background-color: #0D1117;
            border-top: 1px solid #1E2734;
            padding: 16px 28px;
            font-size: 11px;
            color: #555F6D;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="subtitle">AIFA — Aeropuerto Internacional Felipe Ángeles</div>
            <h1>GTS — Grupo de Seguridad Tecnológica</h1>
            <div>
                <span class="badge">Folio: {{ $reportFolio }}</span>
            </div>
        </div>

        <div class="content">
            <p style="margin-top: 0; font-size: 14px; line-height: 1.5;">
                Estimado(a), se ha generado un informe formal de incidencias operativas conforme a los parámetros solicitados.
            </p>

            <div class="meta-box">
                <div class="meta-grid">
                    <div class="meta-row">
                        <div class="meta-label">Emitido por:</div>
                        <div class="meta-val">{{ $requester->name }} ({{ $requester->badge_number ?? 'S/N' }})</div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-label">Fecha y Hora:</div>
                        <div class="meta-val">{{ now()->format('d/m/Y H:i:s') }} hrs</div>
                    </div>
                    @foreach($appliedFilters as $label => $val)
                        <div class="meta-row">
                            <div class="meta-label">{{ $label }}:</div>
                            <div class="meta-val">{{ $val }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if(!empty($notes))
                <div class="notes-box">
                    <strong>Nota del Solicitante:</strong><br>
                    {{ $notes }}
                </div>
            @endif

            <h3 style="font-size: 13px; text-transform: uppercase; color: #8D98A8; letter-spacing: 0.05em; margin-bottom: 8px;">
                Resumen Ejecutivo
            </h3>

            <div class="summary-grid">
                <div class="summary-cell">
                    <div class="summary-num">{{ $summary['total'] }}</div>
                    <div class="summary-title">Total Incidencias</div>
                </div>
                <div class="summary-cell">
                    <div class="summary-num" style="color: #20C997;">{{ $summary['completed'] }}</div>
                    <div class="summary-title">Resueltas / Cerradas</div>
                </div>
                <div class="summary-cell">
                    <div class="summary-num" style="color: #F59E0B;">{{ $summary['resolution_rate'] }}%</div>
                    <div class="summary-title">Tasa de Eficacia</div>
                </div>
            </div>

            <div class="attachment-note">
                📎 <strong>Documento Adjunto:</strong> Se adjunta el archivo oficial <code>Reporte_GTS_{{ $reportFolio }}.pdf</code> con el desglose tabular completo y trazabilidad pericial.
            </div>
        </div>

        <div class="footer">
            Este correo es emitido por el sistema automatizado de gestión técnica de GTS AIFA.<br>
            Información confidencial para uso exclusivo del personal de operaciones y seguridad tecnológica.
        </div>
    </div>
</body>
</html>

