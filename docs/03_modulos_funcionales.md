# MÓDULOS FUNCIONALES Y LÍMITES DE CADA MÓDULO
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. MAPA GENERAL DE MÓDULOS

Para garantizar **alta cohesión interna** y **bajo acoplamiento externo**, el sistema **GIT** se divide en 7 módulos funcionales independientes pero coordinados:

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        MÓDULOS DE SOPORTE                              │
├───────────────────────────────────┬────────────────────────────────────┤
│ 1. Autenticación y Cuentas (Auth) │ 2. Catálogos y Datos Maestros      │
│    - Sesiones, credenciales       │    - Subsistemas, Prioridades      │
│    - Roles APOC / GTS             │    - Estados del Ciclo de Vida     │
└─────────────────┬─────────────────┴──────────────────┬─────────────────┘
                  │                                    │
                  ▼                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        MÓDULOS DE OPERACIÓN                            │
├───────────────────────────────────┬────────────────────────────────────┤
│ 3. Registro y Despacho            │ 4. Operación y Ciclo de Vida       │
│    - Formulario de alta           │    - Asignación técnica GTS        │
│    - Generador de Folios          │    - Notas de campo                │
│    - Expediente de incidencia     │    - Resolución técnica y Cierre   │
└─────────────────┬─────────────────┴──────────────────┬─────────────────┘
                  │                                    │
                  ▼                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        MÓDULOS DE CONTROL                              │
├───────────────────────────────────┬────────────────────────────────────┤
│ 5. Trazabilidad y Auditoría       │ 6. Evidencias y Archivos Adjuntos  │
│    - Eventos cronológicos         │    - Almacenamiento privado seguro │
│    - Bitácora inmutable (append)  │    - Validación MIME y hash        │
├───────────────────────────────────┴────────────────────────────────────┤
│ 7. Dashboard Operativo y Telemetría                                    │
│    - Resumen de turno APOC/GTS                                         │
│    - Distribución por subsistema y cola de atención inmediata          │
└────────────────────────────────────────────────────────────────────────┘
```

---

### 2. ESPECIFICACIÓN Y FRONTERAS DE CADA MÓDULO

---

#### MÓDULO 1: Autenticación y Control de Acceso (Auth & RBAC)
* **Propósito:** Gestionar la identidad del operador y verificar sus credenciales institucionales antes de permitir cualquier interacción con la plataforma.
* **Entradas:** Correo institucional, contraseña, solicitud de cierre de sesión.
* **Salidas:** Sesión HTTP autenticada con token CSRF, objeto `User` inyectado en la petición con su rol asociado.
* **Límites estrictos:**
  * **Lo que hace:** Valida hash de contraseñas (Bcrypt/Argon2id), administra sesiones y suministra el rol del usuario (`apoc`, `gts`, `admin`).
  * **Lo que NO hace:** No decide si un usuario puede o no editar una incidencia específica (eso es responsabilidad del Módulo 4 mediante *Policies*).

---

#### MÓDULO 2: Catálogos y Datos Maestros
* **Propósito:** Proveer las listas de valores controladas y normalizadas para clasificar y gestionar los incidentes aeroportuarios sin redundancia ni inconsistencias ortográficas.
* **Entidades:**
  * `Category`: Subsistemas tecnológicos del aeropuerto (`CCTV`, `Control de Acceso`, `FIDS`, `TETRA`, `Redes e Infraestructura IT`).
  * `Priority`: Niveles de severidad (`Baja`, `Media`, `Alta`, `Crítica`).
  * `Status`: Estados operacionales del flujo (`Nueva`, `Asignada`, `En Atención`, `Resuelta`, `Cerrada`).
* **Límites estrictos:**
  * **Lo que hace:** Provee datos tipificados, colores asociados (hexadecimales para Tailwind) y tiempos de SLA referenciales.
  * **Lo que NO hace:** No almacena lógica de transición de estados ni modifica el estado de un ticket.

---

#### MÓDULO 3: Registro y Despacho de Incidencias
* **Propósito:** Capturar la anomalía reportada por el APOC, validar su consistencia técnica, generar el folio único y abrir el expediente formal.
* **Componentes clave:**
  * `StoreIncidentRequest`: Validación en capa de transporte.
  * `FolioGeneratorService`: Generación atómica del folio con bloqueo concurrente en PostgreSQL (`INC-YYYY-XXXX`).
  * `IncidentService@createIncident`: Orquestador de persistencia.
* **Entradas:** Título, descripción del síntoma, categoría seleccionada, ubicación física precisa, prioridad sugerida, evidencias opcionales.
* **Salidas:** Registro de incidencia persistido con estado inicial `NUEVA` y folio determinista.
* **Límites estrictos:**
  * **Lo que hace:** Garantiza la completitud y la unicidad del folio al momento del despacho.
  * **Lo que NO hace:** No permite definir la solución técnica al momento de la creación (la solución solo se ingresa en el Módulo 4).

---

#### MÓDULO 4: Operación y Ciclo de Vida
* **Propósito:** Gestionar las transiciones del ticket a través de las etapas operativas de atención técnica en campo y validación de cierre.
* **Casos de uso gobernados:**
  1. **Asignación / Reasignación:** Vincular la incidencia a un especialista GTS específico.
  2. **Inicio de Atención:** Pasar a `EN ATENCIÓN` cuando el técnico arriba a la ubicación o toma control remoto.
  3. **Notas Técnicas de Campo:** Registro de diagnósticos parciales, pruebas de continuidad o reemplazo de componentes.
  4. **Resolución:** Registro obligatorio de la causa raíz y solución aplicada por parte de GTS (`RESUELTA`).
  5. **Cierre:** Conformidad y visto bueno final otorgado por el APOC (`CERRADA`).
* **Límites estrictos:**
  * **Lo que hace:** Aplica la máquina de estados finitos y las *Policies* de autorización.
  * **Lo que NO hace:** No borra datos anteriores; cada cambio de estado dispara automáticamente un evento al Módulo 5.

---

#### MÓDULO 5: Trazabilidad y Auditoría
* **Propósito:** Garantizar el **no repudio** y la transparencia de las acciones mediante una bitácora cronológica inmutable.
* **Entidad:** `IncidentHistory` (tabla `incident_histories`).
* **Datos registrados por cada evento:**
  * `incident_id`: Referencia a la incidencia.
  * `user_id`: Quién ejecutó la acción.
  * `action`: Verbo descriptivo (`CREACIÓN`, `ASIGNACIÓN`, `CAMBIO_ESTADO`, `NOTA_TÉCNICA`, `RESOLUCIÓN`, `CIERRE`).
  * `field_name`: Campo afectado (e.g. `status_id`, `assigned_to_user_id`).
  * `old_value`: Valor previo.
  * `new_value`: Valor posterior.
  * `comment`: Justificación o texto técnico asociado.
  * `created_at`: Timestamp inmutable de base de datos.
* **Límites estrictos:**
  * **Lo que hace:** Almacenamiento *Append-Only* (solo inserciones).
  * **Lo que NO hace:** No implementa métodos `update` ni `delete`; la auditoría histórica no se modifica bajo ninguna circunstancia.

---

#### MÓDULO 6: Evidencias y Archivos Adjuntos
* **Propósito:** Almacenar capturas de error, bitácoras o diagramas que respalden el diagnóstico del incidente de forma segura.
* **Componentes:** `FileStorageService`, tabla `incident_attachments`.
* **Políticas de Seguridad:**
  * Almacenamiento en disco no público: `storage/app/private/incidents/{incident_id}/`.
  * Nombre físico reemplazado por UUID v4 criptográfico para evitar sobreescrituras e inyecciones de ruta (*Path Traversal*).
  * Validación por contenido binario real (MIME type).
* **Límites estrictos:**
  * **Lo que hace:** Recibe, valida, encripta el nombre, guarda y sirve el archivo mediante un controlador que verifica si el usuario tiene permiso para ver la incidencia.
  * **Lo que NO hace:** No permite la ejecución de archivos en el servidor (los archivos no residen en `public/`).

---

#### MÓDULO 7: Dashboard Operativo y Telemetría
* **Propósito:** Proporcionar visibilidad situacional instantánea al personal de guardia (APOC y GTS) para la toma de decisiones.
* **Componentes:** `DashboardController`, consultas optimizadas con índices relacionales.
* **Telemetría visualizada:**
  * Tarjetas de estado rápido: Abiertas, En Atención, Resueltas Hoy, Críticas Activas.
  * Tabla 65%: Cola de incidencias prioritarias y recientes con acceso directo a detalle.
  * Columna 35%: Incidencias que requieren acción inmediata y desglose de incidentes por subsistema (CCTV, Acceso, FIDS, Redes).
* **Límites estrictos:**
  * **Lo que hace:** Agrega y filtra métricas operativas del turno activo en tiempo real.
  * **Lo que NO hace:** No realiza procesamiento analítico masivo de big data ni genera proyecciones estadísticas complejas (reservadas para post-MVP).

---

### 3. MATRIZ DE INTERACCIÓN Y CONTRATOS ENTRE MÓDULOS

| Módulo Emisor | Módulo Receptor | Datos / Contrato Compartido | Propósito |
|---|---|---|---|
| **M1 (Auth)** | **M3 (Registro)** | Objeto `User` autenticado (`id`, `role`) | Atribuir la autoría de la incidencia creada. |
| **M2 (Catálogos)** | **M3 (Registro)** | IDs de Categoría y Prioridad | Clasificar la falla con valores válidos. |
| **M3 (Registro)** | **M5 (Auditoría)** | Evento `IncidentCreated` | Registrar el nacimiento del ticket con su folio. |
| **M4 (Operación)** | **M5 (Auditoría)** | Datos del cambio (`old_status`, `new_status`, notas) | Dejar evidencia inmutable de cada paso de atención. |
| **M3 / M4** | **M6 (Evidencias)** | Archivos binarios subidos + `incident_id` | Asociar capturas de error al expediente. |
| **M3 / M4 / M2** | **M7 (Dashboard)** | Consultas indexadas por `status_id` y `created_at` | Alimentar los indicadores y la tabla de guardia. |

