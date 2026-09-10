# DISEÑO DEL FLUJO DE ESTADOS DE UNA INCIDENCIA (FSM)
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Modelo:** Máquina de Estados Finitos (Finite State Machine - FSM)  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. MODELO FORMAL DEL CICLO DE VIDA

El ciclo de vida de toda incidencia tecnológica en **GIT** se rige por una **Máquina de Estados Finitos Determinista**. Una incidencia solo puede existir en exactamente **uno** de 5 estados discretos en un momento dado:

```text
    ┌───────────┐
    │ 1. NUEVA  │  (Reportada por APOC en espera de guardia GTS)
    └─────┬─────┘
          │  Acción: Asignar a especialista o Auto-asignarse
          ▼
    ┌───────────┐
    │2.ASIGNADA │  (Asignada formalmente a técnico GTS con especialidad)
    └─────┬─────┘
          │  Acción: Arribar a sitio / Iniciar diagnóstico en consola
          ▼
 ┌─────────────────┐
 │ 3. EN ATENCIÓN  │◄────────┐  (Técnico en campo registrando notas técnicas)
 └────────┬────────┘         │
          │                  │  Acción: Rechazar solución (APOC detecta que falla persiste)
          │ Acción: Resolver │  Reingreso justificado a atención técnica
          ▼                  │
    ┌───────────┐            │
    │4. RESUELTA│────────────┘  (GTS certifica solución técnica con descripción obligatoria)
    └─────┬─────┘
          │  Acción: Validación y VoBo final de APOC
          ▼
    ┌───────────┐
    │ 5. CERRADA│  (Cierre operacional definitivo e inmutable)
    └───────────┘
```

---

### 2. DEFINICIÓN OPERATIVA DE LOS ESTADOS

| # | Estado | Código DB | Color UI (Tailwind) | Descripción Operativa en el Aeropuerto |
|---|---|---|---|---|
| **1** | **Nueva** | `NUEVA` | Cyan / Radio (`#3B82F6`) | Reporte registrado por APOC. Aún no cuenta con técnico asignado ni diagnóstico iniciado. |
| **2** | **Asignada** | `ASIGNADA` | Púrpura / Acceso (`#8B5CF6`) | La coordinación GTS o el propio especialista asumió la responsabilidad del ticket. |
| **3** | **En Atención** | `EN_ATENCION` | Ámbar / Alerta (`#F59E0B`) | Especialista trabajando en sitio (pista, terminal, IDF) o en consola. Genera notas técnicas. |
| **4** | **Resuelta** | `RESUELTA` | Esmeralda (`#10B981`) | El equipo tecnológico recuperó su servicio. GTS documentó la solución y espera visto bueno. |
| **5** | **Cerrada** | `CERRADA` | Gris Neutro (`#64748B`) | APOC constató la restitución del servicio en operación aérea y cerró el expediente formal. |

---

### 3. MATRIZ DE TRANSICIONES DE ESTADO

| Estado Origen | Estado Destino | Acción / Evento | Rol Facultado | Precondiciones Obligatorias | Efectos Colaterales (Side Effects) |
|---|---|---|---|---|---|
| `[INICIO]` | `NUEVA` | Despacho de Ticket | APOC / Admin | Formulario válido (título, categoría, ubicación, prioridad). | Generación atómica de folio `INC-YYYY-XXXX`. Registro en `incident_histories`. |
| `NUEVA` | `ASIGNADA` | Asignar Especialista | GTS / Admin | Seleccionar un usuario con rol `gts` activo. | Se fija `assigned_to_user_id`. Registro de auditoría de asignación. |
| `NUEVA` | `EN_ATENCION` | Auto-atención Inmediata | GTS / Admin | Técnico GTS toma el ticket directamente. | Se autoasigna al usuario autenticado y pasa directo a atención. |
| `ASIGNADA` | `EN_ATENCION` | Iniciar Diagnóstico | GTS Asignado / Admin | La incidencia debe estar previamente en `ASIGNADA`. | Dispara contador de SLA de atención técnica. Registro de evento. |
| `ASIGNADA` | `ASIGNADA` | Reasignación | GTS / Admin | Cambiar de especialista (e.g. por cambio de guardia). | Actualiza `assigned_to_user_id`. Justificación registrada en auditoría. |
| `EN_ATENCION` | `RESUELTA` | Certificar Solución | GTS Asignado / Admin | **Obligatorio:** Captura de texto de solución aplicada ($\ge 15$ caracteres). | Fija `resolved_at = NOW()`, registra `solution_description` y auditoría. |
| `RESUELTA` | `EN_ATENCION` | Rechazo de Solución | APOC / Admin | La falla persiste tras pruebas operacionales de APOC. Justificación obligatoria. | Borra `resolved_at`, registra evento `RECHAZO_SOLUCION` con motivo. |
| `RESUELTA` | `CERRADA` | Visto Bueno y Cierre | APOC / Admin | Solo desde estado `RESUELTA`. Validación de servicio restituido. | Fija `closed_at = NOW()`. El ticket queda en modo de solo lectura. |

---

### 4. TRANSICIONES PROHIBIDAS Y REGLAS DE BLOQUEO

1. **Salto de Asignación Prohibido:** Una incidencia `NUEVA` no puede pasar a `RESUELTA` ni a `CERRADA` directamente sin pasar por atención técnica y diagnóstico documentado.
2. **Cierre sin Validación Prohibido:** Una incidencia `EN_ATENCION` no puede pasar a `CERRADA` sin antes haber sido certificada como `RESUELTA` por un técnico.
3. **Inmutabilidad del Estado `CERRADA`:** Una vez que una incidencia alcanza el estado `CERRADA`, **ningún usuario** (incluyendo administradores) puede reactivarla ni alterar sus campos. Si se presenta un problema similar posteriormente, se debe crear una nueva incidencia referenciando el folio anterior.

---

### 5. INTEGRACIÓN VISUAL DEL STEPPER (UI MOCKUP)

En la vista de detalle de la incidencia ([`incidents/show.blade.php`](file:///c:/Users/Asus/Desktop/GIT/resources/views/incidents/show.blade.php)), el flujo se traduce directamente en el **Stepper Visual Horizontal** de 5 pasos observado en los diseños:

* **Paso completado:** Icono de verificación (`check`) con color verde esmeralda (`text-status-resolved`) y timestamp de finalización.
* **Paso activo:** Icono de herramientas/ingeniería (`engineering`) en color ámbar (`text-status-warning`), indicador pulsante y nombre del especialista de guardia.
* **Paso pendiente:** Número ordinal atenuado (`opacity-60 text-outline`) indicando que el proceso está a la espera de las etapas previas.

