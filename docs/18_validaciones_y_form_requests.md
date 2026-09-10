# VALIDACIONES Y FORM REQUESTS
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Capa:** HTTP Form Requests  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. FORM REQUESTS IMPLEMENTADOS

| Clase Form Request | Propósito Operativo | Reglas Clave y Restricciones | Autorización (`authorize`) |
|---|---|---|---|
| [`StoreIncidentRequest`](file:///c:/Users/Asus/Desktop/GIT/app/Http/Requests/StoreIncidentRequest.php) | Despacho de incidencia | `title` (5-255 car), `description` (10-3000 car), `location` (3-255 car), `category_id` (activo en DB), `priority_id` (activo en DB), `attachments` (máx 5 archivos, máx 10 MB, mimes: jpg,jpeg,png,pdf,txt). | `$user->can('create', Incident::class)` |
| [`AssignIncidentRequest`](file:///c:/Users/Asus/Desktop/GIT/app/Http/Requests/AssignIncidentRequest.php) | Asignación / Reasignación | `assigned_to_user_id` (requerido, debe existir en `users` con rol `gts` y estar `is_active = true`), `comment` (máx 500 car). | `$user->can('assign', $incident)` |
| [`ResolveIncidentRequest`](file:///c:/Users/Asus/Desktop/GIT/app/Http/Requests/ResolveIncidentRequest.php) | Certificación de solución | `solution_description` (obligatorio, mínimo 15 caracteres y máximo 3000 explicando el arreglo técnico). | `$user->can('resolve', $incident)` |
| [`AddNoteRequest`](file:///c:/Users/Asus/Desktop/GIT/app/Http/Requests/AddNoteRequest.php) | Notas de campo | `note` (obligatorio, 5 a 1000 caracteres con avances o mediciones de campo). | `$user->can('addNote', $incident)` |
| [`RejectResolutionRequest`](file:///c:/Users/Asus/Desktop/GIT/app/Http/Requests/RejectResolutionRequest.php) | Rechazo de solución | `rejection_reason` (obligatorio, mínimo 10 caracteres justificando la persistencia de la falla). | `$user->can('rejectResolution', $incident)` |

---

### 2. RESULTADOS DE LA SUITE DE PRUEBAS

Se ejecutó la suite `tests/Feature/IncidentValidationTest.php`:
* `test_store_incident_request_validates_required_fields`: **PASÓ**
* `test_store_incident_request_passes_with_valid_data`: **PASÓ**
* `test_assign_incident_request_requires_active_gts_user`: **PASÓ**
* `test_resolve_incident_request_requires_minimum_length_solution`: **PASÓ**
* `test_add_note_request_validates_content`: **PASÓ**
* `test_reject_resolution_request_requires_explanation`: **PASÓ**

Total acumulado Fase 4 (Seguridad y Autorización): 17 pruebas, 44 aserciones superadas al 100%.

