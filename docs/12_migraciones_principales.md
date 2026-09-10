# CREACIÓN DE MIGRACIONES PRINCIPALES (POSTGRESQL)
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Base de Datos:** PostgreSQL 18.6 (`git_incidentes`)  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. MIGRACIONES CREADAS Y EJECUTADAS

| # | Archivo de Migración | Tabla Creada / Modificada | Propósito y Relaciones |
|---|---|---|---|
| **1** | `2026_09_10_000001_create_roles_table.php` | `roles` | Catálogo de perfiles institucionales (`apoc`, `gts`, `admin`). |
| **2** | `2026_09_10_000002_add_role_and_badge_to_users_table.php` | `users` (alter) | Agrega `role_id` (FK a `roles`), `badge_number` e `is_active`. |
| **3** | `2026_09_10_000003_create_categories_table.php` | `categories` | Subsistemas tecnológicos (CCTV, Acceso, FIDS, TETRA, Redes). |
| **4** | `2026_09_10_000004_create_priorities_table.php` | `priorities` | Criticidad y SLAs referenciales (Baja, Media, Alta, Crítica). |
| **5** | `2026_09_10_000005_create_statuses_table.php` | `statuses` | Estados del ciclo de vida FSM (Nueva, Asignada, Atención, etc.). |
| **6** | `2026_09_10_000006_create_folio_sequences_table.php` | `folio_sequences` | Control atómico de correlativos anuales de folio (`INC-YYYY-XXXX`). |
| **7** | `2026_09_10_000007_create_incidents_table.php` | `incidents` | Expediente transaccional central de la falla con índices compuestos. |
| **8** | `2026_09_10_000008_create_incident_histories_table.php` | `incident_histories` | Bitácora inmutable de eventos de auditoría (Append-Only). |
| **9** | `2026_09_10_000009_create_incident_attachments_table.php` | `incident_attachments` | Metadatos de evidencias diagnósticas y adjuntos seguros. |

---

### 2. RESULTADO DE EJECUCIÓN

Todas las migraciones se ejecutaron satisfactoriamente mediante `php artisan migrate`, creando 17 tablas relacionales en PostgreSQL con integridad referencial estricta (`ON DELETE RESTRICT` y `ON DELETE CASCADE` en historiales/adjuntos).

