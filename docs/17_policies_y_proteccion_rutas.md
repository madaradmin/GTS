# POLICIES Y PROTECCIÓN DE REGLAS DE NEGOCIO (RBAC)
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Mecanismo:** Laravel Policies (`IncidentPolicy`)  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. POLÍTICAS DE AUTORIZACIÓN IMPLEMENTADAS

Se creó la clase [`IncidentPolicy`](file:///c:/Users/Asus/Desktop/GIT/app/Policies/IncidentPolicy.php) que evalúa en el servidor tanto la identidad del usuario como el estado actual de la máquina de estados finitos (FSM):

| Habilidad (*Ability*) | Roles Autorizados | Precondición de Estado FSM | Propósito Operativo |
|---|---|---|---|
| `viewAny` | APOC, GTS, ADMIN | Usuario activo | Consulta del catálogo y cola de tickets. |
| `view` | APOC, GTS, ADMIN | Usuario activo | Consulta del expediente detallado. |
| `create` | **APOC**, ADMIN | Usuario activo | APOC es el canal autorizado para levantar fallas. |
| `assign` | **GTS**, ADMIN | `NUEVA`, `ASIGNADA`, `EN_ATENCION` | Asignación o reasignación a especialistas de guardia. |
| `startAttention` | **GTS**, ADMIN | `NUEVA`, `ASIGNADA` | Comienzo de trabajos de campo/consola. |
| `addNote` | **GTS**, ADMIN | `EN_ATENCION` | Bitácora técnica y diagnóstica de campo. |
| `resolve` | **GTS**, ADMIN | `EN_ATENCION` | Certificación de la solución técnica. |
| `rejectResolution` | **APOC**, ADMIN | `RESUELTA` | Devolución a atención si la falla persiste. |
| `close` | **APOC**, ADMIN | `RESUELTA` | **Doble control:** APOC valida y otorga el visto bueno final. |
| `delete` | **NINGUNO** | Prohibido siempre | Trazabilidad y auditoría inmutable aeronáutica. |

---

### 2. RESULTADOS DE LA SUITE DE PRUEBAS

Se ejecutó la suite `tests/Feature/IncidentPolicyTest.php`:
* `test_apoc_can_create_incidents_but_gts_cannot`: **PASÓ**
* `test_gts_can_assign_when_incident_is_open`: **PASÓ**
* `test_gts_can_resolve_when_in_attention_but_apoc_cannot`: **PASÓ**
* `test_apoc_can_close_when_resolved_but_gts_cannot`: **PASÓ**
* `test_apoc_can_reject_resolution_when_resolved`: **PASÓ**
* `test_deletion_is_strictly_prohibited_for_all_users`: **PASÓ**

Total acumulado de pruebas de seguridad: 11 pruebas, 27 aserciones superadas con éxito.

