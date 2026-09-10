# MODELOS Y RELACIONES ELOQUENT
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**ORM:** Laravel Eloquent ORM  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. MAPA DE MODELOS Y RELACIONES IMPLEMENTADAS

| Modelo | Tabla Mapeada | Relaciones Declaradas | Métodos de Utilidad / Reglas Clave |
|---|---|---|---|
| [`Role`](file:///c:/Users/Asus/Desktop/GIT/app/Models/Role.php) | `roles` | `hasMany(User::class)` | Catálogo de perfiles institucionales. |
| [`User`](file:///c:/Users/Asus/Desktop/GIT/app/Models/User.php) | `users` | `belongsTo(Role::class)`, `hasMany(Incident::class, 'reported_by')`, `hasMany(Incident::class, 'assigned_to')`, `hasMany(IncidentHistory::class)` | Helper `hasRole('apoc'|'gts')` para consultas rápidas de autorización. |
| [`Category`](file:///c:/Users/Asus/Desktop/GIT/app/Models/Category.php) | `categories` | `hasMany(Incident::class)` | Subsistemas tecnológicos con casteo a boolean (`is_active`). |
| [`Priority`](file:///c:/Users/Asus/Desktop/GIT/app/Models/Priority.php) | `priorities` | `hasMany(Incident::class)` | SLAs y ponderación numérica (`level`). |
| [`Status`](file:///c:/Users/Asus/Desktop/GIT/app/Models/Status.php) | `statuses` | `hasMany(Incident::class)` | Máquina de estados con orden para Stepper (`step_order`). |
| [`FolioSequence`](file:///c:/Users/Asus/Desktop/GIT/app/Models/FolioSequence.php) | `folio_sequences` | N/A (Entidad de control) | Bloqueo transaccional de correlativos anuales por `year`. |
| [`Incident`](file:///c:/Users/Asus/Desktop/GIT/app/Models/Incident.php) | `incidents` | `belongsTo(Category::class)`, `belongsTo(Priority::class)`, `belongsTo(Status::class)`, `belongsTo(User::class, 'reported_by')`, `belongsTo(User::class, 'assigned_to')`, `hasMany(IncidentHistory::class)`, `hasMany(IncidentAttachment::class)` | Scopes de consulta: `scopeActive`, `scopeSearch` (búsqueda insensible a mayúsculas con `ILIKE` en PostgreSQL). |
| [`IncidentHistory`](file:///c:/Users/Asus/Desktop/GIT/app/Models/IncidentHistory.php) | `incident_histories` | `belongsTo(Incident::class)`, `belongsTo(User::class)` | **Append-Only:** `const UPDATED_AT = null;` para impedir modificaciones en auditoría. |
| [`IncidentAttachment`](file:///c:/Users/Asus/Desktop/GIT/app/Models/IncidentAttachment.php) | `incident_attachments` | `belongsTo(Incident::class)`, `belongsTo(User::class, 'uploaded_by')` | Metadatos y control de archivos diagnósticos. |

---

### 2. CARACTERÍSTICAS TÉCNICAS DESTACADAS

1. **Búsqueda Optimizada para PostgreSQL (`Incident::scopeSearch`):**
   Utiliza el operador nativo `ILIKE` de PostgreSQL para búsquedas de texto completas e insensibles a mayúsculas sobre `folio`, `title` y `location`.
2. **Auditoría Inmutable (`IncidentHistory`):**
   Al desactivar `UPDATED_AT`, Eloquent nunca intentará ejecutar sentencias `UPDATE` sobre los registros de trazabilidad.
3. **Verificación de Rol Eficiente (`User::hasRole`):**
   Carga perezosa (*lazy loading*) con salvaguarda si la relación ya está en memoria para evitar consultas duplicadas a la base de datos.

