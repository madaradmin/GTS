# PASO 26: IMPLEMENTACIÓN DE HISTORIAL Y AUDITORÍA (TRAZABILIDAD AERONÁUTICA)

## 1. OBJETIVO DEL PASO
Implementar, blindar y auditar de forma integral el subsistema de **Trazabilidad y Auditoría Histórica (Append-Only)** en **GIT** (Gestión de Incidencias Tecnológicas - APOC / GTS). Este mecanismo provee el registro exhaustivo, inmutable y no repudiable de **quién, qué, cuándo y qué cambió** en cada interacción técnica u operativa sobre un expediente de incidencia. Se incluye la protección estricta contra modificaciones (`updating`) o borrados (`deleting`) a nivel de eventos de modelo Eloquent, consultas periciales optimizadas con scopes de filtrado (`search`, `action`, `user_id`, `incident_id`, `date_from`, `date_to`), un panel web centralizado de bitácora general de auditoría (`/auditoria`), indicadores de actividad (KPIs periciales) y el enlace de navegación táctico en el menú del sistema.

---

## 2. POR QUÉ LO HACEMOS
En un entorno aeroportuario crítico regido por normativas de la OACI (Doc 9734) y la AFAC (Circular Obligatoria de Gestión de Seguridad Operacional):
1. **Principio de No Repudio (*Non-Repudiation*):** Ningún actor operativo (APOC) ni especialista de ingeniería (GTS) puede negar haber creado un ticket, reasignado una guardia, emitido una nota técnica, reportado una solución o devuelto un expediente a atención. La bitácora estampa el identificador único del usuario, su rol y la fecha/hora militar con precisión de milisegundos.
2. **Inmutabilidad Absoluta (*Append-Only Architecture* - RN-04 / RF-05):** A diferencia de las tablas ordinarias de un sistema de información, una bitácora forense jamás admite operaciones `UPDATE` ni `DELETE`. Si un operador comete un error, la corrección debe registrarse como un nuevo evento posterior, preservando la línea de tiempo original para auditorías técnicas e investigaciones de seguridad aeronáutica.
3. **Visibilidad Táctica y Análisis Causa-Raíz:** Al centralizar todos los eventos en un visor pericial con filtros combinados, los supervisores de TI y jefes de APOC pueden rastrear el historial completo de intervenciones sobre un subsistema específico (e.g. CCTV o e-Gates) durante un turno o una ventana de mantenimiento preventivo.
4. **Diferenciales de Estado (*Field Diffs*):** El subsistema no solo guarda una descripción textual; almacena pares estructurados `old_value` $\to$ `new_value`, permitiendo a los ingenieros comparar de forma exacta qué valores de configuración, responsables o estados cambiaron en cada fase.

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):** Tabla `incident_histories` con campos `incident_id`, `user_id`, `action`, `field_name`, `old_value`, `new_value`, `comment` y `created_at` (sin columna `updated_at`).
* **Fase 4 (Seguridad y Autorización):**
  * `IncidentPolicy::viewAudit`: Autorización para consulta de bitácora pericial accesible a usuarios autenticados activos con roles `APOC`, `GTS` y `ADMIN`.
* **Fase 5 (Ciclo de Vida de Incidencias):** Todos los flujos previos (`createIncident`, `assignIncident`, `startAttention`, `addTechnicalNote`, `resolveIncident`, `rejectResolution`, `closeIncident`) integrados con `AuditService::logAction`.

---

## 4. ESTADO ACTUAL
* 108 pruebas automatizadas superadas al 100% (367 aserciones).
* Base de datos PostgreSQL `git_incidentes` con integridad referencial activa.
* Interfaz táctica unificada con tokens de diseño SITIA / AIFA y componentes accesibles.

---

## 5. PLAN DE EJECUCIÓN
1. **Blindaje del Modelo (`IncidentHistory.php`):**
   * Configurar `const UPDATED_AT = null`.
   * Interceptar eventos Eloquent en `booted()` (`updating` y `deleting`) arrojando `\RuntimeException` para bloquear cualquier intento de mutación en runtime.
   * Implementar `scopeFilter(Builder $query, array $filters)` con soporte multiplataforma (`ILIKE` en PostgreSQL / `LIKE` en SQLite) para búsquedas de texto, filtrado por acción, usuario, incidencia y rango de fechas.
   * Proveer accesores/helpers de presentación táctica (`getActionLabel`, `getActionIcon`, `getActionBadgeClasses`).
2. **Ampliación del Servicio (`AuditService.php`):**
   * Conservar `logAction(...)` para inserciones atómicas inmutables.
   * Implementar `getGlobalAuditTrail(array $filters, int $perPage = 20)` con carga impaciente (*eager loading*) optimizada (`incident.category`, `incident.priority`, `incident.status`, `user.role`).
   * Implementar `getStatistics()` para cómputo de métricas agregadas.
   * Implementar `getAvailableActions()` para poblar selectores de filtro.
3. **Controlador Web (`AuditController.php`):**
   * Implementar método `index(Request $request)` con validación de actividad del usuario, recolección de filtros y retorno de vista con datos tipificados.
4. **Rutas del Sistema (`routes/web.php`):**
   * Registrar endpoint `GET /auditoria` protegido por middleware `auth` y `verified`.
5. **Navegación Táctica (`navigation.blade.php`):**
   * Agregar enlace permanente "Auditoría" con icono `manage_history` en menú superior de escritorio y menú móvil.
6. **Vista de Bitácora Pericial (`resources/views/audit/index.blade.php`):**
   * Header con badge "Append-Only".
   * 4 tarjetas de métricas periciales (Total eventos, Cambios de estado, Notas de campo, Resoluciones/Cierres).
   * Barra de filtros combinados con inputs de fecha nativos y botón de limpieza de parámetros.
   * Tabla de alta densidad con timestamps militares, enlaces directos a expedientes, avatares de operador, badges de acción, diferenciales `old_value` $\to$ `new_value` y sellos de inmutabilidad.
   * Paginador de resultados conservando parámetros GET.
7. **Suite de Pruebas Automatizadas (`tests/Feature/AuditTest.php`):**
   * Cobertura de 12 pruebas de integración, autorización, blindaje de inmutabilidad, filtrado y cálculo estadístico.

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Models/IncidentHistory.php` (Modificado: blindaje inmutable, `scopeFilter` y helpers de diseño)
* `app/Services/AuditService.php` (Modificado: consultas globales, estadísticas y catálogo de acciones)
* `app/Http/Controllers/AuditController.php` (Creado: controlador de auditoría)
* `routes/web.php` (Modificado: ruta `audit.index`)
* `resources/views/layouts/navigation.blade.php` (Modificado: enlaces a la bitácora de auditoría)
* `resources/views/audit/index.blade.php` (Creado: interfaz táctica de bitácora general de auditoría)
* `tests/Feature/AuditTest.php` (Creado: 12 pruebas unitarias y de integración)
* `docs/26_historial_y_auditoria.md` (Creado: documentación técnica y pedagógica)

---

## 7. IMPLEMENTACIÓN DETALLADA

### 7.1. Blindaje Inmutable y Filtrado: `app/Models/IncidentHistory.php`
```php
protected static function booted(): void
{
    static::updating(function () {
        throw new RuntimeException('La bitácora de auditoría es estrictamente inmutable (Append-Only) por normativa aeronáutica.');
    });

    static::deleting(function () {
        throw new RuntimeException('La eliminación de registros de auditoría histórica está terminantemente prohibida.');
    });
}

public function scopeFilter(Builder $query, array $filters): Builder
{
    $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

    if (!empty($filters['search'])) {
        $search = '%' . trim($filters['search']) . '%';
        $query->where(function (Builder $q) use ($search, $likeOperator) {
            $q->where('comment', $likeOperator, $search)
                ->orWhere('field_name', $likeOperator, $search)
                ->orWhere('old_value', $likeOperator, $search)
                ->orWhere('new_value', $likeOperator, $search)
                ->orWhereHas('incident', function (Builder $iq) use ($search, $likeOperator) {
                    $iq->where('folio', $likeOperator, $search)
                       ->orWhere('title', $likeOperator, $search);
                });
        });
    }

    if (!empty($filters['action'])) {
        $query->where('action', $filters['action']);
    }

    if (!empty($filters['user_id'])) {
        $query->where('user_id', $filters['user_id']);
    }

    if (!empty($filters['date_from'])) {
        $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
    }

    if (!empty($filters['date_to'])) {
        $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
    }

    return $query;
}
```

### 7.2. Métricas y Consultas Periciales: `app/Services/AuditService.php`
```php
public function getGlobalAuditTrail(array $filters = [], int $perPage = 20): LengthAwarePaginator
{
    return IncidentHistory::query()
        ->with([
            'incident.category',
            'incident.priority',
            'incident.status',
            'user.role',
        ])
        ->filter($filters)
        ->orderBy('created_at', 'desc')
        ->orderBy('id', 'desc')
        ->paginate($perPage);
}

public function getStatistics(): array
{
    return [
        'total_events' => IncidentHistory::count(),
        'state_changes' => IncidentHistory::where('action', 'CAMBIO_ESTADO')->count(),
        'technical_notes' => IncidentHistory::where('action', 'NOTA_TECNICA')->count(),
        'resolutions' => IncidentHistory::where('action', 'RESOLUCION')->count(),
        'rejections' => IncidentHistory::where('action', 'RECHAZO_SOLUCION')->count(),
        'closures' => IncidentHistory::where('action', 'CERRADA')->count(),
    ];
}
```

### 7.3. Controlador Web: `app/Http/Controllers/AuditController.php`
```php
public function index(Request $request): View
{
    abort_if(!$request->user()->is_active, 403, 'Usuario inactivo.');

    $filters = $request->only([
        'search',
        'action',
        'user_id',
        'folio',
        'date_from',
        'date_to',
    ]);

    $histories = $this->auditService->getGlobalAuditTrail($filters, 20);
    $statistics = $this->auditService->getStatistics();
    $actions = $this->auditService->getAvailableActions();
    $users = User::with('role')->orderBy('name')->get();

    return view('audit.index', [
        'histories' => $histories,
        'statistics' => $statistics,
        'actions' => $actions,
        'users' => $users,
        'filters' => $filters,
    ]);
}
```

---

## 8. EXPLICACIÓN TÉCNICA PARA UN ESTUDIANTE DE TELECOMUNICACIONES

### La Caja Negra Aeronáutica (FDR) y el Syslog / SNMP Trap de un Centro de Operaciones de Red (NOC)
Para un Ingeniero en Telecomunicaciones, el subsistema de auditoría implementado es el equivalente digital exacto al **Flight Data Recorder (FDR / Caja Negra)** de una aeronave y al **Servidor Centralizado de Syslog / SNMP Traps** de un proveedor de telecomunicaciones:

1. **El Principio de Append-Only (Solo Adición):** En un enrutador de borde (e.g. Cisco ASR o Juniper MX) o en una estación terrena satelital, los registros de eventos (`syslog` bajo estándar RFC 5424) son flujos de tramas unidireccionales UDP/TLS enviados a un servidor central. Una vez que un paquete de registro llega al servidor, **es físicamente imposible sobrescribir una línea pasada**. Si la interfaz óptica GigabitEthernet 0/1/0 se cayó a las 14:03:02 UTC y volvió a subir a las 14:05:18 UTC, no se puede borrar la caída; queda el registro `LINEPROTO-5-UPDOWN: Line protocol on Interface Gi0/1/0, changed state to down`. De la misma forma, nuestro modelo `IncidentHistory` bloquea en runtime cualquier invocación a `$history->update()` o `$history->delete()`.
2. **No Repudio y Peritaje Forense:** Si ocurre un incidente grave de interferencia en las frecuencias aeronáuticas VHF (118.000 – 136.975 MHz) de la torre de control, los peritos de telecomunicaciones extraen la bitácora cronológica: ¿quién apagó el transmisor principal?, ¿a qué hora exacta se conmutó al transmisor de respaldo?, ¿qué técnico manipuló la matriz de audio? Nuestro sistema garantiza que cada asignación técnica, nota de campo o solución tenga un timestamp inalterable ligado a la identidad digital del ingeniero de guardia.
3. **Diferenciales de Estado (*Field Diffs*):** Al igual que una herramienta de gestión de configuración de red (e.g. Rancid u Oxidized) guarda el *diff* de configuración (`- ip address 10.0.1.1` $\to$ `+ ip address 10.0.1.2`), la bitácora registra `old_value` $\to$ `new_value`, facilitando la reconstrucción exacta del comportamiento del sistema antes y después de cada intervención técnica.

---

## 9. PRUEBAS AUTOMATIZADAS
Se implementó la suite completa [`tests/Feature/AuditTest.php`](file:///c:/Users/Asus/Desktop/GIT/tests/Feature/AuditTest.php) compuesta por 12 pruebas automatizadas:

| # | Prueba en `AuditTest.php` | Propósito Técnico | Resultado |
|---|---|---|---|
| 1 | `test_guest_is_redirected_to_login_when_accessing_audit_log` | Usuarios no autenticados son redirigidos a `/login`. | **Aprobado** |
| 2 | `test_authenticated_apoc_user_can_access_audit_log` | Operadores APOC acceden a `/auditoria` y reciben vista con datos y métricas. | **Aprobado** |
| 3 | `test_authenticated_gts_user_can_access_audit_log` | Especialistas GTS acceden a `/auditoria` para consulta técnica. | **Aprobado** |
| 4 | `test_authenticated_admin_user_can_access_audit_log` | Administradores acceden a la bitácora global sin restricciones. | **Aprobado** |
| 5 | `test_inactive_user_cannot_access_audit_log` | Usuarios desactivados reciben error HTTP `403 Forbidden`. | **Aprobado** |
| 6 | `test_incident_history_is_strictly_immutable_against_updates` | Intentar ejecutar `$history->save()` con cambios arroja `RuntimeException`. | **Aprobado** |
| 7 | `test_incident_history_is_strictly_immutable_against_deletion` | Intentar ejecutar `$history->delete()` arroja `RuntimeException`. | **Aprobado** |
| 8 | `test_audit_log_filters_by_action` | Filtrado correcto por tipo de evento pericial (`action=RESOLUCION`). | **Aprobado** |
| 9 | `test_audit_log_filters_by_user` | Filtrado por identificador de operador responsable. | **Aprobado** |
| 10 | `test_audit_log_filters_by_search_term` | Búsqueda textual por tokens en folio, comentario o valores modificados. | **Aprobado** |
| 11 | `test_audit_log_filters_by_date_range` | Filtrado preciso por rango de fechas `date_from` y `date_to`. | **Aprobado** |
| 12 | `test_audit_service_statistics_calculation` | Cómputo matemático exacto de contadores agregados por tipo de evento. | **Aprobado** |

### Resultado de Ejecución:
```powershell
php artisan test --filter=AuditTest
# {"tool":"phpunit","result":"passed","tests":12,"passed":12,"assertions":43,"duration_ms":2039}

php artisan test
# {"tool":"phpunit","result":"passed","tests":108,"passed":108,"assertions":367,"duration_ms":5587}
```

---

## 10. RESULTADO ESPERADO
1. El menú superior exhibe el nuevo acceso directo **"Auditoría"** con icono `manage_history`.
2. Al ingresar a `/auditoria`, el usuario visualiza el panel pericial con cuatro tarjetas de telemetría: Total de Eventos, Cambios de Estado, Notas de Campo y Resoluciones/Cierres.
3. El panel de filtros permite seleccionar de forma combinada términos de búsqueda, tipos de acción (`CREADA`, `ASIGNACION`, `CAMBIO_ESTADO`, `NOTA_TECNICA`, `RESOLUCION`, `RECHAZO_SOLUCION`, `CERRADA`), operador específico y rango de fechas.
4. La tabla táctica presenta en riguroso orden cronológico inverso cada evento, con timestamps militares formateados, enlace directo al expediente (`INC-2026-XXXX`), datos y avatar del usuario responsable, badges de color con iconos tácticos, caja diferencial `old_value` $\to$ `new_value`, descripción técnica y sello de seguridad.
5. Cualquier intento de actualizar o eliminar un registro histórico mediante código o consola interactiva es bloqueado de inmediato por los interceptores de modelo arrojando una excepción de inmutabilidad.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES

### Problema 1: Intentos de manipulación o alteración de bitácora
* **Causa:** Un desarrollador o script administrativo intenta modificar un registro de auditoría mediante `$history->update([...])`.
* **Solución:** El interceptor `static::updating()` en el método `booted()` de `IncidentHistory` arroja una `RuntimeException` inmediata que cancela la transacción y protege el archivo.

### Problema 2: Incompatibilidad de operadores de búsqueda entre motores SQL
* **Causa:** `ILIKE` solo es soportado por PostgreSQL, mientras que SQLite en el entorno de pruebas en memoria (`:memory:`) requiere `LIKE`.
* **Solución:** Detección dinámica del driver en `scopeFilter`:
  ```php
  $likeOperator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
  ```

### Problema 3: Pérdida de parámetros de filtro al paginar resultados
* **Causa:** Al hacer clic en la página 2, Laravel por defecto solo genera `?page=2`, descartando `search` o `action`.
* **Solución:** Invocar `$histories->withQueryString()->links()` en la vista Blade para concatenar automáticamente todos los parámetros GET activos.

---

## 12. VERIFICACIÓN FINAL
* [x] Modelo `IncidentHistory` blindado contra operaciones `UPDATE` y `DELETE` con excepciones `RuntimeException`.
* [x] Scopes avanzados de filtrado implementados con soporte multiplataforma PostgreSQL / SQLite.
* [x] `AuditService` ampliado con consultas periciales paginadas, métricas estadísticas y catálogo de acciones.
* [x] `AuditController` implementado y registrado en `routes/web.php` (`GET /auditoria`).
* [x] Enlace "Auditoría" agregado a la navegación principal y responsiva.
* [x] Vista táctica de auditoría `resources/views/audit/index.blade.php` diseñada con alta densidad de datos, KPIs periciales y tokens SITIA / AIFA.
* [x] Suite `AuditTest.php` creada con 12 pruebas completas aprobadas al 100%.
* [x] Suite global de pruebas ejecutada con éxito (108 tests, 367 aserciones, 100% verde).
* [x] Base de datos de desarrollo `git_incidentes` sembrada de forma consistente con `php artisan db:seed`.
* [x] **Paso 26 completado en su totalidad.**

