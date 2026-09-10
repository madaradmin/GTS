# FACTORIES PARA PRUEBAS AUTOMATIZADAS
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Framework:** Laravel Model Factories (Faker PHP)  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. FACTORIES IMPLEMENTADOS Y ESTADOS DISPONIBLES

| Factory | Modelo Asociado | Estados Específicos (*State Methods*) | Propósito en Pruebas |
|---|---|---|---|
| [`UserFactory`](file:///c:/Users/Asus/Desktop/GIT/database/factories/UserFactory.php) | `User` | `apoc()`, `gts()`, `admin()`, `inactive()` | Generación de usuarios con roles específicos y gafetes institucionales. |
| [`RoleFactory`](file:///c:/Users/Asus/Desktop/GIT/database/factories/RoleFactory.php) | `Role` | N/A | Generación de roles sintéticos para tests aislados. |
| [`CategoryFactory`](file:///c:/Users/Asus/Desktop/GIT/database/factories/CategoryFactory.php) | `Category` | N/A | Generación de subsistemas tecnológicos. |
| [`PriorityFactory`](file:///c:/Users/Asus/Desktop/GIT/database/factories/PriorityFactory.php) | `Priority` | N/A | Generación de niveles de severidad y SLAs. |
| [`StatusFactory`](file:///c:/Users/Asus/Desktop/GIT/database/factories/StatusFactory.php) | `Status` | N/A | Generación de estados FSM personalizados. |
| [`IncidentFactory`](file:///c:/Users/Asus/Desktop/GIT/database/factories/IncidentFactory.php) | `Incident` | `nueva()`, `asignada()`, `enAtencion()`, `resuelta()`, `cerrada()`, `critica()` | Generación de tickets con folios formateados (`INC-YYYY-XXXX`) y ubicaciones aeroportuarias reales. |
| [`IncidentHistoryFactory`](file:///c:/Users/Asus/Desktop/GIT/database/factories/IncidentHistoryFactory.php) | `IncidentHistory` | N/A | Generación de eventos de trazabilidad y auditoría. |
| [`IncidentAttachmentFactory`](file:///c:/Users/Asus/Desktop/GIT/database/factories/IncidentAttachmentFactory.php) | `IncidentAttachment` | N/A | Generación de adjuntos sintéticos (`.jpg`, `.pdf`, `.png`, `.txt`). |

---

### 2. EJEMPLOS DE USO EN FEATURE TESTS

```php
// 1. Crear un operador APOC y despachar una incidencia nueva
$apocUser = User::factory()->apoc()->create();
$incident = Incident::factory()->nueva()->create([
    'reported_by_user_id' => $apocUser->id,
]);

// 2. Crear una incidencia en atención con especialista GTS asignado
$gtsUser = User::factory()->gts()->create();
$incident = Incident::factory()->enAtencion($gtsUser)->create();

// 3. Crear una incidencia resuelta con solución técnica documentada
$resolvedIncident = Incident::factory()->resuelta('Reemplazo de inyector PoE')->create();
```

