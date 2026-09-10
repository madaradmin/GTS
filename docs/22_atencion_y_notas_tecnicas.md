# PASO 22: ATENCIÓN TÉCNICA, DIAGNÓSTICO EN SITIO Y BITÁCORA DE CAMPO (NOTAS TÉCNICAS)

## 1. OBJETIVO DEL PASO
Implementar, verificar y auditar la fase activa de atención técnica y diagnóstico de fallas en **GIT** (Gestión de Incidencias Tecnológicas - APOC / GTS). El módulo permite la transición operacional del ticket al estado `EN_ATENCION` (RF-05 / RN-02), la captura del timestamp exacto de inicio de labores (`attended_at`), la auto-asignación técnica si el ticket no contaba previamente con responsable (RN-03) y el registro continuo de notas técnicas de campo y sustitución de hardware en la bitácora inmutable de trazabilidad (`incident_histories` con acción `NOTA_TECNICA`), validado mediante Form Request y protegido por la política de autorización RBAC (`addNote`).

---

## 2. POR QUÉ LO HACEMOS
En un entorno operacional aeronáutico crítico:
1. **Medición Precisa del MTTR (Mean Time to Repair):** La diferencia entre `reported_at` y `attended_at` mide el tiempo de respuesta inicial de GTS (*Time to Respond*); la diferencia entre `attended_at` y `resolved_at` mide el tiempo real de diagnóstico y reparación técnica (*Time to Repair*). Sin esta fase, las métricas de desempeño de los contratos de servicio (SLA) serían ambiguas.
2. **Registro Detallado de Maniobras de Campo:** Los especialistas que intervienen fibra óptica, switches de pista, cámaras PTZ o lectores de torniquetes deben documentar diagnósticos intermedios (ej. mediciones de atenuación óptica, cambio de fusibles, reubicación de cables patch cord) antes de declarar el problema como formalmente resuelto.
3. **Visibilidad Operativa para APOC:** El Centro de Operaciones Aeroportuarias monitorea en tiempo real qué tickets están activamente siendo atendidos en sitio por cuadrillas técnicas, evitando despachos duplicados o incertidumbre operativa.

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):** Modelos `Incident`, `Status`, `User` e `IncidentHistory`.
* **Fase 4 (Seguridad y Autorización):** `AddNoteRequest`, `IncidentPolicy::startAttention` e `IncidentPolicy::addNote`.
* **Fase 5 (Pasos 19, 20 y 21):** Creación, listado general y asignación técnica previa.

---

## 4. ESTADO ACTUAL
* Sistema con 75 pruebas automatizadas superadas al 100% (231 aserciones).
* Base de datos PostgreSQL con 30 incidencias de prueba en distintos estados operativos.
* Módulo de asignación técnica ([Paso 21](file:///c:/Users/Asus/Desktop/GIT/docs/21_asignacion_y_toma_tecnica.md)) operativo.

---

## 5. PLAN DE EJECUCIÓN
1. **Capa de Servicio (`IncidentService.php`):**
   * Implementar `startAttention(Incident $incident, User $user, ?string $comment = null): Incident`:
     * Autoasignar al técnico si la incidencia carece de responsable.
     * Transicionar a `EN_ATENCION` y actualizar `attended_at = now()`.
     * Registrar auditoría inmutable de cambio de estado.
   * Implementar `addTechnicalNote(Incident $incident, User $user, string $note): IncidentHistory`:
     * Registrar en la bitácora un evento con acción `NOTA_TECNICA`, autor y texto descriptivo.
2. **Acciones de Controlador (`IncidentController.php`):**
   * `startAttention`: Invoca `startAttention()` previa autorización por Gate y redirige con mensaje flash.
   * `addNote`: Valida mediante `AddNoteRequest`, invoca `addTechnicalNote()` y recarga la ficha técnica.
3. **Rutas Web (`routes/web.php`):**
   * `POST /incidencias/{incident}/iniciar-atencion` (`incidents.start-attention`).
   * `POST /incidencias/{incident}/notas` (`incidents.add-note`).
4. **Diseño de Interfaz (`resources/views/incidents/show.blade.php`):**
   * Botón táctico "Iniciar Atención" en la cabecera cuando el ticket está en `NUEVA` o `ASIGNADA`.
   * Banner de alerta táctica pulsante de "Atención Activa" con hora de inicio cuando el estado es `EN_ATENCION`.
   * Formulario de captura de notas técnicas de campo para personal GTS.
   * Renderizado visual destacado para eventos de tipo `NOTA_TECNICA` en la bitácora cronológica.
5. **Suite de Pruebas Automatizadas (`AttentionAndTechnicalNotesTest.php`):**
   * 8 casos de prueba exhaustivos cubriendo permisos, transiciones de estado, auto-asignación, validaciones de notas y bloqueos en estados terminales.

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Services/IncidentService.php` (Modificado: `startAttention` y `addTechnicalNote`)
* `app/Http/Controllers/IncidentController.php` (Modificado: acciones `startAttention` y `addNote`)
* `routes/web.php` (Modificado: rutas de inicio de atención y notas)
* `resources/views/incidents/show.blade.php` (Modificado: botón de atención, banner activo, formulario de notas y diseño de bitácora)
* `tests/Feature/AttentionAndTechnicalNotesTest.php` (Nuevo)
* `docs/22_atencion_y_notas_tecnicas.md` (Nuevo)

---

## 7. IMPLEMENTACIÓN TÉCNICA

### Transición a Estado EN_ATENCION y Registro de Notas (`IncidentService.php`)
```php
public function startAttention(Incident $incident, User $user, ?string $comment = null): Incident
{
    return DB::transaction(function () use ($incident, $user, $comment) {
        $previousStatus = $incident->status->name;

        // Auto-asignación si el ticket no contaba con técnico previo (RN-02 / RN-03)
        if (is_null($incident->assigned_to_user_id)) {
            $incident->assigned_to_user_id = $user->id;
            $incident->assigned_at = now();

            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $user->id,
                action: 'ASIGNADA',
                fieldName: 'assigned_to_user_id',
                oldValue: 'Sin asignar',
                newValue: $user->name,
                comment: "Incidencia asumida directamente al iniciar atención técnica."
            );
        }

        // Transición de estado a EN_ATENCION
        $inAttentionStatus = Status::where('code', 'EN_ATENCION')->firstOrFail();
        $incident->status_id = $inAttentionStatus->id;
        $incident->attended_at = now();
        $incident->save();

        // Auditoría inmutable de cambio de estado (RN-04)
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $user->id,
            action: 'CAMBIO_ESTADO',
            fieldName: 'status_id',
            oldValue: $previousStatus,
            newValue: $inAttentionStatus->name,
            comment: $comment ?? "Atención técnica iniciada por el especialista {$user->name}."
        );

        return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
    });
}

public function addTechnicalNote(Incident $incident, User $user, string $note): IncidentHistory
{
    return $this->auditService->logAction(
        incidentId: $incident->id,
        userId: $user->id,
        action: 'NOTA_TECNICA',
        fieldName: null,
        oldValue: null,
        newValue: null,
        comment: trim($note)
    );
}
```

---

## 8. EXPLICACIÓN DIDÁCTICA PARA ESTUDIANTE DE TELECOMUNICACIONES
Para un ingeniero o estudiante de Telecomunicaciones, la atención técnica activa y la bitácora de campo equivalen a una **sesión de mantenimiento en consola OAM (Operations, Administration and Maintenance) y registros de Syslog**:
1. **Loopback Testing y Estado de Diagnóstico Activo:**
   En enlaces troncales E1/T1 o interfaces GigabitEthernet perimetrales, cuando se detecta pérdida de paquetes, el ingeniero de red no declara el enlace reparado de inmediato; primero pone el puerto en modo de pruebas (*Loopback Mode* o estado de diagnóstico). Esto equivale al estado `EN_ATENCION`: el sistema señaliza a toda la red aeroportuaria que el subsistema está bajo intervención activa de ingeniería y que cualquier variación de estado es esperada.
2. **Syslog Técnico Inmutable (Syslog Severity Levels):**
   En conmutadores Cisco o routers Juniper, cada evento relevante genera un mensaje de Syslog con fecha, hora en microsegundos, proceso y descripción del evento. Nuestra acción `NOTA_TECNICA` opera exactamente como un servidor Syslog centralizado: preserva en un modelo de sólo adición (*Append-Only*) cada ajuste de hardware (ej. "Reemplazo de módulo SFP en switch SW-P14B"), garantizando que si se produce una falla posterior, exista un registro forense completo de qué parámetros se modificaron en campo.

---

## 9. PRUEBAS Y VALIDACIÓN
Se ejecutó la suite de pruebas de integración [`tests/Feature/AttentionAndTechnicalNotesTest.php`](file:///c:/Users/Asus/Desktop/GIT/tests/Feature/AttentionAndTechnicalNotesTest.php):
* `test_guest_cannot_start_attention_or_add_notes`: **PASÓ** (Redirige a `/login`)
* `test_apoc_user_cannot_start_attention_nor_add_notes`: **PASÓ** (403 Forbidden por segregación SoD)
* `test_gts_user_can_start_attention_from_assigned_state`: **PASÓ** (Transición a `EN_ATENCION` con timestamp `attended_at`)
* `test_gts_user_can_start_attention_from_new_state_with_auto_assignment`: **PASÓ** (Auto-asignación y transición atómica)
* `test_cannot_start_attention_on_resolved_or_closed_incident`: **PASÓ** (403 Forbidden en tickets concluidos)
* `test_gts_user_can_add_technical_note_when_incident_is_in_attention`: **PASÓ** (Persistencia de nota en bitácora)
* `test_cannot_add_technical_note_when_incident_is_not_in_attention`: **PASÓ** (Restricción por máquina de estados)
* `test_technical_note_validation_rules`: **PASÓ** (Validación de longitud mínima de 5 caracteres)

Resultado de la suite:
```sh
php artisan test --filter=AttentionAndTechnicalNotesTest
Tests: 8 passed (33 assertions)
Duration: 1.00s
```

Total del sistema global:
```sh
php artisan test
Tests: 75 passed (231 assertions)
Duration: 4.28s
```

---

## 10. RESULTADO ESPERADO
* Botón "Iniciar Atención" accesible en `/incidencias/{folio}` para personal técnico.
* Activación del banner informativo con indicador pulsante y avance del stepper a fase 3 (`En Atención`).
* Formulario para redactar notas técnicas con almacenamiento inmediato en la bitácora inmutable.
* Visualización destacada de las notas técnicas dentro de la línea de tiempo del expediente.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES
1. **Intento de registrar notas en tickets en espera o cerrados:**
   * *Causa:* Personal técnico intenta documentar observaciones antes de pulsar "Iniciar Atención" o después de que el ticket fue resuelto.
   * *Solución:* Política `addNote` en `IncidentPolicy` que verifica de forma estricta `$incident->status->code === 'EN_ATENCION'`, impidiendo registros fuera de la ventana operativa de atención.
2. **Pérdida de trazabilidad al omitir la asignación antes de atender:**
   * *Causa:* Un técnico de campo inicia atención de un ticket en estado `NUEVA` sin asignarse previamente.
   * *Solución:* Automatización en `IncidentService::startAttention()` que detecta la ausencia de responsable y autoasigna al técnico en la misma transacción, cumpliendo simultáneamente las reglas RN-02, RN-03 y RN-04.

---

## 12. VERIFICACIÓN FINAL
* 75 pruebas automatizadas ejecutadas y superadas al 100%.
* Base de datos local PostgreSQL con 30 incidencias operativas y datos de prueba.
* Cambios confirmados en Git con el commit correspondiente.