# PASO 23: RESOLUCIÓN TÉCNICA DE INCIDENCIAS POR GTS (CON DESCRIPCIÓN OBLIGATORIA DE SOLUCIÓN)

## 1. OBJETIVO DEL PASO
Implementar, blindar y auditar el flujo de **Resolución Técnica** en **GIT** (Gestión de Incidencias Tecnológicas - APOC / GTS). Este hito permite a los especialistas técnicos del grupo GTS y Administradores transicionar una incidencia desde el estado activo `EN_ATENCION` hacia el estado `RESUELTA` (RF-05 / RN-02). La transición impone la obligación contractual y normativa de registrar de manera detallada la solución técnica aplicada (`solution_description`, mínimo 15 caracteres), captura el timestamp inmutable de resolución (`resolved_at`), genera los registros de auditoría correspondientes (`CAMBIO_ESTADO` y `RESUELTA`) y presenta el expediente preparado para la fase posterior de validación y visto bueno por parte del Centro de Operaciones Aeroportuarias (APOC).

---

## 2. POR QUÉ LO HACEMOS
En la gestión de infraestructura tecnológica aeroportuaria crítica (aeropuerto internacional AIFA):
1. **Segregación Estricta de Funciones (Separation of Concerns):** El personal técnico que repara un equipo (GTS) no puede auto-aprobarse el cierre administrativo ni certificar por sí mismo el restablecimiento del servicio de cara al usuario final. La resolución técnica es la declaración de GTS de que los trabajos de ingeniería están concluidos; el cierre definitivo es potestad exclusiva de APOC tras verificar la operatividad en sitio.
2. **Memoria Técnica y Base de Conocimiento:** Exigir un mínimo de 15 caracteres descriptivos evita resoluciones ambiguas o vacías como "ok", "listo" o "reparado". Queda registrado exactamente qué transceptor SFP se reemplazó, qué fusible se sustituyó, qué puerto o VLAN fue reconfigurado, y qué mediciones de telemetría/atenuación se obtuvieron.
3. **Cálculo Preciso de Métricas MTBF y MTTR:** El timestamp `resolved_at` marca el fin exacto de la ventana de reparación técnica. La diferencia `resolved_at - attended_at` determina el *Mean Time to Repair* del subsistema tecnológico específico (CCTV, e-Gates, BHS, TETRA, FIDS).
4. **Trazabilidad Aeronáutica Inmutable (RN-04):** Cualquier resolución queda registrada en la bitácora append-only `incident_histories`, impidiendo manipulaciones retroactivas sobre las acciones tomadas durante una contingencia operacional.

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):** Tabla `incidents` con columnas `solution_description`, `resolved_at` y `status_id`.
* **Fase 4 (Seguridad y Autorización):**
  * `ResolveIncidentRequest`: Valida obligatoriedad y longitud (15-3000 caracteres) de la solución.
  * `IncidentPolicy::resolve`: Restringe la acción exclusivamente a usuarios GTS o Administradores activos cuando el ticket se encuentra en estado `EN_ATENCION`.
* **Fase 5 (Pasos 19 al 22):** Ciclo de vida previo: Creación (`NUEVA`), Asignación (`ASIGNADA`) y Atención en campo (`EN_ATENCION`).

---

## 4. ESTADO ACTUAL
* Sistema con 82 pruebas automatizadas superadas al 100% (259 aserciones).
* Base de datos PostgreSQL `git_incidentes` con migraciones, seeders y datos iniciales listos.
* Interfaz táctica con visualización de estado en stepper visual, línea de tiempo de auditoría y captura de notas técnicas.

---

## 5. PLAN DE EJECUCIÓN
1. **Capa de Servicio (`IncidentService.php`):**
   * Crear el método `resolveIncident(Incident $incident, User $technician, string $solutionDescription): Incident`:
     * Ejecutado dentro de una transacción atómica `DB::transaction`.
     * Transicionar `status_id` al ID correspondiente a `RESUELTA`.
     * Asignar `solution_description = trim($solutionDescription)`.
     * Asignar `resolved_at = now()`.
     * Registrar auditoría inmutable de cambio de estado (`CAMBIO_ESTADO`).
     * Registrar auditoría inmutable de certificación técnica (`RESUELTA`) con el detalle de la solución aplicada.
2. **Acción de Controlador (`IncidentController.php`):**
   * Crear el método `resolve(ResolveIncidentRequest $request, Incident $incident): RedirectResponse`:
     * Autorización explícita mediante `Gate::authorize('resolve', $incident)`.
     * Invocar `IncidentService::resolveIncident`.
     * Redireccionar al expediente con notificación flash de éxito indicando que el ticket espera visto bueno de APOC.
3. **Definición de Rutas (`routes/web.php`):**
   * Registrar `POST /incidencias/{incident}/resolver` con nombre `incidents.resolve`.
4. **Interfaz Gráfica de Usuario (`resources/views/incidents/show.blade.php`):**
   * Botón táctico "Marcar como Resuelta" en el encabezado visible solo bajo directiva `@can('resolve', $incident)`.
   * Tarjeta visual destacada en color esmeralda `Solución Técnica Certificada (GTS)` en la columna izquierda cuando existe `solution_description`.
   * Banner informativo en estado `RESUELTA`: "En espera de validación de servicio y visto bueno por el Centro de Operaciones (APOC) para proceder con el cierre formal del ticket".
   * Estilo diferenciado con badge verde y tarjeta destacada en la bitácora inmutable para el evento `RESUELTA`.
   * Modal interactivo Alpine.js (`showResolveModal`) con validación de campo, contador descriptivo y botón de confirmación.
5. **Suite de Pruebas Automatizadas (`ResolveIncidentTest.php`):**
   * Cobertura completa de invitados, accesos indebidos de APOC, éxito de GTS, éxito de Administradores, intentos de resolución en estados no permitidos (`NUEVA`, `ASIGNADA`, `RESUELTA`, `CERRADA`) y validación de longitud mínima de la solución técnica.

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Services/IncidentService.php` (Modificado: incorporación de `resolveIncident`)
* `app/Http/Controllers/IncidentController.php` (Modificado: importación de `ResolveIncidentRequest` e implementación de `resolve`)
* `routes/web.php` (Modificado: registro de ruta `incidents.resolve`)
* `resources/views/incidents/show.blade.php` (Modificado: botón, modal Alpine.js, tarjeta de solución y formateo de auditoría)
* `resources/css/app.css` (Modificado: regla `[x-cloak] { display: none !important; }`)
* `tests/Feature/ResolveIncidentTest.php` (Nuevo: 7 casos de prueba para el flujo de resolución)
* `docs/23_resolucion_tecnica.md` (Nuevo: memoria técnica del paso)

---

## 7. IMPLEMENTACIÓN DETALLADA

### 7.1. Lógica Transaccional en `IncidentService.php`
```php
public function resolveIncident(Incident $incident, User $technician, string $solutionDescription): Incident
{
    return DB::transaction(function () use ($incident, $technician, $solutionDescription) {
        $previousStatus = $incident->status->name;
        $resolvedStatus = Status::where('code', 'RESUELTA')->firstOrFail();

        // 1. Actualizar estado operacional, solución técnica y timestamp de resolución (RN-02)
        $incident->status_id = $resolvedStatus->id;
        $incident->solution_description = trim($solutionDescription);
        $incident->resolved_at = now();
        $incident->save();

        // 2. Registrar auditoría de cambio de estado a RESUELTA (RN-04)
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $technician->id,
            action: 'CAMBIO_ESTADO',
            fieldName: 'status_id',
            oldValue: $previousStatus,
            newValue: $resolvedStatus->name,
            comment: "Incidencia marcada como RESUELTA por el especialista {$technician->name}."
        );

        // 3. Registrar auditoría específica de la solución aplicada (RF-05 / RN-02 / RN-04)
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $technician->id,
            action: 'RESUELTA',
            fieldName: 'solution_description',
            oldValue: null,
            newValue: null,
            comment: trim($solutionDescription)
        );

        return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
    });
}
```

### 7.2. Acción de Controlador y Form Request
```php
public function resolve(ResolveIncidentRequest $request, Incident $incident): RedirectResponse
{
    Gate::authorize('resolve', $incident);

    $this->incidentService->resolveIncident(
        incident: $incident,
        technician: $request->user(),
        solutionDescription: $request->validated('solution_description')
    );

    return redirect()->route('incidents.show', $incident->folio)
        ->with('success', "Incidencia {$incident->folio} resuelta exitosamente. Pendiente de validación y visto bueno por APOC.");
}
```

---

## 8. EXPLICACIÓN TÉCNICA PARA ESTUDIANTE DE TELECOMUNICACIONES
Imagina una estación terrena o un enlace de radioenlace por microondas entre la Torre de Control y los radares de aproximación:
1. **Diagnóstico vs. Aceptación del Enlace:** Un ingeniero de radiofrecuencia (GTS) sube a la torre, ajusta el acimut de la antena parabólica, reemplaza el Low-Noise Block (LNB) dañado y comprueba con el analizador de espectro que la relación Portadora-Ruido ($C/N$) recuperó su nivel nominal de 16 dB. En ese momento, el ingeniero registra su reporte técnico: *"Reemplazo de LNB marca Invacom, realineación de azimut a 248° y verificación de BER < $10^{-9}$"*. El enlace para el técnico está **RESUELTO**.
2. **La Validación de Tráfico Real:** El controlador de tráfico aéreo (APOC) que utiliza la señal radar en su consola debe certificar que los ecos de las aeronaves y las tramas ASTERIX se reciben sin fluctuaciones ni pérdidas de paquetes durante el servicio activo. Solo cuando APOC confirma que el sistema es totalmente funcional de cara a la operación aérea, se procede al **CIERRE** definitivo del expediente.
3. **El Estado Intermedio `RESUELTA`:** Por tanto, `RESUELTA` no significa cerrado. Es el puente técnico donde concluye el *Mean Time to Repair* de ingeniería y comienza la ventana de homologación operacional y control de calidad.

---

## 9. PRUEBAS REALIZADAS Y RESULTADOS
Se ejecutó la suite completa de pruebas unitarias y de integración:

```sh
php artisan test --filter=ResolveIncidentTest
```
* `test_guest_cannot_resolve_incident`: PASS (Redirección a login).
* `test_apoc_user_cannot_resolve_incident`: PASS (HTTP 403 Prohibido por política de segregación).
* `test_gts_user_can_resolve_incident_in_attention_with_valid_solution`: PASS (Transición de estado a `RESUELTA`, almacenamiento de solución técnica, timestamp `resolved_at` y 2 registros de auditoría en `incident_histories`).
* `test_admin_user_can_resolve_incident_in_attention`: PASS (Permisos de contingencia para administrador).
* `test_cannot_resolve_incident_in_new_or_assigned_state`: PASS (HTTP 403 si no ha iniciado formalmente atención en campo).
* `test_cannot_resolve_incident_already_resolved_or_closed`: PASS (HTTP 403 al intentar sobre-escribir estados terminales).
* `test_solution_description_is_required_and_must_have_at_least_15_characters`: PASS (Validación de longitud y campo obligatorio).

Suite global:
```sh
php artisan test
# Result: Tests: 82 passed, 259 assertions (100% green)
```

---

## 10. RESULTADO ESPERADO
* Un técnico de GTS o Administrador visualiza el botón esmeralda "Marcar como Resuelta" únicamente cuando la incidencia se encuentra en estado `EN_ATENCION`.
* Al presionar el botón, se despliega un modal centrado con el folio de la incidencia y un área de texto obligatoria donde se detalla la maniobra técnica.
* Al enviar el formulario con más de 15 caracteres descriptivos, el estado pasa inmediatamente a `RESUELTA`, el stepper ilumina el paso 4 con la fecha y hora exacta, se presenta la tarjeta verde con la solución aplicada y se notifica que el ticket aguarda la revisión final de APOC.
* En la bitácora inmutable queda registrado el cambio de estado y el contenido íntegro de la solución técnica aplicada con autoría e indexación temporal.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES
1. **Error de Validación por Longitud Menor a 15 Caracteres:**
   * *Síntoma:* El usuario envía "Se corrigió" y la interfaz retorna error.
   * *Solución:* La regla `min:15` en `ResolveIncidentRequest` fuerza una descripción con rigor técnico. El modal incluye texto de ayuda indicando que debe detallarse el componente o la maniobra técnica efectuada.
2. **Intento de Resolución Directa desde `ASIGNADA`:**
   * *Síntoma:* Un técnico quiere resolver directamente sin haber presionado antes "Iniciar Atención".
   * *Solución:* La política `IncidentPolicy::resolve` rechaza con `403 Forbidden`. En la interfaz, el botón "Marcar como Resuelta" solo se renderiza si el estado actual es `EN_ATENCION`.
3. **Parpadeo Visual del Modal con Alpine.js:**
   * *Síntoma:* El modal aparecía momentáneamente durante la carga inicial del DOM antes de que Alpine.js evaluara la expresión `x-show="false"`.
   * *Solución:* Se agregó el atributo `x-cloak` al contenedor del modal y la regla CSS correspondiente `[x-cloak] { display: none !important; }` en `resources/css/app.css`.

---

## 12. VERIFICACIÓN FINAL
* [x] Capa de servicio implementada con atomicidad transaccional (`IncidentService::resolveIncident`).
* [x] Controlador y Form Request configurados con validación de solución obligatoria (15-3000 caracteres).
* [x] Ruta web protegida registrada (`/incidencias/{incident}/resolver`).
* [x] Vista `show.blade.php` con botón condicional, modal Alpine.js, tarjeta de solución técnica y actualización de stepper.
* [x] Bitácora inmutable registra eventos `CAMBIO_ESTADO` y `RESUELTA`.
* [x] Suite `ResolveIncidentTest` (7 tests) y suite completa (82 tests) ejecutadas con 100% de éxito.
* [x] Base de datos de desarrollo re-sembrada (`php artisan db:seed`).

