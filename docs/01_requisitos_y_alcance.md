# ESPECIFICACIÓN DE REQUISITOS Y ALCANCE DEFINITIVO (SRS)
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Entidad Operativa:** AIFA (Aeropuerto Internacional Felipe Ángeles)  
**Actores Principales:** APOC (Airport Operations Center) / GTS (Grupo de Seguridad Tecnológica)  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. PROPÓSITO Y CONTEXTO OPERATIVO
GIT es una solución web transaccional interna diseñada para centralizar, estructurar, despachar y auditar el ciclo de vida de fallas e incidentes tecnológicos que impactan la operación aeroportuaria.

En el entorno aeroportuario:
- **APOC:** Es el centro neurálgico que supervisa la continuidad operativa de terminales, pistas, filtros y plataformas. Requiere reportar averías de manera inmediata y monitorear el estado de atención.
- **GTS:** Es la unidad de ingeniería y soporte en campo encargada de atender, diagnosticar, mantener y certificar la corrección de los subsistemas tecnológicos (CCTV, Control de Acceso, FIDS, TETRA, Redes).

---

### 2. REQUERIMIENTOS FUNCIONALES (RF)

* **RF-01: Autenticación y Control de Sesión**
  * El sistema debe permitir el inicio de sesión seguro mediante credenciales institucionales (correo y contraseña con hash Argon2id/Bcrypt).
  * Debe mantener sesiones seguras y permitir el cierre explícito de sesión.
  * Debe restringir el acceso a cualquier vista operativa a usuarios no autenticados.

* **RF-02: Gestión de Roles y Privilegios (RBAC)**
  * Se deben implementar perfiles diferenciados:
    * **Operador APOC:** Creación de incidencias, consulta de incidencias reportadas por APOC, consulta de estado, cierre formal con visto bueno.
    * **Especialista / Coordinador GTS:** Visualización completa de incidencias tecnológicas, asignación/toma técnica, actualización de estado a atención o resolución, captura de bitácora técnica de campo.
    * **Administrador del Sistema:** Gestión de catálogos y usuarios.

* **RF-03: Módulo de Registro y Despacho de Incidencias**
  * Captura de título descriptivo, subsistema/categoría tecnológica, ubicación física precisa (Lado Aire / Lado Tierra / Edificio Terminal), prioridad sugerida y descripción detallada del problema.
  * Carga opcional de evidencias diagnósticas (imágenes, bitácoras TXT, documentos PDF).

* **RF-04: Generador Determinista de Folios Únicos**
  * Toda incidencia creada debe recibir de forma automática un folio inmutable con estructura: `INC-YYYY-XXXX` (donde `YYYY` es el año en curso y `XXXX` un correlativo anual continuo relleno con ceros a la izquierda).
  * La generación debe ser atómica para evitar duplicados o colisiones ante concurrencia.

* **RF-05: Ciclo de Vida y Máquina de Estados Finitos**
  * El sistema debe gestionar exactamente 5 estados operacionales:
    1. **NUEVA:** Incidencia despachada por APOC pendiente de asignación técnica.
    2. **ASIGNADA:** Asignada a un técnico o especialista de guardia GTS.
    3. **EN ATENCIÓN:** El especialista GTS se encuentra en sitio o en consola ejecutando diagnóstico o reparación.
    4. **RESUELTA:** El especialista GTS certifica la solución y registra la descripción técnica del arreglo.
    5. **CERRADA:** El operador APOC valida la solución y otorga el cierre administrativo.

* **RF-06: Clasificación y Priorización**
  * Categorización por subsistemas tecnológicos:
    * `CCTV`: Videovigilancia, cámaras domo PTZ, servidores VMS, almacenamiento de video.
    * `Control de Acceso`: Torniquetes, e-Gates, lectores biométricos, esclusas, cerraduras electromagnéticas.
    * `FIDS`: Pantallas de información de vuelos, servidores de cartelería digital, controladores multimedia.
    * `TETRA`: Radiocomunicación troncalizada, estaciones base, repetidores, consolas de despacho.
    * `Redes e Infraestructura IT`: Switches de borde perimetral, racks IDF/MDF, cableado estructurado, enlaces de fibra óptica.
  * Matriz de Prioridad: `Baja`, `Media`, `Alta`, `Crítica` (con impacto directo en nivel de atención visual).

* **RF-07: Bitácora de Acciones y Notas Técnicas**
  * Los especialistas GTS deben poder registrar avances, sustitución de componentes (e.g. switches, fuentes PoE) y notas de campo durante el estado `EN ATENCIÓN`.

* **RF-08: Trazabilidad y Registro de Auditoría Inmutable**
  * Cada acción sobre una incidencia genera un evento en bitácora registrando: fecha/hora exacta, usuario responsable, tipo de acción, valores anteriores y nuevos (en cambios de estado/prioridad/asignación).

* **RF-09: Dashboard Operativo de Guardia (GTS / APOC)**
  * Panel visual con telemetría de turno: contadores de incidencias abiertas, en atención, resueltas hoy, críticas activas.
  * Visualización dividida 65/35:
    * Columna principal (65%): Tabla de incidencias recientes y de alta prioridad con acceso a detalle.
    * Columna lateral (35%): Alertas de atención inmediata y distribución de fallas por subsistema.

* **RF-10: Búsqueda, Filtrado y Paginación**
  * Búsqueda en tiempo real por folio (`INC-...`), asunto o ubicación.
  * Filtros combinados por estado, prioridad y subsistema.

---

### 3. REQUERIMIENTOS NO FUNCIONALES (RNF)

* **RNF-01: Seguridad en Capa de Aplicación**
  * Protección total contra OWASP Top 10: Prevención de SQL Injection (mediante Eloquent ORM), XSS (escapado nativo Blade `{{ }}`), CSRF (tokens en cada formulario).
  * Autorización estricta por servidor (Laravel Policies); nunca delegar la seguridad a la visibilidad en cliente.

* **RNF-02: Integridad Referencial y ACID**
  * Base de datos PostgreSQL con constraints relacionales estrictos (`ON DELETE RESTRICT`) para impedir orfandad de registros de auditoría o incidencias.

* **RNF-03: Rendimiento y Tiempos de Respuesta**
  * Respuestas de consulta de listado y dashboard en menos de 250 ms en red local mediante índices compuestos en base de datos (`folio`, `status_id`, `priority_id`, `created_at`).

* **RNF-04: Diseño de Interfaz Institucional (UI/UX)**
  * Interfaz de alta densidad, modo oscuro tecnológico adaptado a centros de control (C4/APOC), paleta con contraste accesible según WCAG AA.
  * Desktop-first, optimizada para estaciones de trabajo de monitoreo con pantallas múltiples.

* **RNF-05: Seguridad y Control de Almacenamiento de Archivos**
  * Archivos subidos guardados con nombres hash criptográficos y fuera del directorio accesible directamente por la web (`storage/app/private/attachments`).
  * Límite de tamaño de 10 MB por archivo. Tipos permitidos estrictos: `jpg`, `jpeg`, `png`, `pdf`, `txt`.

* **RNF-06: Modularidad y Mantenibilidad**
  * Arquitectura desacoplada MVC + Service Layer. Cero lógica de negocio compleja en controladores.

* **RNF-07: Trazabilidad y No Repudio**
  * Los registros de auditoría son de solo adición (*append-only*); no existe opción de actualización ni eliminación para ningún rol (ni siquiera administrador).

* **RNF-08: Compatibilidad de Navegadores**
  * Compatible al 100% con las últimas versiones de navegadores modernos estándar en estaciones de trabajo: Google Chrome, Microsoft Edge, Mozilla Firefox.

---

### 4. REGLAS DE NEGOCIO (RN)

* **RN-01 (Inmutabilidad del Folio):** Una vez asignado el folio a una incidencia, este no podrá ser modificado ni reasignado bajo ninguna circunstancia.
* **RN-02 (Transición Válida de Estados):**
  * Solo se puede pasar a `ASIGNADA` desde `NUEVA`.
  * Solo se puede pasar a `EN ATENCIÓN` si la incidencia tiene un responsable asignado.
  * Solo un usuario con rol GTS puede marcar una incidencia como `RESUELTA`.
  * La transición a `RESUELTA` requiere obligatoriamente una descripción técnica de la solución aplicada.
  * La transición a `CERRADA` requiere obligatoriamente que la incidencia se encuentre previamente en estado `RESUELTA`.
* **RN-03 (Auto-Asignación y Despacho GTS):** Un especialista GTS puede autoasignarse una incidencia en estado `NUEVA`, lo que disparará automáticamente la transición a `ASIGNADA` o `EN ATENCIÓN`.
* **RN-04 (Auditoría Mandatoria):** Ningún campo de estado, asignación o prioridad puede modificarse sin que se genere en la misma transacción de base de datos el registro correspondiente en la tabla de auditoría.
* **RN-05 (Integridad de Evidencias):** Los archivos adjuntos pertenecen al expediente de la incidencia. No pueden ser eliminados una vez que la incidencia ha pasado a estado `RESUELTA`.

---

### 5. FRONTERAS DEL ALCANCE (MATRIZ IN / OUT)

| En el Alcance (MVP - Fases 1 a 9) | Fuera del Alcance (Versiones Posteriores) |
|---|---|
| Autenticación local con roles APOC y GTS | Integración con Active Directory / LDAP institucional |
| Generación de folios deterministas `INC-YYYY-XXXX` | Notificaciones por SMS / WhatsApp |
| Flujo estricto de 5 estados con validación en servidor | WebSockets para actualización push sin refrescar |
| Registro de notas técnicas de campo | Geolocalización GIS interactiva en mapa aeroportuario |
| Carga de evidencias diagnósticas (PNG, PDF, etc.) | Firma electrónica avanzada con certificado digital |
| Dashboard operativo con métricas de turno y cola 65/35 | Exportación de informes mensuales en PDF/Excel |
| Bitácora cronológica inmutable de auditoría | Interconexión directa con Genetec Security Center |

