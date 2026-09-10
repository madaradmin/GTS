# ARQUITECTURA TÉCNICA Y ESTRUCTURA GENERAL DEL PROYECTO
## Proyecto: GIT — Gestión de Incidencias Tecnológicas (APOC / GTS)
**Patrón Arquitectónico:** MVC Extendido + Service Layer + Form Requests + Policies  
**Versión:** 1.0.0  
**Fecha:** Septiembre 2026  

---

### 1. VISIÓN GENERAL Y FILOSOFÍA ARQUITECTÓNICA

El sistema **GIT** adopta una arquitectura multicapa desacoplada y orientada a servicios dentro del marco de trabajo **Laravel**. 

El objetivo primordial es **eliminar los Controladores Monolíticos ("Fat Controllers")** y los **Modelos Saturados de Lógica ("Fat Models")**, estableciendo fronteras claras entre la recepción de la petición HTTP, la validación de los datos, la autorización de acceso, las reglas de negocio de despacho aeroportuario y la persistencia relacional en **PostgreSQL**.

```text
                                 PETICIÓN DEL CLIENTE
                                          │
                                          ▼
                             ┌─────────────────────────┐
                             │    HTTP Router / Web    │
                             └────────────┬────────────┘
                                          │
                                          ▼
                             ┌─────────────────────────┐
                             │       Middleware        │  (Auth, RBAC, CSRF)
                             └────────────┬────────────┘
                                          │
                                          ▼
                             ┌─────────────────────────┐
                             │       Controller        │  (Thin: Orquestador HTTP)
                             └────────────┬────────────┘
                                          │
             ┌────────────────────────────┼────────────────────────────┐
             ▼                            ▼                            ▼
  ┌─────────────────────┐      ┌─────────────────────┐      ┌─────────────────────┐
  │    Form Request     │      │       Policy        │      │       Service       │
  │ (Reglas Validación) │      │ (Reglas Permisos)   │      │ (Lógica de Negocio) │
  └─────────────────────┘      └─────────────────────┘      └──────────┬──────────┘
                                                                       │
                                          ┌────────────────────────────┘
                                          ▼
                             ┌─────────────────────────┐
                             │     Eloquent Model      │  (Entidad / Relaciones)
                             └────────────┬────────────┘
                                          │
                                          ▼
                             ┌─────────────────────────┐
                             │  PostgreSQL (ACID DB)   │
                             └─────────────────────────┘
```

---

### 2. RESPONSABILIDAD ESTRICTA POR CAPAS

Para asegurar el principio de **Responsabilidad Única (SRP - Single Responsibility Principle)**, cada capa tiene un alcance delimitado:

#### A. Capa de Enrutamiento y Middleware (`routes/web.php`, `app/Http/Middleware/`)
* **Qué hace:** Captura la URL, el verbo HTTP (`GET`, `POST`, `PATCH`, `DELETE`) y aplica filtros transversales antes de que la petición toque la aplicación.
* **Middlewares principales:**
  * `auth`: Garantiza que el usuario cuente con sesión activa.
  * `role:apoc|gts`: Verifica que el usuario autenticado pertenezca al rol requerido para el recurso.
* **Qué NO hace:** No consulta modelos ni ejecuta lógica de negocio.

#### B. Capa de Validación (`app/Http/Requests/`)
* **Qué hace:** Intercepta la petición y valida de forma estricta los datos entrantes (tipos, longitud, existencia en base de datos, tipos MIME de archivos).
* **Manejo de fallos:** Si falla la validación, Laravel redirige automáticamente con errores formateados y datos viejos (`old()`), impidiendo que el controlador reciba datos corruptos.
* **Qué NO hace:** No persiste en base de datos ni modifica registros.

#### C. Capa de Controladores (`app/Http/Controllers/`)
* **Qué hace:** Recibe la petición validada, ejecuta la verificación de políticas (`$this->authorize()`), delega la operación al *Service* correspondiente mediante Inyección de Dependencias y retorna la vista Blade o una redirección con mensaje flash.
* **Regla de oro:** Un método de controlador no debe exceder preferentemente 15-20 líneas de código.
* **Qué NO hace:** No contiene consultas complejas de Eloquent (`where`, `join`), no genera folios y no realiza cálculos de estado.

#### D. Capa de Autorización (`app/Policies/`)
* **Qué hace:** Responde a la pregunta: *¿Tiene este usuario en particular el permiso para ejecutar esta acción sobre esta incidencia específica?*
* **Ejemplo:** Un especialista GTS solo puede resolver incidencias que estén en estado `EN ATENCIÓN`. Un operador APOC solo puede cerrar incidencias que estén en estado `RESUELTA`.
* **Qué NO hace:** No valida formatos de entrada ni guarda datos.

#### E. Capa de Servicios (`app/Services/`)
* **Qué hace:** Es el núcleo del dominio. Contiene los casos de uso del sistema aeroportuario:
  * `IncidentService`: Orquesta la creación, asignación, notas técnicas y resolución.
  * `FolioGeneratorService`: Genera folios `INC-YYYY-XXXX` con aislamiento y bloqueo concurrente en base de datos.
  * `AuditService`: Registra eventos inmutables de auditoría en cada cambio relevante.
  * `FileStorageService`: Sanitiza nombres, valida hash y almacena evidencias en disco seguro.
* **Manejo de transacciones:** Utiliza `DB::transaction(function() { ... })` para asegurar que operaciones compuestas (e.g. actualizar estado + insertar auditoría) sean atómicas (todo o nada).

#### F. Capa de Datos y Modelos (`app/Models/`)
* **Qué hace:** Representa las entidades del negocio (`Incident`, `User`, `Category`, `Priority`, `Status`, `IncidentHistory`, `IncidentAttachment`).
* **Define:** Relaciones (`belongsTo`, `hasMany`), casteos de tipos, scopes de consulta (`scopeActive`, `scopePriority`) y atributos protegidos (`$fillable`).
* **Qué NO hace:** No procesa peticiones HTTP ni sube archivos físicos.

#### G. Capa de Presentación (`resources/views/`)
* **Qué hace:** Renderiza la interfaz de usuario utilizando plantillas Blade modulares.
* **Estilo visual:** Tailwind CSS compilado con Vite, paleta institucional oscura SITIA/GIT (`#0B0F17`, `#06B6D4`, `#10B981`, `#EF4444`).
* **Componentes Blade:** Stepper de ciclo de vida, badges de prioridad, modales de confirmación y tablas de alta densidad.

---

### 3. CONVENCIONES DE CÓDIGO Y ESTÁNDARES

1. **Estándar PHP:** Cumplimiento estricto de **PSR-12** (estándar de codificación extendido de PHP) y tipos estrictos (`declare(strict_types=1);`).
2. **Nomenclatura:**
   * **Clases:** `PascalCase` (e.g. `IncidentService`, `StoreIncidentRequest`).
   * **Métodos y variables:** `camelCase` (e.g. `resolveIncident()`, `$incidentId`).
   * **Tablas de Base de Datos:** `snake_case` plural (e.g. `incidents`, `incident_histories`).
   * **Columnas y Llaves Foráneas:** `snake_case` singular con sufijo `_id` (e.g. `category_id`, `assigned_to_user_id`).
   * **Rutas HTTP:** Kebab-case plural para recursos RESTful (e.g. `/incidencias`, `/incidencias/{folio}/asignar`).
3. **Inyección de Dependencias (DI):** Los servicios se inyectan en los constructores o métodos de los controladores mediante el contenedor de servicios de Laravel, garantizando desacoplamiento e intercambiabilidad en pruebas unitarias.

---

### 4. GESTIÓN DE ERRORES Y EXCEPCIONES DE DOMINIO

El sistema no debe exponer pantallas de error genéricas ("Whoops!") al usuario final en producción:
* **Excepciones de Dominio Personalizadas (`app/Exceptions/`):**
  * `InvalidStatusTransitionException`: Lanzada cuando se intenta una transición prohibida (e.g. de `NUEVA` a `CERRADA`).
  * `IncidentAlreadyResolvedException`: Lanzada si se intenta modificar una incidencia resuelta.
* **Control Centralizado:** Captura de excepciones en la capa de servicios y controladores con mensajes flash informativos y registro silencioso en el log de Laravel (`storage/logs/laravel.log`).

