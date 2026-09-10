# ROLES, PERMISOS Y REGLAS DE AUTORIZACIÓN (RBAC)
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Modelo de Seguridad:** Role-Based Access Control (RBAC) + Laravel Policies  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. MODELO CONCEPTUAL DE AUTORIZACIÓN

El sistema **GIT** implementa el principio de **Mínimo Privilegio (*Least Privilege*)** y **Defensa en Profundidad (*Defense in Depth*)**. La seguridad no descansa únicamente en ocultar o deshabilitar botones en la interfaz visual (Blade), sino que se valida obligatoriamente en tres barreras sucesivas en el servidor:

```text
                                  PETICIÓN HTTP
                                       │
                                       ▼
                     ┌───────────────────────────────────┐
                     │     BARRERA 1: Middleware Auth    │ (¿Está autenticado?)
                     └─────────────────┬─────────────────┘
                                       │
                                       ▼
                     ┌───────────────────────────────────┐
                     │     BARRERA 2: Middleware Rol     │ (¿Pertenece al grupo permitido?)
                     └─────────────────┬─────────────────┘
                                       │
                                       ▼
                     ┌───────────────────────────────────┐
                     │     BARRERA 3: Laravel Policy     │ (¿Tiene permiso sobre este
                     └─────────────────┬─────────────────┘  registro y en este estado?)
                                       │
                                       ▼
                     ┌───────────────────────────────────┐
                     │         EJECUCIÓN SEGURA          │ (IncidentService)
                     └───────────────────────────────────┘
```

---

### 2. DEFINICIÓN FORMAL DE ROLES

1. **Operador APOC (`apoc`):**
   * **Perfil:** Personal del Centro de Operaciones Aeroportuarias de guardia. Monitorea la continuidad operacional de terminales, pistas y filtros.
   * **Responsabilidad:** Detectar la avería, reportar con precisión la ubicación y síntoma, y certificar formalmente que el sistema quedó operativo para cerrar el ticket.

2. **Especialista / Personal GTS (`gts`):**
   * **Perfil:** Ingenieros y técnicos de soporte en campo especializados en subsistemas aeroportuarios (CCTV, Acceso, FIDS, TETRA, Redes).
   * **Responsabilidad:** Tomar el ticket, diagnosticar en sitio o en consola, registrar notas técnicas de avance, reparar la infraestructura y certificar la solución técnica.

3. **Administrador del Sistema (`admin`):**
   * **Perfil:** Jefe de Seguridad Tecnológica o Administrador de Infraestructura IT.
   * **Responsabilidad:** Gestión de cuentas de usuario, mantenimiento de catálogos y auditoría global.

---

### 3. MATRIZ DE CONTROL DE ACCESO (RBAC MATRIX)

| Permiso / Acción Operativa | Código de Habilidad (Ability) | APOC | GTS | ADMIN | Regla de Validación de Negocio |
|---|---|:---:|:---:|:---:|---|
| **Acceso al Dashboard** | `dashboard.view` | Sí | Sí | Sí | El contenido y filtros se adaptan al contexto del rol. |
| **Crear Nueva Incidencia** | `incident.create` | **Sí** | No* | Sí | APOC es el canal oficial de reporte; GTS atiende. (*Excepción técnica autorizada). |
| **Consultar Listado General** | `incident.viewAny` | Sí | Sí | Sí | Todos los roles autorizados pueden ver la cola operativa. |
| **Ver Detalle de Incidencia** | `incident.view` | Sí | Sí | Sí | Acceso completo al expediente, stepper y evidencias. |
| **Auto-asignarse Incidencia** | `incident.selfAssign` | **No** | **Sí** | Sí | Solo especialistas GTS pueden tomar un ticket en estado `NUEVA`. |
| **Reasignar a otro Técnico** | `incident.reassign` | **No** | **Sí** | Sí | Solo personal GTS o Admin puede transferir un ticket a otro técnico. |
| **Iniciar Atención (`EN ATENCIÓN`)** | `incident.startAttention` | **No** | **Sí** | Sí | Requiere que la incidencia esté previamente `ASIGNADA`. |
| **Agregar Notas Técnicas** | `incident.addNote` | **No** | **Sí** | Sí | Solo GTS registra bitácora técnica de campo en estado `EN ATENCIÓN`. |
| **Resolver Incidencia (`RESUELTA`)** | `incident.resolve` | **No** | **Sí** | Sí | Requiere registrar obligatoriamente la solución técnica. |
| **Cerrar Incidencia (`CERRADA`)** | `incident.close` | **Sí** | **No** | Sí | **Segregación de funciones:** GTS no puede auto-aprobarse el cierre. |
| **Ver Historial de Auditoría** | `incident.viewAudit` | Sí | Sí | Sí | Bitácora transparente y visible para fines periciales. |
| **Descargar Evidencias** | `incident.downloadAttachment` | Sí | Sí | Sí | Solo usuarios autenticados con acceso al ticket. |
| **Eliminar Incidencia** | `incident.delete` | **No** | **No** | **No** | **Prohibido.** En aviación civil no se eliminan tickets; solo se cierran o cancelan formalmente con auditoría. |

---

### 4. REGLAS DE SEGREGACIÓN DE FUNCIONES (SOD - SEGREGATION OF DUTIES)

Para cumplir con estándares de auditoría aeroportuaria e informática:

1. **Principio de Doble Control en el Cierre:**
   * El especialista GTS que resolvió la falla **no tiene autorización** para dar el cierre definitivo (`CERRADA`).
   * El cierre administrativo lo otorga el operador APOC tras validar que el servicio (cámara, torniquete o pantalla) efectivamente recuperó su operación normal.
2. **Prohibición de Manipulación de Asignación por APOC:**
   * El operador APOC no asigna técnicos directamente; reporta la necesidad. GTS gestiona internamente sus recursos humanos según guardia y especialidad técnica.
3. **Inmutabilidad Post-Resolución:**
   * Una vez que una incidencia pasa a estado `RESUELTA`, ni APOC ni GTS pueden alterar la descripción original del problema ni eliminar evidencias adjuntas.

---

### 5. ESPECIFICACIÓN DE POLICIES EN LARAVEL (`IncidentPolicy`)

Cada método de la política devolverá un valor booleano evaluando tanto el rol del usuario como el estado actual de la entidad:

```php
// Ejemplo conceptual de IncidentPolicy.php

public function assign(User $user, Incident $incident): bool
{
    // Solo GTS o Admin pueden asignar, y la incidencia no debe estar cerrada
    return ($user->hasRole('gts') || $user->hasRole('admin'))
        && in_array($incident->status->code, ['NUEVA', 'ASIGNADA', 'EN_ATENCION']);
}

public function resolve(User $user, Incident $incident): bool
{
    // Solo GTS puede resolver, y debe estar en atención
    return ($user->hasRole('gts') || $user->hasRole('admin'))
        && $incident->status->code === 'EN_ATENCION';
}

public function close(User $user, Incident $incident): bool
{
    // Solo APOC o Admin pueden cerrar, y debe estar resuelta previamente
    return ($user->hasRole('apoc') || $user->hasRole('admin'))
        && $incident->status->code === 'RESUELTA';
}
```

