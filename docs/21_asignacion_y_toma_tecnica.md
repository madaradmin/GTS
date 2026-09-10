# PASO 21: FLUJO OPERACIONAL DE ASIGNACIÓN Y TOMA TÉCNICA DE INCIDENCIAS (GTS)

## 1. OBJETIVO DEL PASO
Implementar, verificar y auditar el flujo operacional de asignación, auto-asignación ("Tomar Incidencia") y reasignación técnica de tickets por parte del personal del Grupo de Seguridad Tecnológica (GTS). El módulo garantiza la transición gobernada de estado `NUEVA` a `ASIGNADA` (RN-02 / RN-03), el enlace inmutable del especialista responsable (`assigned_to_user_id`), el registro obligatorio del timestamp (`assigned_at`) y la emisión de eventos de bitácora en la tabla inmutable de auditoría (`incident_histories`), respaldado por una interfaz interactiva con modal Tailwind/Alpine y botón de acción rápida.

---

## 2. POR QUÉ LO HACEMOS
En la gestión técnica aeroportuaria:
1. **Responsabilidad Operacional y No Repudio:** Una falla técnica reportada por APOC (ej. caída de lector biométrico en filtro de migración) no puede quedar en el limbo operativo. Debe existir un especialista de guardia formalmente asignado como responsable del diagnóstico y reparación.
2. **Doble Modalidad de Despacho:**
   * **Despacho por Coordinación:** Un supervisor o coordinador GTS examina la cola y delega el ticket al técnico calificado para ese subsistema (ej. especialista en CCTV o en redes de fibra).
   * **Auto-asignación / Toma Técnica Rápida:** Un especialista que atiende la consola C2 o monitores de turno puede presionar un solo botón ("Tomar Incidencia") para asumir inmediatamente el ticket.
3. **Automatización de la Máquina de Estados:** Al asignar un técnico a una incidencia en estado `NUEVA`, el sistema efectúa la transición automática a `ASIGNADA`, actualizando el indicador visual del stepper sin requerir pasos manuales redundantes.
4. **Trazabilidad en Reasignaciones:** Si por cambio de turno o relevo de guardia un ticket debe transferirse a otro técnico, el sistema registra el especialista previo, el nuevo especialista y el motivo justificado.

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):** Modelos `Incident`, `Status`, `User` e `IncidentHistory`.
* **Fase 4 (Seguridad y Autorización):** `AssignIncidentRequest`, `IncidentPolicy::assign` (habilidad restringida a personal GTS/Admin y estados no terminales).
* **Fase 5 (Pasos 19 y 20):** Expediente base de incidentes y listado general.

---

## 4. ESTADO ACTUAL
* Entorno operativo con 67 pruebas automatizadas superadas al 100% (198 aserciones).
* Base de datos PostgreSQL con 18 incidencias de prueba y catálogos sincronizados.
* Módulo de listado y búsqueda ([Paso 20](file:///c:/Users/Asus/Desktop/GIT/docs/20_listado_y_busqueda.md)) concluido.

---

## 5. PLAN DE EJECUCIÓN
1. **Lógica de Servicio (`IncidentService::assignIncident`):**
   * Encapsulamiento en `DB::transaction`.
   * Detección de estado inicial: si el estado es `NUEVA`, transicionar a `ASIGNADA` y auditar `CAMBIO_ESTADO`.
   * Asignación de `assigned_to_user_id` y establecimiento de `assigned_at = now()`.
   * Registro de auditoría con acción `ASIGNADA` o `REASIGNADA` detallando técnico anterior, nuevo y observaciones.
2. **Route Model Binding por Folio (`Incident.php`):**
   * Configurar `getRouteKeyName(): string` retornando `'folio'`, permitiendo enlaces limpios en URL (`/incidencias/INC-2026-0001/asignar`).
3. **Acción de Controlador (`IncidentController::assign`):**
   * Validación mediante `AssignIncidentRequest`.
   * Verificación de Gate: `Gate::authorize('assign', $incident)`.
   * Invocación a la capa de servicio y redirección al expediente con mensaje flash diferenciado.
4. **Interfaz Táctica de Expediente (`show.blade.php`):**
   * Inclusión del Stepper FSM de 5 fases con timestamp dinámico de asignación.
   * Botón de acción rápida "Tomar Incidencia" para especialistas GTS cuando el ticket no tiene responsable.
   * Botón "Asignar / Reasignar Especialista" que despliega modal interactivo con selectores de personal GTS.
   * Visualización de avatar, nombre, correo y ficha técnica del técnico responsable.
   * Renderizado de la línea de tiempo de auditoría con eventos cronológicos.
5. **Rutas HTTP (`routes/web.php`):**
   * Registro de `Route::post('/incidencias/{incident}/asignar', [IncidentController::class, 'assign'])->name('incidents.assign');`.
6. **Batería de Pruebas Automatizadas (`AssignIncidentTest.php`):**
   * 8 pruebas exhaustivas cubriendo acceso no autenticado, rechazo a rol APOC, auto-asignación, reasignación, validación de técnicos y prohibición sobre tickets resueltos o cerrados.

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Models/Incident.php` (Modificado: `getRouteKeyName`)
* `app/Services/IncidentService.php` (Modificado: `assignIncident`)
* `app/Http/Controllers/IncidentController.php` (Modificado: `assign` y `show`)
* `resources/views/incidents/show.blade.php` (Modificado: controles tácticos y modal de asignación)
* `routes/web.php` (Modificado: ruta `incidents.assign`)
* `tests/Feature/AssignIncidentTest.php` (Nuevo)
* `database/factories/IncidentFactory.php` (Modificado: generación determinista no colisionable)
* `docs/21_asignacion_y_toma_tecnica.md` (Nuevo)

---

## 7. IMPLEMENTACIÓN TÉCNICA

### Transacción de Asignación y Auditoría Dual (`IncidentService.php`)
```php
public function assignIncident(Incident $incident, User $assigner, User $technician, ?string $comment = null): Incident
{
    return DB::transaction(function () use ($incident, $assigner, $technician, $comment) {
        $isInitialAssignment = is_null($incident->assigned_to_user_id);
        $previousTechnician = $incident->assignedTo?->name ?? 'Sin asignar';
        $previousStatus = $incident->status->name;

        // 1. Transición automática a ASIGNADA si la incidencia es NUEVA (RN-02 / RN-03)
        if ($incident->status->code === 'NUEVA') {
            $assignedStatus = Status::where('code', 'ASIGNADA')->firstOrFail();
            $incident->status_id = $assignedStatus->id;

            $this->auditService->logAction(
                incidentId: $incident->id,
                userId: $assigner->id,
                action: 'CAMBIO_ESTADO',
                fieldName: 'status_id',
                oldValue: $previousStatus,
                newValue: $assignedStatus->name,
                comment: $comment ?? ($assigner->id === $technician->id 
                    ? "Incidencia tomada directamente por {$technician->name}." 
                    : "Incidencia asignada a {$technician->name}.")
            );
        }

        // 2. Actualizar responsable técnico y fecha
        $incident->assigned_to_user_id = $technician->id;
        if (is_null($incident->assigned_at)) {
            $incident->assigned_at = now();
        }
        $incident->save();

        // 3. Auditoría de asignación inmutable
        $auditAction = $isInitialAssignment ? 'ASIGNADA' : 'REASIGNADA';
        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $assigner->id,
            action: $auditAction,
            fieldName: 'assigned_to_user_id',
            oldValue: $previousTechnician,
            newValue: $technician->name,
            comment: $comment ?? ($isInitialAssignment
                ? "Asignado a {$technician->name} por {$assigner->name}."
                : "Reasignado de {$previousTechnician} a {$technician->name} por {$assigner->name}.")
        );

        return $incident->fresh(['category', 'priority', 'status', 'reportedBy', 'assignedTo', 'histories.user', 'attachments']);
    });
}
```

---

## 8. EXPLICACIÓN DIDÁCTICA PARA ESTUDIANTE DE TELECOMUNICACIONES
Para un estudiante de Telecomunicaciones, la asignación técnica de un ticket es análoga a la **conmutación de circuitos, asignación de portadoras y control de flujo con acuse de recibo (ACK)**:
1. **Conmutación de Circuitos y Asignación de Canales Dedicados:**
   En telefonía celular clásica o en enlaces de radio troncalizada (TETRA aeroportuario), cuando una terminal solicita comunicación, el controlador central (Base Station Controller) debe asignarle una ranura de tiempo (*Time Slot*) o una frecuencia portadora específica. De la misma manera, una incidencia en estado `NUEVA` es una señalización de alerta en espera de canal: la acción de asignación vincula el ticket a un "transceptor humano" concreto (el especialista GTS), transformando un evento anónimo en un circuito de atención dedicado y trazable.
2. **Handshake de Tres Vías y Acuse de Recibo (SYN $\rightarrow$ SYN-ACK):**
   Cuando APOC crea la incidencia, emite una solicitud de atención (`SYN`). La toma técnica por el especialista GTS actúa como el acuse de recibo de recepción (`SYN-ACK`), confirmando formalmente: *"He recibido el paquete de falla y asumo la responsabilidad operativa de su resolución"*.
3. **Telemetría de Conmutación (Handoff / Reasignación):**
   Si un avión o móvil se desplaza entre celdas celulares, la red ejecuta un *Handover* transparente, transfiriendo la llamada de la antena A a la antena B sin cortar la comunicación. Cuando un ticket se reasigna de un técnico saliente a uno entrante en cambio de guardia, la base de datos registra de inmediato el evento en la bitácora (`REASIGNADA`), garantizando que la cadena de custodia nunca sufra pérdidas de información.

---

## 9. PRUEBAS Y VALIDACIÓN
Se ejecutó la suite de integración [`tests/Feature/AssignIncidentTest.php`](file:///c:/Users/Asus/Desktop/GIT/tests/Feature/AssignIncidentTest.php):
* `test_guest_cannot_assign_incident`: **PASÓ** (Redirige a `/login`)
* `test_apoc_user_cannot_assign_incident`: **PASÓ** (403 Forbidden por segregación de funciones)
* `test_inactive_gts_user_cannot_assign_incident`: **PASÓ** (403 Forbidden para cuentas inactivas)
* `test_gts_user_can_assign_technician_to_new_incident`: **PASÓ** (Transiciona de `NUEVA` a `ASIGNADA` y audita ambos eventos)
* `test_gts_user_can_self_assign_incident`: **PASÓ** (Toma técnica directa)
* `test_gts_user_can_reassign_already_assigned_incident`: **PASÓ** (Audita `REASIGNADA` con nombres previo y nuevo)
* `test_cannot_assign_non_gts_user`: **PASÓ** (Rechazo de validación si el usuario no es GTS)
* `test_cannot_assign_resolved_or_closed_incident`: **PASÓ** (403 Forbidden en estados terminales o resueltos)

Resultado de la suite:
```sh
php artisan test --filter=AssignIncidentTest
Tests: 8 passed (28 assertions)
Duration: 0.90s
```

Total del sistema global:
```sh
php artisan test
Tests: 67 passed (198 assertions)
Duration: 3.33s
```

---

## 10. RESULTADO ESPERADO
* Pantalla de detalle de incidencia `/incidencias/{folio}` con modal de asignación/reasignación operativo.
* Botón táctico "Tomar Incidencia" disponible con un clic para especialistas GTS.
* Transición automática del stepper a fase 2 (`Asignada`) con timestamp y datos del técnico responsable.
* Registro inalterable de auditoría visualizado en la línea de tiempo.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES
1. **Conflicto de Route Model Binding con folios de texto:**
   * *Causa:* Por defecto Eloquent busca la clave primaria numérica `id`.
   * *Solución:* Sobrescribir `getRouteKeyName()` en `Incident.php` para retornar `'folio'`, permitiendo que rutas como `/incidencias/{incident}/asignar` resuelvan automáticamente por código `INC-YYYY-XXXX`.
2. **Colisión de folios en pruebas repetidas de factories:**
   * *Causa:* Un contador estático fijo originaba duplicación de clave única en PostgreSQL al reejecutar seeders.
   * *Solución:* Emplear `fake()->unique()->numberBetween(1000, 99999)` en `IncidentFactory`.

---

## 12. VERIFICACIÓN FINAL
* 67 pruebas automatizadas ejecutadas y superadas al 100%.
* Base de datos resembrada con catálogo y 18 incidencias operativas.
* Cambios listos para commit en Git.