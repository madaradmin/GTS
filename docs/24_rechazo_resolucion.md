# PASO 24: RECHAZO DE RESOLUCIÓN TÉCNICA POR APOC Y REINGRESO A ATENCIÓN TÉCNICA

## 1. OBJETIVO DEL PASO
Implementar, verificar y auditar el flujo de **Rechazo de Solución Técnica** en **GIT** (Gestión de Incidencias Tecnológicas - APOC / GTS). Este mecanismo faculta a los operadores del Centro de Operaciones Aeroportuarias (APOC) y a los Administradores para evaluar una incidencia que ha sido marcada como `RESUELTA` por GTS. Si tras las pruebas operacionales en sitio o consola se constata que la falla persiste o que el servicio aeronáutico no ha quedado restablecido a entera satisfacción, APOC rechaza formalmente la solución mediante un motivo técnico obligatorio (`rejection_reason`, mínimo 10 caracteres). La incidencia retorna inmediatamente al estado activo `EN_ATENCION`, se reinicia su contador de resolución activa (`resolved_at = null`), se muestra la retroalimentación al especialista técnico en la interfaz y se generan los registros inmutables de auditoría en la bitácora (`CAMBIO_ESTADO` y `RECHAZO_SOLUCION`).

---

## 2. POR QUÉ LO HACEMOS
En un entorno aeroportuario de misión crítica (AIFA):
1. **Control de Calidad Operacional y Homologación de Servicio:** El equipo que repara físicamente el hardware o software (GTS) puede dar por resuelta una falla según sus pruebas de laboratorio o de conectividad local (e.g. respuesta a ping). Sin embargo, el usuario final operativo (APOC) es quien interactúa con los sistemas en producción (torniquetes biométricos e-Gates, pantallas FIDS, cámaras perimetrales, radios TETRA). Si el sistema sigue fallando bajo carga real, APOC debe contar con una vía formal para devolver el ticket a ingeniería.
2. **Retroalimentación Técnica Obligatoria sin Pérdida de Contexto:** Exigir un motivo de rechazo ($\ge 10$ caracteres) impide devolver incidencias sin argumentos técnicos. El especialista GTS asignado puede leer de inmediato en su expediente por qué su arreglo fue insuficiente (e.g. *"Persiste timeout tras el tercer pase de abordar en horas pico"*), permitiéndole enfocar el nuevo diagnóstico sin duplicar esfuerzos ni crear folios nuevos.
3. **Métricas Fidedignas de Reincidencia y Precisión Técnica:** En auditorías de calidad aeronáutica (OACI / AFAC), una métrica esencial es la tasa de resolución a la primera intervención (*First Time Fix Rate*). Contar con el evento `RECHAZO_SOLUCION` en la bitácora permite identificar qué subsistemas tecnológicos presentan soluciones fallidas recurrentes o requieren sustitución definitiva de equipamiento.
4. **Segregación Estricta de Funciones (RN-02):** Un técnico de GTS no puede rechazar la solución de su propio grupo ni de otro compañero; el rechazo es potestad exclusiva del área reportante (APOC) o de la administración del sistema.

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):** Columna `rejection_reason` (text, nullable) y `resolved_at` en la tabla `incidents`.
* **Fase 4 (Seguridad y Autorización):**
  * `RejectResolutionRequest`: Validación de obligatoriedad y longitud (10-1000 caracteres) del motivo de rechazo.
  * `IncidentPolicy::rejectResolution`: Restringe la acción exclusivamente a usuarios APOC o Administradores activos cuando el ticket se encuentra en estado `RESUELTA`.
* **Fase 5 (Pasos 19 al 23):** Ciclo de vida previo: Creación (`NUEVA`), Asignación (`ASIGNADA`), Atención en campo (`EN_ATENCION`) y Resolución técnica previa (`RESUELTA`).

---

## 4. ESTADO ACTUAL
* Sistema con 88 pruebas automatizadas superadas al 100% (288 aserciones).
* Base de datos PostgreSQL `git_incidentes` con soporte transaccional íntegro.
* Vistas tácticas con stepper de ciclo de vida y tarjeta de solución técnica certificada por GTS.

---

## 5. PLAN DE EJECUCIÓN
1. **Capa de Servicio (`IncidentService.php`):**
   * Implementar `rejectResolution(Incident $incident, User $actor, string $reason): Incident`:
     * Ejecutado en transacción atómica `DB::transaction`.
     * Transicionar `status_id` al ID de `EN_ATENCION`.
     * Persistir `rejection_reason = trim($reason)`.
     * Restablecer `resolved_at = null` para reabrir la ventana de atención técnica.
     * Registrar auditoría inmutable de cambio de estado regresivo (`CAMBIO_ESTADO` de "Resuelta" a "En Atención").
     * Registrar auditoría inmutable de rechazo (`RECHAZO_SOLUCION`) con el contenido completo del motivo.
2. **Acción de Controlador (`IncidentController.php`):**
   * Importar `RejectResolutionRequest`.
   * Implementar `rejectResolution(RejectResolutionRequest $request, Incident $incident): RedirectResponse`.
   * Autorización mediante `Gate::authorize('rejectResolution', $incident)`.
   * Invocar `IncidentService::rejectResolution` y redirigir con notificación flash informativa.
3. **Definición de Rutas (`routes/web.php`):**
   * Registrar `POST /incidencias/{incident}/rechazar-solucion` con nombre `incidents.reject-resolution`.
4. **Diseño de Interfaz de Usuario (`resources/views/incidents/show.blade.php`):**
   * Botón táctico "Rechazar Solución" en color carmesí visible para APOC/Admin bajo directiva `@can('rejectResolution', $incident)`.
   * Banner de alerta táctica "Solución Técnica Previa Rechazada por APOC" cuando la incidencia se encuentra en `EN_ATENCION` y posee `rejection_reason`.
   * Modal interactivo Alpine.js (`showRejectModal`) con validación de caracteres y advertencia de reingreso a diagnóstico.
   * Formateo distintivo en la bitácora inmutable para el evento `RECHAZO_SOLUCION` con tarjeta y badge carmesí.
5. **Suite de Pruebas Automatizadas (`RejectResolutionTest.php`):**
   * Pruebas exhaustivas para invitados, denegación a GTS, aceptación para APOC y Administrador, bloqueos en estados indebidos y validación de longitud mínima del motivo.

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Services/IncidentService.php` (Modificado: incorporación de `rejectResolution`)
* `app/Http/Controllers/IncidentController.php` (Modificado: importación de `RejectResolutionRequest` e implementación de `rejectResolution`)
* `routes/web.php` (Modificado: registro de ruta `incidents.reject-resolution`)
* `resources/views/incidents/show.blade.php` (Modificado: botón táctico, modal de rechazo, banner de retroalimentación y estilo en bitácora)
* `tests/Feature/RejectResolutionTest.php` (Nuevo: 6 casos de prueba unitarios e integrales)
* `docs/24_rechazo_resolucion.md` (Nuevo: memoria técnica del paso)

---

## 7. IMPLEMENTACIÓN DETALLADA

### 7.1. Lógica Transaccional en `IncidentService.php`
```php
public function rejectResolution(Incident $incident, User $actor, string $reason): Incident
{
    return DB::transaction(function () use ($incident, $actor, $reason) {
        $previousStatus = $incident->status->name;
        $inAttentionStatus = Status::where('code', 'EN_ATENCION')->firstOrFail();

        // 1. Revertir estado a EN_ATENCION y registrar el motivo de rechazo (RN-02)
        $incident->status_id = $inAttentionStatus->id;
        $incident->rejection_reason = trim($reason);
        $incident->resolved_at = null; // Se reinicia el tiempo de resolución para reabrir la ventana técnica
        $incident->save();

        // 2. Auditoría inmutable del cambio de estado regresivo
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $actor->id,
            action: 'CAMBIO_ESTADO',
            fieldName: 'status_id',
            oldValue: $previousStatus,
            newValue: $inAttentionStatus->name,
            comment: "Solución rechazada por {$actor->name} (APOC). Incidencia reingresada a atención técnica."
        );

        // 3. Auditoría inmutable del rechazo con la justificación operativa (RF-05 / RN-02 / RN-04)
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $actor->id,
            action: 'RECHAZO_SOLUCION',
            fieldName: 'rejection_reason',
            oldValue: null,
            newValue: null,
            comment: trim($reason)
        );

        return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
    });
}
```

### 7.2. Acción de Controlador en `IncidentController.php`
```php
public function rejectResolution(RejectResolutionRequest $request, Incident $incident): RedirectResponse
{
    Gate::authorize('rejectResolution', $incident);

    $this->incidentService->rejectResolution(
        incident: $incident,
        actor: $request->user(),
        reason: $request->validated('rejection_reason')
    );

    return redirect()->route('incidents.show', $incident->folio)
        ->with('success', "Solución de la incidencia {$incident->folio} rechazada. El expediente ha retornado al estado EN ATENCIÓN para nuevo diagnóstico por GTS.");
}
```

---

## 8. EXPLICACIÓN TÉCNICA PARA ESTUDIANTE DE TELECOMUNICACIONES
Imagina el subsistema de CCTV y analítica de video perimetral del aeropuerto:
1. **La Solución Aparente:** El equipo técnico de GTS interviene una cámara domo PTZ que reportaba pérdida de video. Reemplazan el inyector PoE y comprueban en el switch que el enlace Ethernet negocia a 1 Gbps y que el comando `ping` responde con 0% de pérdidas. GTS da la incidencia como **`RESUELTA`**.
2. **La Prueba en Operación Real:** En el Centro de Operaciones (APOC), el despachador de seguridad abre el software cliente VMS (ej. Genetec) para vigilar la cabecera de pista. Al solicitar el movimiento PTZ y el flujo de video en alta definición (H.265 a 30 fps), la transmisión se congela periódicamente debido a saturación en el ancho de banda del puerto o bitrate inestable.
3. **El Rechazo Justificado:** APOC rechaza la solución indicando: *"El flujo de video en stream principal presenta caída de frames y congelamiento continuo al solicitar paneo PTZ hacia plataforma de carga"*.
4. **Efecto en la Máquina de Estados:** La incidencia no requiere un nuevo ticket ni pierde su folio original `INC-YYYY-XXXX`. Regresa inmediatamente a **`EN_ATENCION`**, conservando intacto todo el historial de lo que ya se hizo, pero informando con claridad que el problema no ha sido erradicado en el plano de aplicación real.

---

## 9. PRUEBAS REALIZADAS Y RESULTADOS
Se ejecutó la suite de pruebas automatizadas:

```powershell
php artisan test --filter=RejectResolutionTest
```
* `test_guest_cannot_reject_resolution`: **PASS** (Redirección a login).
* `test_gts_user_cannot_reject_resolution`: **PASS** (HTTP 403 Forbidden para usuarios GTS).
* `test_apoc_user_can_reject_resolution_when_incident_is_resolved`: **PASS** (Reversión a `EN_ATENCION`, reseteo de `resolved_at`, persistencia de `rejection_reason` y generación de 2 registros de auditoría).
* `test_admin_user_can_reject_resolution_when_incident_is_resolved`: **PASS** (Permisos de supervisión administrativa verificados).
* `test_cannot_reject_resolution_when_incident_is_not_in_resolved_state`: **PASS** (HTTP 403 en estados `NUEVA`, `ASIGNADA`, `EN_ATENCION` y `CERRADA`).
* `test_rejection_reason_is_required_and_must_have_at_least_10_characters`: **PASS** (Validación de longitud mínima y no nulidad).

Suite global:
```powershell
php artisan test
# Result: Tests: 88 passed, 288 assertions (100% green)
```

---

## 10. RESULTADO ESPERADO
* El botón "Rechazar Solución" solo es visible para personal con rol `apoc` o `admin` cuando la incidencia está en estado `RESUELTA`.
* Al pulsar el botón, se abre el modal interactivo de rechazo solicitando los motivos técnicos del rechazo.
* Tras confirmar con un texto de al menos 10 caracteres, el estado de la incidencia cambia de forma inmediata a `EN_ATENCION`.
* El técnico GTS observa en el encabezado del ticket un banner de advertencia carmesí que expone el motivo del rechazo para orientar su nuevo trabajo de campo.
* En la bitácora inmutable de auditoría se añade el evento `RECHAZO_SOLUCION` con autoría e indexación temporal inmutable.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES
1. **Intento de Rechazo por Personal Técnico (GTS):**
   * *Síntoma:* Un especialista GTS intenta rechazar una solución y el sistema responde con error 403.
   * *Solución:* Por diseño de segregación de funciones (RN-02), GTS no tiene la facultad de rechazar; solo el área solicitante (APOC) o la Administración pueden rechazar una certificación técnica.
2. **Pérdida de la Causa de Rechazo:**
   * *Síntoma:* Si no se registrara el motivo de rechazo en base de datos, el técnico volvería a atender el ticket a ciegas.
   * *Solución:* El Form Request valida `rejection_reason` con obligatoriedad y el servicio lo almacena simultáneamente en la tabla `incidents` y en el historial inmutable `incident_histories`.

---

## 12. VERIFICACIÓN FINAL
* [x] Método transaccional `IncidentService::rejectResolution` implementado con reinicio de `resolved_at`.
* [x] Acción de controlador `IncidentController::rejectResolution` validada con `RejectResolutionRequest`.
* [x] Ruta web registrada y protegida (`POST /incidencias/{incident}/rechazar-solucion`).
* [x] Vista `show.blade.php` con botón condicional, modal interactivo Alpine.js, banner de rechazo previo y diseño en bitácora.
* [x] Pruebas automatizadas `RejectResolutionTest` (6 casos) superadas con 100% de éxito.
* [x] Suite completa de pruebas (88 tests, 288 aserciones) ejecutada en verde.
* [x] Base de datos de desarrollo re-sembrada con `php artisan db:seed`.
* [x] Commit Git efectuado con mensaje estandarizado.

