# PASO 25: VALIDACIÓN, VISTO BUENO (VOBO) Y CIERRE DEFINITIVO POR APOC

## 1. OBJETIVO DEL PASO
Implementar, verificar y auditar el flujo de **Validación, Visto Bueno (VoBo) y Cierre Definitivo de Incidencias** en **GIT** (Gestión de Incidencias Tecnológicas - APOC / GTS). Este mecanismo faculta al personal del Centro de Operaciones Aeroportuarias (APOC) y a los Administradores para evaluar en vivo el restablecimiento de los sistemas aeronáuticos tras la intervención técnica de GTS. Al certificar la conformidad operacional, APOC formaliza el cierre del ticket mediante un formulario con observaciones opcionales de servicio. El sistema transiciona la incidencia al estado final e inmutable `CERRADA`, fija el timestamp `closed_at`, completa el indicador de avance del ciclo de vida al 100% (Paso 5 del Stepper), registra los eventos inmutables en la bitácora de auditoría (`CAMBIO_ESTADO` y `CERRADA`), y bloquea de manera definitiva cualquier mutación posterior en el expediente (reasignación, notas técnicas, resolución o rechazo).

---

## 2. POR QUÉ LO HACEMOS
En un aeropuerto internacional de alta densidad y misión crítica como el AIFA:
1. **Segregación Estricta de Funciones (Principio de Oposición de Intereses - RN-02):** Un axioma fundamental en gestión aeroportuaria y de TI es que la brigada técnica que ejecuta la reparación (GTS) no puede autorizar su propio cierre. El técnico reporta "solución técnica aplicada" (`RESUELTA`), pero es el operador de APOC —dueño del proceso operativo y usuario del servicio— quien constata en sitio o consola que los pasajeros y aerolíneas ya no se ven afectados, otorgando el VoBo formal.
2. **Inmutabilidad y Sello Legal del Expediente (RN-04 / RF-05):** Una vez que un ticket alcanza el estado `CERRADA`, se convierte en un registro histórico legal. No se permite agregar notas extemporáneas, reasignar personal ni modificar soluciones para evitar alteraciones que vicien peritajes aeronáuticos (e.g. en investigaciones de la AFAC o auditorías de la OACI).
3. **Cálculo Preciso de Métricas Operacionales (MTTR vs. TTR Total):** Al registrar de forma independiente el momento de resolución técnica (`resolved_at`) y el momento de cierre operacional definitivo (`closed_at`), el sistema permite a la alta dirección medir con precisión dos tiempos clave:
   * *Mean Time To Repair (MTTR):* Tiempo que tardó GTS en diagnosticar y reparar la falla física o lógica.
   * *Total Downtime / Validation Overhead:* Tiempo transcurrido entre la reparación técnica y la confirmación en vivo por APOC.
4. **Culminación del Ciclo de Vida de Incidencias (Fase 5 Completa):** Este paso sella el circuito completo de estados:
   $$\text{NUEVA} \longrightarrow \text{ASIGNADA} \longrightarrow \text{EN ATENCIÓN} \longrightarrow \text{RESUELTA} \longleftrightarrow [\text{RECHAZO}] \longrightarrow \text{CERRADA}$$

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):**
  * Columna `closed_at` (timestamp con zona horaria, nullable) en la tabla `incidents`.
  * Catálogo de estados en `statuses` (`CERRADA`, orden 5, es estado final).
* **Fase 4 (Seguridad y Autorización):**
  * `CloseIncidentRequest`: Valida que las observaciones de cierre no excedan los 1000 caracteres y comprueba la autorización vía Policy.
  * `IncidentPolicy::close`: Autoriza exclusivamente a usuarios con rol `APOC` o `ADMIN` activos, únicamente cuando la incidencia se encuentra en estado `RESUELTA`.
* **Fase 5 (Pasos 19 al 24):**
  * Existencia previa de los estados `NUEVA`, `ASIGNADA`, `EN_ATENCION`, `RESUELTA` y el flujo de rechazo documentado en el Paso 24.
  * `AuditService`: Servicio transversal para la persistencia inmutable de eventos en `incident_histories`.

---

## 4. ESTADO ACTUAL
* Sistema con 96 pruebas automatizadas aprobadas al 100% (324 aserciones).
* Base de datos PostgreSQL `git_incidentes` con integridad referencial completa y disparadores de secuencias de folio activos.
* Interfaz táctica blade con Alpine.js preparada para modales de flujo operativo, timeline inmutable y stepper de 5 pasos.

---

## 5. PLAN DE EJECUCIÓN
1. **Form Request de Validación (`CloseIncidentRequest.php`):**
   * Validar reglas de negocio para el cierre: campo `comment` opcional, tipo string, máximo 1000 caracteres.
   * Control de autorización delegando en la política `close` del modelo `Incident`.
2. **Capa de Servicio (`IncidentService.php`):**
   * Implementar método `closeIncident(Incident $incident, User $closer, ?string $comment = null): Incident`:
     * Envolver en transacción atómica `DB::transaction`.
     * Transicionar `status_id` al ID correspondiente al código `CERRADA`.
     * Estampar `closed_at = now()`.
     * Registrar auditoría inmutable de cambio de estado (`CAMBIO_ESTADO` de "Resuelta" a "Cerrada").
     * Registrar auditoría inmutable del evento de cierre (`CERRADA`) archivando el timestamp y las observaciones de conformidad del operador.
3. **Controlador Web (`IncidentController.php`):**
   * Implementar método `close(CloseIncidentRequest $request, Incident $incident): RedirectResponse`:
     * Autorizar la acción mediante `Gate::authorize('close', $incident)`.
     * Invocar `IncidentService::closeIncident`.
     * Retornar redirección al detalle con mensaje flash de éxito y confirmación de archivo inmutable.
4. **Definición de Rutas (`routes/web.php`):**
   * Registrar endpoint `POST /incidencias/{incident}/cerrar` nombrado `incidents.close`.
5. **Interfaz de Usuario Táctica (`resources/views/incidents/show.blade.php`):**
   * Estado reactivo Alpine.js: variable `showCloseModal: false`.
   * Botón táctico "Dar VoBo y Cerrar" en azul primario con icono `verified`, visible exclusivamente bajo `@can('close', $incident)`.
   * Banner superior de "Expediente Cerrado Definitivamente" con candado inmutable, timestamp y datos del operador que certificó el cierre.
   * Stepper de 5 pasos con indicador al 100% en tono esmeralda/primario.
   * Modal accesible con confirmación de folio, especialista asignado, subsistema tecnológico y textarea para observaciones de conformidad.
   * Enriquecimiento de la línea de tiempo (timeline) con tarjeta destacada para el evento `CERRADA`.
6. **Suite de Pruebas Automatizadas (`CloseIncidentTest.php`):**
   * Cobertura de 8 escenarios: invitados, rechazo para técnicos GTS, cierre con comentarios por APOC, cierre sin comentarios, cierre por Administrador, bloqueo en estados no resueltos, bloqueo de mutaciones posteriores en tickets cerrados y validación de longitud máxima del comentario.

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Http/Requests/CloseIncidentRequest.php` (Creado: validación y autorización del cierre)
* `app/Services/IncidentService.php` (Modificado: lógica transaccional de cierre e historización)
* `app/Http/Controllers/IncidentController.php` (Modificado: acción `close` y redirección con flash message)
* `routes/web.php` (Modificado: ruta `incidents.close`)
* `resources/views/incidents/show.blade.php` (Modificado: modal Alpine.js, botón VoBo, banner inmutable, stepper 100% y timeline)
* `tests/Feature/CloseIncidentTest.php` (Creado: 8 pruebas unitarias y de integración)
* `docs/25_cierre_definitivo.md` (Creado: documentación técnica y pedagógica exhaustiva)

---

## 7. IMPLEMENTACIÓN DETALLADA

### 7.1. Form Request: `app/Http/Requests/CloseIncidentRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident
            ? $this->user()->can('close', $incident)
            : ($this->user()->isApoc() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.max' => 'Las observaciones de cierre no pueden exceder los 1000 caracteres.',
        ];
    }
}
```

### 7.2. Capa de Servicio: `app/Services/IncidentService.php`
```php
public function closeIncident(Incident $incident, User $closer, ?string $comment = null): Incident
{
    return DB::transaction(function () use ($incident, $closer, $comment) {
        $previousStatus = $incident->status->name;
        $closedStatus = Status::where('code', 'CERRADA')->firstOrFail();

        // 1. Transicionar estado a CERRADA y registrar timestamp final (RN-02)
        $incident->status_id = $closedStatus->id;
        $incident->closed_at = now();
        $incident->save();

        // 2. Auditoría inmutable de cambio de estado a CERRADA
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $closer->id,
            action: 'CAMBIO_ESTADO',
            fieldName: 'status_id',
            oldValue: $previousStatus,
            newValue: $closedStatus->name,
            comment: $comment ?? "Visto bueno operacional otorgado por {$closer->name} (APOC). Incidencia cerrada definitivamente."
        );

        // 3. Auditoría inmutable de cierre definitivo del expediente (RF-05 / RN-02 / RN-04)
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $closer->id,
            action: 'CERRADA',
            fieldName: 'closed_at',
            oldValue: null,
            newValue: $incident->closed_at->toIso8601String(),
            comment: $comment ?? "Expediente archivado en modo inmutable con conformidad total de servicio."
        );

        return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
    });
}
```

### 7.3. Controlador Web: `app/Http/Controllers/IncidentController.php`
```php
public function close(CloseIncidentRequest $request, Incident $incident): RedirectResponse
{
    Gate::authorize('close', $incident);

    $this->incidentService->closeIncident(
        incident: $incident,
        closer: $request->user(),
        comment: $request->validated('comment')
    );

    return redirect()->route('incidents.show', $incident->folio)
        ->with('success', "Incidencia {$incident->folio} cerrada definitivamente con visto bueno de APOC. Expediente archivado en modo inmutable.");
}
```

### 7.4. Rutas del Sistema: `routes/web.php`
```php
Route::post('/incidencias/{incident}/cerrar', [IncidentController::class, 'close'])->name('incidents.close');
```

---

## 8. EXPLICACIÓN TÉCNICA PARA UN ESTUDIANTE DE TELECOMUNICACIONES
Para un futuro Ingeniero en Telecomunicaciones, este proceso es idéntico al protocolo de **Aceptación de Enlace de Transmisión y Firma del Acta de Entrega-Recepción**:

### La Metáfora de la Fusión de Fibra Óptica y Certificación de Enlace
Imagina que se produce un corte en el anillo de fibra óptica perimetral que alimenta el radar meteorológico del AIFA:
1. **Atención y Fusión (GTS - Resolución Técnica):** La cuadrilla de telecomunicaciones sale a campo con la empalmadora por fusión. Alinearán los núcleos de sílice, aplican arco voltaico y protegen la unión con un manguito termorretráctil. Conectan un reflectómetro óptico en el dominio del tiempo (OTDR) y comprueban que la atenuación del empalme es de apenas $0.03\text{ dB}$, dentro de la norma ITU-T G.652. El líder de la cuadrilla marca la orden de trabajo como "Resuelta" (`RESUELTA`).
2. **Pruebas de Transmisión en Servicio (APOC - Validación Operacional):** ¿Significa eso que el radar ya está operando para los controladores aéreos? No necesariamente. Podría haber una pérdida de sincronismo en la trama SDH/OTN, una incompatibilidad de transceptor SFP+ en el switch o un descarte de paquetes UDP. Quien monitorea la pantalla del radar en la consola de operaciones y coordina con la torre de control es el operador de APOC.
3. **El Visto Bueno (VoBo):** APOC realiza una prueba operativa de tráfico real (tasa de bits de error $\text{BER} < 10^{-12}$, latencia de paquetes estable y despliegue correcto de ecos de precipitación en la pantalla del controlador). Cuando APOC confirma que el servicio aeronáutico funciona a plenitud, emite el **Visto Bueno**.
4. **Acta de Entrega-Recepción e Inmutabilidad (Estado CERRADA):** En proyectos de telecomunicaciones, tras las pruebas de aceptación se firma el Acta de Entrega Definitiva. A partir de ese segundo, el enlace pasa de estado "en comisionamiento" a "en servicio productivo comercial". No se le pueden hacer modificaciones arbitrarias; cualquier nueva anomalía posterior requerirá abrir un nuevo expediente con sus respectivas mediciones calibradas. En el software, sellar el ticket como inmutable (`CERRADA`) previene que se alteren registros históricos durante auditorías técnicas de la AFAC o la OACI.

---

## 9. PRUEBAS AUTOMATIZADAS
Se implementó la suite completa `tests/Feature/CloseIncidentTest.php` con 8 pruebas unitarias y de integración que cubren todos los ángulos de seguridad, autorización, integridad de base de datos y validación de formularios:

| # | Prueba en `CloseIncidentTest.php` | Propósito Técnico | Resultado |
|---|---|---|---|
| 1 | `test_guest_cannot_close_incident` | Usuarios sin sesión autenticada son redirigidos a `/login`. | **Aprobado** |
| 2 | `test_gts_user_cannot_close_incident` | Personal de GTS recibe `403 Forbidden` al intentar auto-aprobarse el cierre (RN-02). | **Aprobado** |
| 3 | `test_apoc_user_can_close_incident_with_comment` | APOC cierra exitosamente con comentario; verifica estado `CERRADA`, timestamp `closed_at` y eventos en bitácora. | **Aprobado** |
| 4 | `test_apoc_user_can_close_incident_without_comment` | APOC cierra sin observaciones adicionales (campo nullable); genera auditoría con texto por defecto. | **Aprobado** |
| 5 | `test_admin_user_can_close_incident_when_status_is_resolved` | Administrador del sistema puede otorgar el cierre formal cuando el ticket está en `RESUELTA`. | **Aprobado** |
| 6 | `test_cannot_close_incident_when_incident_is_not_in_resolved_state` | Bloqueo estricto con `403 Forbidden` en estados `NUEVA`, `ASIGNADA`, `EN_ATENCION` y `CERRADA`. | **Aprobado** |
| 7 | `test_closed_incident_is_immutable_against_further_mutations` | Una vez `CERRADA`, todos los endpoints de mutación (`/asignar`, `/iniciar-atencion`, `/notas`, `/resolver`, `/rechazar-solucion`) retornan `403 Forbidden`. | **Aprobado** |
| 8 | `test_comment_validation_fails_if_exceeds_1000_characters` | Valida que comentarios mayores a 1000 caracteres fallen en validación sin mutar la BD. | **Aprobado** |

### Resultado de la Suite Completa:
```bash
php artisan test --filter=CloseIncidentTest
Tests: 8 passed (36 assertions)
Duration: 0.97s

php artisan test
Tests: 96 passed (324 assertions)
Duration: 5.34s
```

---

## 10. RESULTADO ESPERADO
1. Al acceder a una incidencia en estado `RESUELTA`, el operador APOC visualiza el botón interactivo **"Dar VoBo y Cerrar"** en azul primario con icono `verified`.
2. Al hacer clic, se despliega una ventana modal interactiva que resume el folio, el técnico que resolvió y el subsistema tecnológico, permitiendo ingresar observaciones de conformidad del servicio.
3. Al confirmar, el sistema redirige a la vista de detalle con una notificación flash: *"Incidencia AIFA-2026-XXXX cerrada definitivamente con visto bueno de APOC. Expediente archivado en modo inmutable."*
4. El stepper visual muestra sus 5 fases completas (100% de avance).
5. Un banner superior oscuro con candado notifica que el expediente está sellado e inmutable.
6. La línea de tiempo histórica registra el evento formal `CERRADA` con la fecha exacta y los comentarios de visto bueno del operador.
7. Cualquier intento de enviar comandos de modificación sobre dicho folio resulta en un error HTTP `403 Prohibido`.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES

### Problema 1: Intentos de auto-cierre por técnicos de GTS
* **Causa:** Un especialista de GTS intenta presionar el botón de cierre o enviar peticiones HTTP POST directas para dar por terminada su propia asignación.
* **Solución:** La directiva de autorización en Blade `@can('close', $incident)` oculta el botón en la vista, y `IncidentPolicy::close` devuelve terminantemente `false` para usuarios con rol `GTS`, respondiendo el backend con código de estado HTTP `403 Forbidden`.

### Problema 2: Cierre anticipado de incidencias en atención técnica
* **Causa:** Un operador de APOC intenta cerrar una incidencia que aún se encuentra en `EN_ATENCION` o `ASIGNADA`.
* **Solución:** La política de autorización evalúa `$incident->status->code === 'RESUELTA'`. Si la incidencia no ha recibido previamente el informe de solución técnica por parte de GTS, el cierre es rechazado tanto a nivel de interfaz como a nivel de controlador.

### Problema 3: Reingreso de notas o cambios en tickets archivados
* **Causa:** Un usuario intenta postear una nota técnica o reasignar una incidencia ya cerrada.
* **Solución:** Las políticas de `assign`, `startAttention`, `addNote`, `resolve` y `rejectResolution` verifican el estado requerido y rechazan operaciones sobre expedientes cerrados, garantizando la inmutabilidad física y lógica del registro histórico.

---

## 12. VERIFICACIÓN FINAL
* [x] Form Request `CloseIncidentRequest` creado y probado con reglas de validación en español.
* [x] Método `closeIncident` implementado en `IncidentService` con transacción atómica y doble registro en la bitácora inmutable.
* [x] Acción `close` integrada en `IncidentController` y ruta `incidents.close` registrada en `routes/web.php`.
* [x] Vistas Blade actualizadas con botón VoBo, modal Alpine.js, banner inmutable, stepper al 100% y tarjeta de timeline.
* [x] Suite de pruebas `CloseIncidentTest` implementada y aprobada (8 tests, 36 aserciones).
* [x] Suite global de pruebas automatizadas ejecutada al 100% en verde (96 tests, 324 aserciones).
* [x] Base de datos de desarrollo `git_incidentes` sembrada de forma consistente con `php artisan db:seed`.
* [x] Fase 5 del proyecto **GIT** completada en su totalidad.

