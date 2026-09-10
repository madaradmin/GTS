# PASO 27: IMPLEMENTACIÓN DE EVIDENCIAS Y ARCHIVOS ADJUNTOS (ALMACENAMIENTO SEGURO Y VISOR/DESCARGA)

## 1. OBJETIVO DEL PASO
Implementar, blindar e integrar de forma exhaustiva el subsistema de **Evidencias y Archivos Adjuntos** en **GIT** (Gestión de Incidencias Tecnológicas - APOC / GTS). Este módulo permite a los operadores de APOC y al personal técnico de GTS adjuntar capturas de pantalla, bitácoras de consola, trazas de reflectometría óptica (OTDR), manuales de subsistema y diagramas técnicos que respalden el reporte inicial y los avances de atención en campo. Se garantiza el almacenamiento privado no accesible públicamente (`storage/app/private/`), la aleatorización de nombres físicos mediante UUID v4 para mitigar ataques de inyección de rutas (*Path Traversal*), la validación estricta de extensiones y tipos MIME autorizados (JPG, PNG, PDF, TXT; máx. 10 MB), un visor en línea (*inline stream*) para inspección en navegador, descarga forzada controlada por políticas de autorización (`IncidentPolicy::downloadAttachment` y `uploadAttachment`), y la inmutabilidad de adjuntos una vez que el expediente ha sido formalmente cerrado por APOC.

---

## 2. POR QUÉ LO HACEMOS
En un entorno aeroportuario de alta seguridad como el AIFA:
1. **Soporte Forense y Documental:** Un reporte que describa *"pérdida de señal en cámara domo"* o *"timeout en lector biométrico de e-Gates"* adquiere valor pericial cuando se acompaña de evidencias gráficas (captura del monitor del C4) o de telecomunicaciones (diagrama de empalme o lectura de potencia óptica en dBm).
2. **Seguridad contra Inyección de Archivos y Ejecución Remota (RCE):** Permitir la subida de archivos arbitrarios a directorios web públicos (`public/`) representa un riesgo crítico de ciberseguridad (posible ejecución de webshells en `.php`, scripts maliciosos o suplantación de archivos). Al almacenar los archivos en el disco local privado y renombrarlos a UUID v4 (`550e8400-e29b-41d4-a716-446655440000.jpg`), el servidor web jamás ejecutará código malicioso ni permitirá adivinar URLs directas.
3. **Control Estricto de Acceso (RBAC y Segregación):** La descarga y visualización de archivos no se efectúa vía enlaces estáticos; cada solicitud pasa por un controlador Laravel (`IncidentAttachmentController`) que valida la sesión activa, la pertenencia del archivo a la incidencia y las autorizaciones institucionales.
4. **Inmutabilidad Post-Cierre (RN-04 / RF-06):** Cuando un expediente alcanza el estado `CERRADA`, el expediente queda sellado. No se permite inyectar evidencias extemporáneas que alteren los hechos auditados.

---

## 3. DEPENDENCIAS
* **Fase 3 (Base de Datos):** Tabla `incident_attachments` con claves foráneas `incident_id`, `uploaded_by_user_id`, y metadatos (`original_filename`, `stored_path`, `mime_type`, `file_size_bytes`).
* **Fase 4 (Seguridad y Autorización):**
  * `IncidentPolicy::downloadAttachment`: Garantiza acceso exclusivo a usuarios activos autenticados.
  * `IncidentPolicy::uploadAttachment`: Permite la carga a usuarios activos siempre que el ticket no esté cerrado.
  * `StoreAttachmentRequest`: Validación de archivos (máx. 5 archivos, extensiones JPG, PNG, PDF, TXT, máx. 10 MB por elemento).
* **Fase 5 (Ciclo de Vida) y Fase 6 (Auditoría):**
  * Integración con `AuditService::logAction` para generar el evento `ADJUNTOS_CARGADOS` en cada subida de evidencias.

---

## 4. ESTADO ACTUAL
* 119 pruebas automatizadas aprobadas al 100% (403 aserciones).
* Base de datos PostgreSQL `git_incidentes` con integridad referencial activa.
* Interfaz táctica Blade con Alpine.js preparada para modales de carga y previsualización de evidencias.

---

## 5. PLAN DE EJECUCIÓN
1. **Enriquecimiento del Modelo (`IncidentAttachment.php`):**
   * Incorporar accesores para presentación táctica:
     * `formatted_size`: Conversión automática a B, KB o MB.
     * `is_image` / `is_pdf`: Banderas booleanas de tipo MIME.
     * `file_icon`: Selección dinámica de icono Material Symbols (`image`, `picture_as_pdf`, `description`, `attach_file`).
     * `file_badge_color`: Clases Tailwind semánticas.
2. **Capa de Almacenamiento Seguro (`FileStorageService.php`):**
   * Implementar `storeAttachments(Incident $incident, User $uploader, array $files): array`.
   * Implementar `exists(IncidentAttachment $attachment): bool`.
   * Implementar `streamInline(IncidentAttachment $attachment): BinaryFileResponse` (entrega para visualización en navegador).
   * Implementar `download(IncidentAttachment $attachment): StreamedResponse` (descarga forzada con nombre original).
3. **Form Request de Carga (`StoreAttachmentRequest.php`):**
   * Validación de presencia, límite de 5 archivos, extensiones admitidas y tamaño máximo de 10 MB con mensajes en español.
4. **Controlador Web (`IncidentAttachmentController.php`):**
   * `store`: Validación, guardado físico, registro en bitácora inmutable y redirección.
   * `show`: Validación de pertenencia al folio y entrega *inline*.
   * `download`: Validación y entrega como *attachment*.
5. **Políticas de Autorización (`IncidentPolicy.php`):**
   * Incorporar regla `uploadAttachment` (bloquea la subida en incidencias `CERRADA`).
6. **Definición de Rutas (`routes/web.php`):**
   * `POST /incidencias/{incident}/adjuntos` (`incidents.attachments.store`).
   * `GET /incidencias/{incident}/adjuntos/{attachment}/ver` (`incidents.attachments.show`).
   * `GET /incidencias/{incident}/adjuntos/{attachment}/descargar` (`incidents.attachments.download`).
7. **Interfaz de Usuario Táctica (`show.blade.php`):**
   * Tarjeta dedicada de "Evidencias y Archivos Adjuntos" con conteo, miniatura para imágenes, visor, descarga y modal de carga Alpine.js (`showUploadModal`).
   * Banner de bloqueo inmutable cuando el ticket está cerrado.
8. **Suite de Pruebas Automatizadas (`tests/Feature/IncidentAttachmentTest.php`):**
   * Cobertura de 11 pruebas exhaustivas (invitados, usuarios inactivos, carga válida, bloqueo post-cierre, validaciones MIME y tamaño, cruce de expedientes, visualización inline y descarga).

---

## 6. ARCHIVOS INVOLUCRADOS
* `app/Models/IncidentAttachment.php` (Modificado: accesores de tamaño, formato e icono)
* `app/Services/FileStorageService.php` (Modificado: entrega segura inline y descarga con verificación de existencia)
* `app/Policies/IncidentPolicy.php` (Modificado: método `uploadAttachment` con blindaje inmutable)
* `app/Http/Requests/StoreAttachmentRequest.php` (Creado: validación de evidencias)
* `app/Http/Controllers/IncidentAttachmentController.php` (Creado: controlador de carga, visor y descarga)
* `routes/web.php` (Modificado: rutas de adjuntos)
* `resources/views/incidents/show.blade.php` (Modificado: sección de evidencias, miniaturas, acciones y modal Alpine.js)
* `tests/Feature/IncidentAttachmentTest.php` (Creado: 11 pruebas unitarias y de integración)
* `docs/27_evidencias_y_adjuntos.md` (Creado: documentación técnica y pedagógica)

---

## 7. IMPLEMENTACIÓN DETALLADA

### 7.1. Servicio de Almacenamiento Seguro: `app/Services/FileStorageService.php`
```php
public function storeAttachments(Incident $incident, User $uploader, array $files): array
{
    $attachments = [];

    foreach ($files as $file) {
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            continue;
        }

        $originalName = $file->getClientOriginalName();
        $extension    = $file->getClientOriginalExtension() ?: 'bin';
        $mimeType     = $file->getMimeType() ?? 'application/octet-stream';
        $fileSize     = $file->getSize();

        // Nombre criptográfico UUID v4 para evitar sobreescrituras e inyecciones de ruta
        $safeFileName = Str::uuid()->toString() . '.' . strtolower($extension);
        $directory    = 'incidents/' . $incident->id;

        // Almacenamiento privado seguro (disco 'local')
        $storedPath = $file->storeAs($directory, $safeFileName, 'local');

        $attachments[] = IncidentAttachment::create([
            'incident_id' => $incident->id,
            'uploaded_by_user_id' => $uploader->id,
            'original_filename' => $originalName,
            'stored_path' => $storedPath,
            'mime_type' => $mimeType,
            'file_size_bytes' => $fileSize,
        ]);
    }

    return $attachments;
}

public function streamInline(IncidentAttachment $attachment): BinaryFileResponse
{
    $path = Storage::disk('local')->path($attachment->stored_path);

    return response()->file($path, [
        'Content-Type' => $attachment->mime_type,
        'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_filename) . '"',
    ]);
}

public function download(IncidentAttachment $attachment): StreamedResponse
{
    return Storage::disk('local')->download($attachment->stored_path, $attachment->original_filename);
}
```

### 7.2. Controlador Web: `app/Http/Controllers/IncidentAttachmentController.php`
```php
public function store(StoreAttachmentRequest $request, Incident $incident): RedirectResponse
{
    Gate::authorize('uploadAttachment', $incident);

    $files = $request->file('attachments', []);
    $stored = $this->fileStorageService->storeAttachments($incident, $request->user(), $files);

    $count = count($stored);
    $this->auditService->logAction(
        incidentId: $incident->id,
        userId: $request->user()->id,
        action: 'ADJUNTOS_CARGADOS',
        fieldName: 'attachments',
        comment: "{$count} archivo(s) de evidencia adjuntado(s) por {$request->user()->name}."
    );

    return redirect()->route('incidents.show', $incident->folio)
        ->with('success', "{$count} archivo(s) de evidencia adjuntado(s) exitosamente al expediente.");
}

public function show(Incident $incident, IncidentAttachment $attachment): BinaryFileResponse
{
    Gate::authorize('downloadAttachment', $incident);

    abort_if($attachment->incident_id !== $incident->id, 404, 'La evidencia no pertenece a este expediente.');
    abort_if(!$this->fileStorageService->exists($attachment), 404, 'El archivo físico no se encuentra disponible.');

    return $this->fileStorageService->streamInline($attachment);
}

public function download(Incident $incident, IncidentAttachment $attachment): StreamedResponse
{
    Gate::authorize('downloadAttachment', $incident);

    abort_if($attachment->incident_id !== $incident->id, 404, 'La evidencia no pertenece a este expediente.');
    abort_if(!$this->fileStorageService->exists($attachment), 404, 'El archivo físico no se encuentra disponible.');

    return $this->fileStorageService->download($attachment);
}
```

---

## 8. EXPLICACIÓN TÉCNICA PARA UN ESTUDIANTE DE TELECOMUNICACIONES

### La Telemetría Binaria, el Análisis de Espectro y la Transferencia Segura TFTP/SFTP
Para un Ingeniero en Telecomunicaciones, la gestión de evidencias técnicas en un sistema como **GIT** responde a los mismos principios de seguridad que el resguardo de **trazas de reflectometría óptica (OTDR)** y **archivos de volcado de tramas (PCAP / Wireshark)**:

1. **Aislamiento del Medio de Almacenamiento (Private Storage vs. Public DMZ):** Cuando un analizador de redes monitorea el tráfico del subsistema FIDS o de las cámaras de seguridad del aeropuerto, los archivos `.pcap` con capturas de paquetes o las curvas de reflectometría de fibra jamás se almacenan en un servidor web público expuesto a Internet. Se guardan en una SAN/NAS privada dentro de la VLAN de gestión segura. En Laravel, almacenar en `storage/app/private/` garantiza que ningún usuario externo pueda descargar una evidencia simplemente adivinando su URL estática (`/uploads/foto.jpg`).
2. **Aleatorización de Nombres (Criptografía UUID v4 vs. Path Traversal):** En telecomunicaciones, cuando un enrutador envía volcados de memoria (*Core Dumps*) vía TFTP o SFTP, los nombres se identifican por hash criptográfico o identificador determinista para evitar que dos dispositivos colisionen o que una instrucción en el nombre intente escapar del directorio (`../../../etc/shadow`). Al renombrar cada archivo subido con un UUID v4 (`Str::uuid()`), eliminamos de raíz el vector de ataque de *Directory Traversal*.
3. **MIME Sniffing y Content-Disposition:** Al transmitir archivos binarios a través del protocolo HTTP, las cabeceras definen el comportamiento de capa de aplicación:
   * `Content-Type: image/jpeg`: Informa al navegador que el flujo de bytes es un mapa de bits comprimido JPEG para que lo decodifique y renderice directamente (*inline*).
   * `Content-Disposition: attachment; filename="..."`: Fuerza al agente de usuario a abrir el cuadro de diálogo de descarga preservando el nombre técnico original documentado por el operador.

---

## 9. PRUEBAS AUTOMATIZADAS
Se implementó la suite completa [`tests/Feature/IncidentAttachmentTest.php`](file:///c:/Users/Asus/Desktop/GIT/tests/Feature/IncidentAttachmentTest.php) con 11 pruebas automatizadas:

| # | Prueba en `IncidentAttachmentTest.php` | Propósito Técnico | Resultado |
|---|---|---|---|
| 1 | `test_guest_cannot_upload_attachments` | Usuarios no autenticados son redirigidos a `/login`. | **Aprobado** |
| 2 | `test_guest_cannot_view_or_download_attachments` | Visor y descarga bloqueados para peticiones sin sesión. | **Aprobado** |
| 3 | `test_authenticated_user_can_upload_valid_attachments_to_active_incident` | Carga de JPG y PDF a ticket activo; valida almacenamiento privado, BD y auditoría. | **Aprobado** |
| 4 | `test_cannot_upload_attachment_when_incident_is_closed` | Bloqueo estricto con `403 Forbidden` en expedientes en estado `CERRADA`. | **Aprobado** |
| 5 | `test_attachment_validation_rejects_unauthorized_mimes` | Rechazo de extensiones peligrosas (`.php`, ejecutables). | **Aprobado** |
| 6 | `test_attachment_validation_rejects_file_exceeding_10mb` | Rechazo de archivos que superen el límite de 10 MB. | **Aprobado** |
| 7 | `test_attachment_validation_rejects_more_than_5_files` | Bloqueo de cargas masivas de más de 5 archivos simultáneos. | **Aprobado** |
| 8 | `test_authenticated_user_can_view_attachment_inline` | Entrega de archivo con cabecera `inline` para previsualización. | **Aprobado** |
| 9 | `test_authenticated_user_can_download_attachment` | Entrega de archivo con cabecera de descarga y nombre original. | **Aprobado** |
| 10 | `test_cannot_access_attachment_belonging_to_another_incident` | Detección de cruce de folios (retorna `404 Not Found`). | **Aprobado** |
| 11 | `test_inactive_user_cannot_access_attachments` | Bloqueo de usuarios desactivados (`403 Forbidden`). | **Aprobado** |

### Resultado de Ejecución:
```powershell
php artisan test --filter=IncidentAttachmentTest
# {"tool":"phpunit","result":"passed","tests":11,"passed":11,"assertions":36,"duration_ms":1044}

php artisan test
# {"tool":"phpunit","result":"passed","tests":119,"passed":119,"assertions":403,"duration_ms":7842}
```

---

## 10. RESULTADO ESPERADO
1. En la vista de detalle de cualquier incidencia (`/incidencias/{folio}`), se despliega la tarjeta **"Evidencias y Archivos Adjuntos"** con el conteo de evidencias.
2. Cada evidencia se presenta con un badge coloreado según su formato (cyan para imágenes, carmesí para PDFs, ámbar para textos), nombre original, tamaño formateado (KB/MB), usuario que la subió y fecha de carga.
3. Si el archivo es una imagen, se renderiza una miniatura optimizada que se puede ampliar haciendo clic.
4. El operador cuenta con los botones **"Ver"** (abre en nueva pestaña de forma segura) y **"Descargar"** (fuerza la descarga física del archivo).
5. Mientras el ticket esté activo (`NUEVA`, `ASIGNADA`, `EN_ATENCION`, `RESUELTA`), se muestra el botón interactivo **"Adjuntar Evidencia"**, el cual despliega un modal Alpine.js para subir hasta 5 archivos válidos.
6. Si la incidencia se encuentra en estado terminal `CERRADA`, el sistema oculta el botón de subida y exhibe un aviso de expediente archivado e inmutable.

---

## 11. PROBLEMAS COMUNES Y SOLUCIONES

### Problema 1: Inyección de archivos ejecutables maliciosos
* **Causa:** Un usuario intenta subir un archivo con script malicioso renombrado a `.php` o `.sh`.
* **Solución:** `StoreAttachmentRequest` valida estrictamente la lista blanca de extensiones (`mimes:jpg,jpeg,png,pdf,txt`). Adicionalmente, el almacenamiento se realiza fuera de la raíz web pública en el disco privado de Laravel.

### Problema 2: Dependencia de la extensión PHP-GD en pruebas automatizadas
* **Causa:** Al invocar `UploadedFile::fake()->image(...)`, Laravel intenta invocar funciones de la librería GD de PHP, arrojando error si GD no está habilitado en CLI.
* **Solución:** Utilizar `UploadedFile::fake()->create('archivo.jpg', $size, 'image/jpeg')`, garantizando pruebas herméticas y desacopladas de librerías nativas del sistema operativo.

### Problema 3: Intento de cruce de identificadores (*Insecure Direct Object References - IDOR*)
* **Causa:** Un usuario solicita el folio `INC-2026-0001` pero pasa el ID de un adjunto perteneciente a `INC-2026-0089`.
* **Solución:** `IncidentAttachmentController` valida explícitamente `abort_if($attachment->incident_id !== $incident->id, 404)`, rechazando cualquier petición cruzada.

---

## 12. VERIFICACIÓN FINAL
* [x] Modelo `IncidentAttachment` dotado de accesores tácticos de tamaño, formato e icono.
* [x] Servicio `FileStorageService` ampliado con métodos de entrega segura *inline* y descarga forzada.
* [x] Form Request `StoreAttachmentRequest` implementado con validaciones de tamaño, formato y límite de archivos.
* [x] Controlador `IncidentAttachmentController` creado con métodos `store`, `show` y `download`.
* [x] Política `IncidentPolicy::uploadAttachment` implementada y blindada contra tickets cerrados.
* [x] Rutas de adjuntos registradas en `routes/web.php`.
* [x] Vista `resources/views/incidents/show.blade.php` actualizada con tarjetas de evidencia, miniaturas y modal Alpine.js.
* [x] Suite de pruebas `IncidentAttachmentTest.php` implementada con 11 pruebas superadas al 100%.
* [x] Suite global de pruebas automatizadas ejecutada al 100% en verde (119 tests, 403 aserciones).
* [x] Base de datos de desarrollo `git_incidentes` sembrada de forma consistente con `php artisan db:seed`.
* [x] **Paso 27 completado al 100%.**
* [x] **FASE 6 (TRAZABILIDAD) CULMINADA AL 100%.**

---

**STOPPING.** Esperando la instrucción explícita del usuario: `"EJECUTA EL PASO 28"`.

