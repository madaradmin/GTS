# PASO 19: MÓDULO DE CREACIÓN Y DESPACHO DE INCIDENCIAS (APOC / GTS)

## 1. OBJETIVO DEL PASO
Diseñar e implementar el flujo transaccional de creación y despacho de incidencias técnicas en el sistema **GIT** (Gestión de Incidencias Tecnológicas - APOC / GTS). Esto incluye la asignación atómica de folios no colisionables (`INC-YYYY-XXXX`), el registro inmutable en la bitácora de auditoría (`incident_histories`), la captura de evidencias en almacenamiento local (`incident_attachments`), la validación mediante Form Request y la interfaz de usuario de alta densidad en modo oscuro adaptada a los estándares operacionales de AIFA.

---

## 2. POR QUÉ LO HACEMOS
En un entorno de operaciones críticas aeroportuarias (APOC), múltiples operadores pueden reportar fallas simultáneas durante un incidente mayor (por ejemplo, una fluctuación eléctrica en la Terminal que afecte torniquetes, CCTV y pantallas FIDS).
1. **Evitar colisiones de folio:** Si dos operadores envían reportes al mismo microsegundo, el uso de bloqueos pesimistas (`lockForUpdate()`) sobre la tabla de secuencias garantiza numeración estricta sin saltos ni duplicados.
2. **Atomicidad transaccional:** La creación del ticket, la asignación de folio, el almacenamiento de metadatos de archivos y el registro de auditoría deben ocurrir dentro de una única transacción ACID (`DB::transaction`). Si un archivo falla o la base de datos rechaza un parámetro, nada queda huérfano.
3. **Segregación de funciones (SoD):** Únicamente los operadores de APOC (o Administradores) pueden ingresar incidencias nuevas; los técnicos de GTS reciben el ticket en estado `NUEVA` para su posterior diagnóstico y atención.

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):** Migraciones de `incidents`, `statuses`, `categories`, `priorities`, `folio_sequences`, `incident_histories` e `incident_attachments`.
* **Fase 4 (Seguridad y Autorización):** `StoreIncidentRequest`, `IncidentPolicy` (habilidad `create`), `RoleMiddleware` y Gates.
* **Storage Local:** Directorio `storage/app/private/incidents` para almacenamiento confidencial de archivos.

---

## 4. ESTADO ACTUAL
* Entorno Laravel 13 con PostgreSQL 18.6 activo en puerto 5432.
* 49 pruebas unitarias y de integración superadas al 100%.
* Rutas web protegidas con middleware de autenticación y verificación de correo.

---

## 5. PLAN DE EJECUCIÓN
1. **Servicio Generador de Folios (`FolioGeneratorService`):**
   * Consulta bloqueante con `lockForUpdate()` sobre `folio_sequences` para el año en curso.
   * Formateo con padding de 4 dígitos: `INC-{YEAR}-{0001}`.
   * Método de previsualización no transaccional `getNextFolioPreview()` para la vista.
2. **Servicio de Auditoría (`AuditService`):**
   * Registro `append-only` de eventos en `incident_histories` con campos `incident_id`, `user_id`, `action`, `field_name`, `old_value`, `newValue`, `comment`.
3. **Servicio de Almacenamiento Seguro (`FileStorageService`):**
   * Generación de UUIDv4 para nombres físicos de archivo, sanitización de extensiones y almacenamiento en disco `local`.
4. **Capa de Servicio de Negocio (`IncidentService`):**
   * Orquestador de la transacción atómica: asignación de folio, estado `NUEVA`, persistencia en base de datos, guardado de adjuntos y registro de auditoría.
5. **Controlador Operativo (`IncidentController`):**
   * Métodos `create()`, `store()` y `show()`.
6. **Vistas Blade de Despacho:**
   * `create.blade.php`: Interfaz oscura Tailwind CSS con paleta táctica aeroportuaria, selectores estilizados y previsualización de folio.
   * `show.blade.php`: Ficha técnica de detalle con estado, prioridad, descripción, adjuntos y auditoría.
7. **Suite de Pruebas Automatizadas:**
   * `tests/Feature/CreateIncidentTest.php` con 7 casos de prueba cubriendo autorización, validación, transacciones, archivos y auditoría.

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Services/FolioGeneratorService.php` (Nuevo)
* `app/Services/AuditService.php` (Nuevo)
* `app/Services/FileStorageService.php` (Nuevo)
* `app/Services/IncidentService.php` (Nuevo)
* `app/Http/Controllers/IncidentController.php` (Nuevo)
* `resources/views/incidents/create.blade.php` (Nuevo)
* `resources/views/incidents/show.blade.php` (Nuevo)
* `resources/views/layouts/app.blade.php` (Modificado)
* `routes/web.php` (Modificado)
* `tests/Feature/CreateIncidentTest.php` (Nuevo)
* `database/factories/UserFactory.php` (Modificado)

---

## 7. IMPLEMENTACIÓN TÉCNICA

### Secuencia de Bloqueo Pesimista en Folios
```php
public function generateNextFolio(?int $year = null): string
{
    $currentYear = $year ?? (int) now()->year;

    $sequence = FolioSequence::where('year', $currentYear)
        ->lockForUpdate()
        ->first();

    if (! $sequence) {
        $sequence = FolioSequence::create([
            'year' => $currentYear,
            'current_number' => 0,
        ]);
        $sequence = FolioSequence::where('id', $sequence->id)
            ->lockForUpdate()
            ->first();
    }

    $sequence->increment('current_number');
    $nextNumber = $sequence->fresh()->current_number;

    return sprintf('INC-%d-%04d', $currentYear, $nextNumber);
}
```

### Transacción Atómica Completa
```php
public function createIncident(array $data, User $reporter, array $attachments = []): Incident
{
    return DB::transaction(function () use ($data, $reporter, $attachments) {
        $folio = $this->folioGenerator->generateNextFolio();
        $initialStatus = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::create([
            'folio' => $folio,
            'title' => trim($data['title']),
            'description' => trim($data['description']),
            'location' => trim($data['location']),
            'category_id' => (int) $data['category_id'],
            'priority_id' => (int) $data['priority_id'],
            'status_id' => $initialStatus->id,
            'reported_by_user_id' => $reporter->id,
            'reported_at' => now(),
        ]);

        $this->auditService->logAction(
            incidentId: $incident->id,
            userId: $reporter->id,
            action: 'CREADA',
            fieldName: 'status_id',
            oldValue: null,
            newValue: $initialStatus->name,
            comment: "Incidencia despachada con Folio {$folio} desde canal APOC."
        );

        if (!empty($attachments)) {
            $this->fileStorage->storeAttachments($incident, $reporter, $attachments);
        }

        return $incident;
    });
}
```

---

## 8. EXPLICACIÓN DIDÁCTICA PARA ESTUDIANTE DE TELECOMUNICACIONES
Imagina una red de telefonía celular o un enlace satelital con múltiples estaciones terrestres intentando transmitir sobre el mismo canal de control:
1. **Acceso Múltiple y Colisiones (CSMA/CD vs Bloqueo Pesimista):** En telecomunicaciones, si dos transceptores transmiten simultáneamente en Ethernet sin contención, ocurre una colisión de trama. En una base de datos relacional, si dos transacciones leen que el último número de incidente fue el 104 y ambas intentan escribir el 105, una de las dos colisiona o se corrompe la secuencia. El método `lockForUpdate()` funciona como una reserva de ranura temporal rígida (TDMA): la primera transacción que pide el recurso congela el registro de la secuencia a nivel de fila en el kernel de PostgreSQL (`SELECT FOR UPDATE`), bloqueando a los demás transmisores en cola hasta que finaliza el `COMMIT`.
2. **Encapsulamiento y Checksums (File Storage seguro):** Al igual que un paquete TCP/IP no confía ciegamente en la cabecera provista por el transmisor y calcula su propio checksum, el servicio `FileStorageService` nunca almacena un archivo con el nombre proporcionado por el usuario cliente para evitar ataques de inyección de rutas (`../../cmd.exe`). Se genera un UUIDv4 (un identificador de 128 bits pseudoaleatorio con probabilidad de colisión prácticamente nula) y se valida la firma de bytes MIME en el servidor antes de depositarlo en el disco protegido.

---

## 9. PRUEBAS Y VALIDACIÓN
Se diseñó la suite automatizada `tests/Feature/CreateIncidentTest.php` que verifica:
* Acceso no autenticado redirigido a `/login`: **PASÓ**
* Bloqueo 403 para usuarios GTS en pantalla de creación: **PASÓ**
* Acceso exitoso (200 OK) y previsualización de folio para usuario APOC: **PASÓ**
* Persistencia atómica de la incidencia con estado inicial `NUEVA`: **PASÓ**
* Registro inmutable de bitácora `CREADA` en `incident_histories`: **PASÓ**
* Carga de archivos físicos en almacenamiento y persistencia de metadatos: **PASÓ**
* Bloqueo 403 a usuarios GTS que intenten enviar peticiones POST: **PASÓ**
* Validación de errores en campos faltantes o inválidos: **PASÓ**

Resultado de ejecución:
```sh
php artisan test --filter=CreateIncidentTest
Tests: 7 passed (34 assertions)
Duration: 1.35s
```

Total del sistema:
```sh
php artisan test
Tests: 49 passed (139 assertions)
Duration: 2.68s
```

---

## 10. RESULTADO ESPERADO
* Pantalla `/incidencias/nueva` completamente operativa y renderizada con el tema oscuro institucional.
* Inyección de categorías y prioridades dinámicas desde PostgreSQL.
* Generación secuencial de folios `INC-2026-0001` sin saltos.
* Trazabilidad completa en auditoría desde el primer microsegundo.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES
1. **Error: Call to undefined method `IncidentController::authorize()`:**
   * *Causa:* En Laravel 11/13, los controladores ya no incluyen por defecto el trait `AuthorizesRequests`.
   * *Solución:* Emplear el facade `Gate::authorize('create', Incident::class)` directamente, manteniendo compatibilidad nativa con las Policies registradas.
2. **Error de namespace en tests por codificación UTF-8 con BOM:**
   * *Causa:* En PowerShell en Windows, `Set-Content -Encoding UTF8` introduce la firma BOM (`EF BB BF`), provocando que PHP interprete la cabecera como salida enviada al navegador antes de la declaración `namespace`.
   * *Solución:* Utilizar `[System.IO.File]::WriteAllText` configurado con `System.Text.UTF8Encoding($false)` (UTF-8 sin BOM).
3. **Pérdida de datos de prueba tras `RefreshDatabase`:**
   * *Causa:* Los tests de integración con `RefreshDatabase` ejecutan rollback o migración en blanco sobre la base de datos de pruebas.
   * *Solución:* Ejecutar `php artisan db:seed` inmediatamente después del ciclo de pruebas para restaurar usuarios y catálogos en el entorno local.

---

## 12. VERIFICACIÓN FINAL
* Pruebas automatizadas: 49 de 49 pasadas (100% exitosas).
* Base de datos resembrada con catálogos y usuarios de prueba.
* Archivos listos para commit en Git.