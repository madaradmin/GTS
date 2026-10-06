<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppReportService
{
    /**
     * Formatea el mensaje ejecutivo para el despacho por WhatsApp.
     *
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $filters
     * @param  array<string>|null  $selectedFolios
     */
    public function formatReportMessage(array $summary, array $filters, string $reportFolio, ?string $reportUrl = null, ?array $selectedFolios = null): string
    {
        $dateStr = Carbon::now(config('app.timezone', 'America/Mexico_City'))->format('d/m/Y H:i');

        $periodLabel = 'General (Histórico Completo)';
        if (! empty($selectedFolios)) {
            $periodLabel = count($selectedFolios).' Incidencias Seleccionadas';
        } elseif (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            $periodLabel = "Del {$filters['date_from']} al {$filters['date_to']}";
        } elseif (! empty($filters['period_preset'])) {
            $periodLabel = match ($filters['period_preset']) {
                'today' => 'Turno Actual (Hoy)',
                'last_7_days', 'week' => 'Últimos 7 Días',
                'this_month', 'month' => 'Mes en Curso',
                default => ucfirst($filters['period_preset']),
            };
        }

        $total = $summary['total'] ?? 0;
        $nuevas = $summary['by_status']['NUEVA'] ?? 0;
        $asignadas = $summary['by_status']['ASIGNADA'] ?? 0;
        $enAtencion = $summary['by_status']['EN_ATENCION'] ?? 0;
        $resueltas = $summary['by_status']['RESUELTA'] ?? 0;
        $cerradas = $summary['by_status']['CERRADA'] ?? 0;
        $tasa = $summary['resolution_rate'] ?? 0;
        $mttr = $summary['avg_resolution_time_formatted'] ?? 'N/A';

        $selectedFoliosLine = ! empty($selectedFolios)
            ? '🎯 *Folios Seleccionados:* '.implode(', ', array_slice($selectedFolios, 0, 10)).(count($selectedFolios) > 10 ? ' y más...' : '')."\n\n"
            : '';

        $urlLine = $reportUrl ? "\n🔗 *Acceso Seguro:* {$reportUrl}" : '';

        return "🏛️ *AIFA — GTS (Grupo de Seguridad Tecnológica)*\n"
             ."📋 *REPORTE OPERATIVO DE INCIDENCIAS*\n"
             ."🔖 *Folio:* #{$reportFolio}\n"
             ."📅 *Emisión:* {$dateStr} hrs\n"
             ."⏱️ *Período / Criterio:* {$periodLabel}\n\n"
             .$selectedFoliosLine
             ."📊 *RESUMEN EJECUTIVO:*\n"
             ."• *Total Incidencias:* {$total}\n"
             ."• 🔵 *Nuevas:* {$nuevas}\n"
             ."• 🟣 *Asignadas:* {$asignadas}\n"
             ."• 🟡 *En Atención:* {$enAtencion}\n"
             ."• 🟢 *Resueltas:* {$resueltas}\n"
             ."• ⚪ *Cerradas:* {$cerradas}\n"
             ."• ⚡ *Tasa de Eficacia:* {$tasa}%\n"
             ."• ⏳ *MTTR Promedio:* {$mttr}\n"
             .$urlLine."\n\n"
             .'🔒 _Control Operativo Aeroportuario AIFA • Sistema GTS_';
    }

    /**
     * Limpia y estandariza el número telefónico para formato E.164.
     */
    public function sanitizePhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone) ?? '';

        // Si no inicia con '+' ni con '52' y tiene 10 dígitos (México), anteponer 52
        if (strlen($cleaned) === 10 && ! str_starts_with($cleaned, '+')) {
            $cleaned = '52'.$cleaned;
        }

        return ltrim($cleaned, '+');
    }

    /**
     * Despacha el reporte vía API oficial de WhatsApp Cloud o genera enlace verificado directo.
     *
     * @return array<string, mixed>
     */
    public function sendOrGenerate(string $phone, string $message): array
    {
        $sanitizedPhone = $this->sanitizePhoneNumber($phone);
        $apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v19.0');
        $phoneId = config('services.whatsapp.phone_number_id');
        $token = config('services.whatsapp.access_token');

        // Modo API Cloud Oficial cuando las credenciales están presentes
        if (! empty($phoneId) && ! empty($token)) {
            try {
                $response = Http::withToken($token)
                    ->post("{$apiUrl}/{$phoneId}/messages", [
                        'messaging_product' => 'whatsapp',
                        'recipient_type' => 'individual',
                        'to' => $sanitizedPhone,
                        'type' => 'text',
                        'text' => [
                            'preview_url' => false,
                            'body' => $message,
                        ],
                    ]);

                if ($response->successful()) {
                    Log::info("Reporte enviado vía WhatsApp Cloud API al número: {$sanitizedPhone}");

                    return [
                        'success' => true,
                        'mode' => 'api',
                        'phone' => $sanitizedPhone,
                        'message' => 'Reporte despachado exitosamente mediante WhatsApp Business API.',
                    ];
                }

                Log::error('Error al enviar WhatsApp Cloud API: '.$response->body());

                return [
                    'success' => false,
                    'mode' => 'api',
                    'phone' => $sanitizedPhone,
                    'message' => 'Fallo en la API de WhatsApp Cloud: '.$response->status(),
                    'error' => $response->json(),
                ];
            } catch (\Throwable $e) {
                Log::error('Excepción en WhatsAppReportService: '.$e->getMessage());

                return [
                    'success' => false,
                    'mode' => 'api',
                    'phone' => $sanitizedPhone,
                    'message' => 'Error de conexión con la pasarela WhatsApp.',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Modo Enlace Oficial Verificado (WhatsApp Web / Móvil) para entornos locales / sin API Cloud
        $encodedText = rawurlencode($message);
        $directUrl = "https://api.whatsapp.com/send?phone={$sanitizedPhone}&text={$encodedText}";

        Log::info("Enlace oficial de WhatsApp preparado para {$sanitizedPhone}");

        return [
            'success' => true,
            'mode' => 'link',
            'phone' => $sanitizedPhone,
            'url' => $directUrl,
            'message' => 'Enlace oficial preparado para compartir en WhatsApp.',
        ];
    }
}
